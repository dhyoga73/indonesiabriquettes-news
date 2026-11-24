<?php
/**
 * Server-side importer for indonesiabriquettescharcoal.net News (PT DAN).
 *
 * Installed at ~/bin/ibc-news-import.php on the cPanel server. This copy in the
 * repo is for reference only: the cron job runs the server copy, so changes
 * pushed to GitHub can never change what runs on the server.
 *
 * What it does, every run:
 *  - reads ~/repositories/indonesiabriquettes-news/articles/<slug>/{meta.json,body.html}
 *  - for each slug that does NOT yet exist in WordPress (any status, incl. trash),
 *    creates ONE new post (category, excerpt, The SEO Framework title + description)
 *  - status comes from settings.json "post_status" and may only be "draft" or "publish"
 *  - featured image: if meta.json has "image": "images/<file>.jpg", the photo is added to the
 *    Media Library once (re-used afterwards) and set as the post's featured image. For posts
 *    that already exist, a featured image is ONLY added when the post has none yet.
 *
 * What it never does: change the text, title, status or SEO fields of an existing post,
 * replace an existing featured image, trash or delete anything, touch pages, settings,
 * users or plugins. Body HTML is filtered with wp_kses_post().
 */
if (PHP_SAPI !== 'cli') { exit(1); }

