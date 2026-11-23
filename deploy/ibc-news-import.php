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

$home = getenv('HOME') ?: '/home/indone35';
$repo = $home . '/repositories/indonesiabriquettes-news';
$wp   = $home . '/public_html/indonesiabriquettescharcoal.net/wp-load.php';
$log  = function ($m) { echo '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"; };

$settings = json_decode((string) @file_get_contents($repo . '/settings.json'), true) ?: [];
$status   = in_array($settings['post_status'] ?? 'draft', ['draft', 'publish'], true) ? $settings['post_status'] : 'draft';
$cat      = (int) ($settings['category_id'] ?? 5);
$max      = max(1, min(5, (int) ($settings['max_new_posts_per_run'] ?? 2)));

define('WP_USE_THEMES', false);
$_SERVER['HTTP_HOST']   = 'indonesiabriquettescharcoal.net';
$_SERVER['HTTPS']       = 'on';
$_SERVER['REQUEST_URI'] = '/';
require $wp;

// Act as the first administrator so wp_insert_post sets a proper author.
$admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC']);
if ($admins) { wp_set_current_user($admins[0]->ID); }

$created = 0;
$dirs = glob($repo . '/articles/*', GLOB_ONLYDIR) ?: [];
sort($dirs);
foreach ($dirs as $dir) {
    if ($created >= $max) { $log("limit of $max new posts reached, rest waits for next run"); break; }
    $meta = json_decode((string) @file_get_contents($dir . '/meta.json'), true);
    $body = (string) @file_get_contents($dir . '/body.html');
    $slug = basename($dir);
    if (!is_array($meta) || $body === '' || ($meta['slug'] ?? '') !== $slug || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
        $log("skip $slug: invalid meta/body");
        continue;
    }
    $existing = get_posts(['name' => $slug, 'post_type' => 'post', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'], 'numberposts' => 1, 'fields' => 'ids']);
    $existing = $existing ?: get_posts(['name' => $slug . '__trashed', 'post_type' => 'post', 'post_status' => 'trash', 'numberposts' => 1, 'fields' => 'ids']);
    if ($existing) { continue; }
    if (get_page_by_path($slug, OBJECT, ['page'])) { $log("skip $slug: a page uses this slug"); continue; }

    $id = wp_insert_post([
        'post_type'     => 'post',
        'post_status'   => $status,
        'post_title'    => wp_strip_all_tags($meta['title'] ?? ''),
        'post_name'     => $slug,
        'post_content'  => wp_kses_post($body),
        'post_excerpt'  => wp_strip_all_tags($meta['excerpt'] ?? ''),
        'post_category' => [$cat],
    ], true);
    if (is_wp_error($id)) { $log("ERROR $slug: " . $id->get_error_message()); continue; }
    update_post_meta($id, '_genesis_title', wp_strip_all_tags($meta['seo_title'] ?? ''));
    update_post_meta($id, '_genesis_description', wp_strip_all_tags($meta['description'] ?? ''));
    $created++;
    $log("created post $id ($status): $slug " . get_permalink($id));
}
if ($created === 0) { $log('no new articles'); }
