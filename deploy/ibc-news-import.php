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
 *
 * What it never does: update, unpublish, trash or delete any existing post or page,
 * touch settings, users or plugins. Body HTML is filtered with wp_kses_post().
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

define('WP_USE_THEMES', false);
$_SERVER['HTTP_HOST']   = 'indonesiabriquettescharcoal.net';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
require $ibc_wp;

// Act as the first administrator so wp_insert_post sets a proper author.
$ibc_admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
if ($ibc_admins) { wp_set_current_user($ibc_admins[0]->ID); }

$ibc_created = 0;
$ibc_dirs = glob($ibc_repo . '/articles/*', GLOB_ONLYDIR) ?: [];
sort($ibc_dirs);
foreach ($ibc_dirs as $ibc_dir) {
    if ($ibc_created >= $ibc_max) { $ibc_log("limit of $ibc_max new posts reached, rest waits for next run"); break; }
    $ibc_meta = json_decode((string) @file_get_contents($ibc_dir . '/meta.json'), true);
    $ibc_body = (string) @file_get_contents($ibc_dir . '/body.html');
    $ibc_slug = basename($ibc_dir);
    if (!is_array($ibc_meta) || $ibc_body === '' || ($ibc_meta['slug'] ?? '') !== $ibc_slug || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $ibc_slug)) {
        $ibc_log("skip $ibc_slug: invalid meta/body");
        continue;
    }
    $ibc_existing = get_posts(['name' => $ibc_slug, 'post_type' => 'post', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'], 'numberposts' => 1, 'fields' => 'ids']);
    $ibc_existing = $ibc_existing ?: get_posts(['name' => $ibc_slug . '__trashed', 'post_type' => 'post', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids']);
    if ($ibc_existing) { continue; }
    if (get_page_by_path($ibc_slug, OBJECT, ['page'])) { $ibc_log("skip $ibc_slug: a page uses this slug"); continue; }

    $ibc_id = wp_insert_post([
        'post_type'     => 'post',
        'post_status'   => $ibc_status,
        'post_title'    => wp_strip_all_tags($ibc_meta['title'] ?? ''),
        'post_name'     => $ibc_slug,
        'post_content'  => wp_kses_post($ibc_body),
        'post_excerpt'  => wp_strip_all_tags($ibc_meta['excerpt'] ?? ''),
        'post_category' => [$ibc_cat],
    ], true);
    if (is_wp_error($ibc_id)) { $ibc_log("ERROR $ibc_slug: " . $ibc_id->get_error_message()); continue; }
    update_post_meta($ibc_id, '_genesis_title', wp_strip_all_tags($ibc_meta['seo_title'] ?? ''));
    update_post_meta($ibc_id, '_genesis_description', wp_strip_all_tags($ibc_meta['description'] ?? ''));
    $ibc_created++;
    $ibc_log("created post $ibc_id ($ibc_status): $ibc_slug " . get_permalink($ibc_id));
}
if ($ibc_created === 0) { $ibc_log('no new articles'); }