$ibc_home = getenv('HOME') ?: '/home/indone35';
$ibc_repo = $ibc_home . '/repositories/indonesiabriquettes-news';
$ibc_wp   = $ibc_home . '/public_html/indonesiabriquettescharcoal.net/wp-load.php';
$ibc_log  = function ($m) { echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"; };

$ibc_settings = json_decode((string) @file_get_contents($ibc_repo . '/settings.json'), true) ?: [];
$ibc_status   = in_array($ibc_settings['post_status'] ?? 'draft', ['draft', 'publish'], true) ? $ibc_settings['post_status'] : 'draft';
$ibc_cat      = (int) ($ibc_settings['category_id'] ?? 5);
$ibc_max      = max(1, min(5, (int) ($ibc_settings['max_new_posts_per_run'] ?? 2)));
$ibc_max_img  = 3;

define('WP_USE_THEMES', false);
$_SERVER['HTTP_HOST']   = 'indonesiabriquettescharcoal.net';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
require $ibc_wp;
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Act as the first administrator so wp_insert_post sets a proper author.
$ibc_admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
if ($ibc_admins) { wp_set_current_user($ibc_admins[0]->ID); }

// Image catalogue: images/catalog.json -> [{file, alt}]
$ibc_catalog = [];
foreach ((json_decode((string) @file_get_contents($ibc_repo . '/images/catalog.json'), true) ?: []) as $ibc_c) {
    if (!empty($ibc_c['file'])) { $ibc_catalog[$ibc_c['file']] = (string) ($ibc_c['alt'] ?? ''); }
}

/** Returns an attachment ID for repo image $rel (adds it to the Media Library once), or 0. */
$ibc_image_id = function ($rel, $alt, $parent) use ($ibc_repo, $ibc_log) {
    if (!preg_match('#^images/[a-z0-9]+(-[a-z0-9]+)*\.(jpe?g|png|webp)$#', $rel)) { $ibc_log("image rejected: $rel"); return 0; }
    $path = realpath($ibc_repo . '/' . $rel);
    if (!$path || strpos($path, realpath($ibc_repo . '/images') . '/') !== 0 || !is_file($path)) { $ibc_log("image missing: $rel"); return 0; }
    $found = get_posts(['post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
                        'meta_key' => '_ibc_source', 'meta_value' => $rel]);
    if ($found) { return (int) $found[0]; }
    $tmp = wp_tempnam(basename($path));
    if (!$tmp || !copy($path, $tmp)) { $ibc_log("image copy failed: $rel"); return 0; }
    $att = media_handle_sideload(['name' => basename($path), 'tmp_name' => $tmp], $parent, $alt);
    if (is_wp_error($att)) { @unlink($tmp); $ibc_log("image ERROR $rel: " . $att->get_error_message()); return 0; }
    update_post_meta($att, '_wp_attachment_image_alt', $alt);
    update_post_meta($att, '_ibc_source', $rel);
    return (int) $att;
};

$ibc_created = 0;
$ibc_images  = 0;
$ibc_dirs = glob($ibc_repo . '/articles/*', GLOB_ONLYDIR) ?: [];
sort($ibc_dirs);
foreach ($ibc_dirs as $ibc_dir) {
    $ibc_meta = json_decode((string) @file_get_contents($ibc_dir . '/meta.json'), true);
    $ibc_body = (string) @file_get_contents($ibc_dir . '/body.html');
    $ibc_slug = basename($ibc_dir);
    if (!is_array($ibc_meta) || $ibc_body === '' || ($ibc_meta['slug'] ?? '') !== $ibc_slug || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $ibc_slug)) {
        $ibc_log("skip $ibc_slug: invalid meta/body");
        continue;
    }
    $ibc_img_rel = (string) ($ibc_meta['image'] ?? '');
    $ibc_img_alt = wp_strip_all_tags((string) ($ibc_meta['image_alt'] ?? ($ibc_catalog[$ibc_img_rel] ?? ($ibc_meta['title'] ?? ''))));

    $ibc_existing = get_posts(['name' => $ibc_slug, 'post_type' => 'post', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future'], 'numberposts' => 1, 'fields' => 'ids']);
    $ibc_trashed  = get_posts(['name' => $ibc_slug, 'post_type' => 'post', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids'])
                 ?: get_posts(['name' => $ibc_slug . '__trashed', 'post_type' => 'post', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids']);
    if ($ibc_trashed) { continue; }
    if ($ibc_existing) {
        // Existing post: only add a featured image when it has none.
        $ibc_pid = (int) $ibc_existing[0];
        if ($ibc_img_rel !== '' && !has_post_thumbnail($ibc_pid) && $ibc_images < $ibc_max_img) {
            $ibc_att = $ibc_image_id($ibc_img_rel, $ibc_img_alt, $ibc_pid);
            if ($ibc_att) { set_post_thumbnail($ibc_pid, $ibc_att); $ibc_images++; $ibc_log("featured image added to post $ibc_pid: $ibc_img_rel"); }
        }
        continue;
    }
    if ($ibc_created >= $ibc_max) { $ibc_log("limit of $ibc_max new posts reached, rest waits for next run"); continue; }
    if (get_page_by_path($ibc_slug, OBJECT, ['page'])) { $ibc_log("skip $ibc_slug: a page uses this slug"); continue; }

    $ibc_id = wp_insert_post([
        'post_type'     => 'post',
        'post_status'   => 'draft',
        'post_title'    => wp_strip_all_tags($ibc_meta['title'] ?? ''),
        'post_name'     => $ibc_slug,
        'post_content'  => wp_kses_post($ibc_body),
        'post_excerpt'  => wp_strip_all_tags($ibc_meta['excerpt'] ?? ''),
        'post_category' => [$ibc_cat],
    ], true);
    if (is_wp_error($ibc_id)) { $ibc_log("ERROR $ibc_slug: " . $ibc_id->get_error_message()); continue; }
    update_post_meta($ibc_id, '_genesis_title', wp_strip_all_tags($ibc_meta['seo_title'] ?? ''));
    update_post_meta($ibc_id, '_genesis_description', wp_strip_all_tags($ibc_meta['description'] ?? ''));
    if ($ibc_img_rel !== '') {
        $ibc_att = $ibc_image_id($ibc_img_rel, $ibc_img_alt, $ibc_id);
        if ($ibc_att) { set_post_thumbnail($ibc_id, $ibc_att); $ibc_images++; }
    }
    // Publish only after SEO fields and featured image are in place.
    if ($ibc_status === 'publish') { wp_update_post(['ID' => $ibc_id, 'post_status' => 'publish']); }
    $ibc_created++;
    $ibc_log("created post $ibc_id ($ibc_status): $ibc_slug " . get_permalink($ibc_id));
}
if ($ibc_created === 0) { $ibc_log('no new articles'); }
