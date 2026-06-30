<?php
/**
 * One-time migration: title_main values
 * 
 * HOW TO USE:
 * 1. Drop this file in your plugin folder
 * 2. Visit: https://yoursite.com/wp-content/plugins/haifa-staff/migrate-title-main.php
 * 3. Delete this file after running
 */

// Bootstrap WordPress
$wp_load = dirname(__FILE__);
while (!file_exists($wp_load . '/wp-load.php')) {
    $wp_load = dirname($wp_load);
    if ($wp_load === '/') die('wp-load.php not found');
}
require_once $wp_load . '/wp-load.php';

// Must be admin
if (!current_user_can('manage_options')) {
    die('Unauthorized.');
}

// Migration map: old value => new value
$map = [
    'professor'           => 'mr',
    'associate_professor' => 'mrs',
    'assistant_professor' => 'dr',
    'lecturer'            => 'prof',
    // 'other' stays 'other' - no change needed
];

global $wpdb;

$results = [];
$total   = 0;

foreach ($map as $old => $new) {
    $rows = $wpdb->query($wpdb->prepare("
        UPDATE {$wpdb->postmeta}
        SET meta_value = %s
        WHERE meta_key = 'title_main'
        AND meta_value = %s
    ", $new, $old));

    $results[] = "'{$old}' → '{$new}': {$rows} rows updated";
    $total += $rows;
}

// Output results
echo '<pre>';
echo "=== title_main Migration Results ===\n\n";
foreach ($results as $line) {
    echo $line . "\n";
}
echo "\nTotal updated: {$total} rows\n\n";
echo "Done. Delete this file now.\n";
echo '</pre>';
