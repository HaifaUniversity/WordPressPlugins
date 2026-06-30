<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! is_admin() ) {
    return;
}

/**
 * Staff admin filters (edit.php?post_type=staff)
 * -----------------------------------------------------------
 * Keeps a single custom filter row in Hebrew + a custom ACF image column.
 *
 * Features:
 * - Custom filters row (tags, categories, staff_type, staff_academic_type, empty wysiwyg)
 * - Tags/categories dropdowns show only terms attached to staff posts
 * - Categories show staff-only count in parentheses
 * - ACF selects loaded from field choices (by field name)
 * - staff_academic_type filter shown only when staff_type value === 'academic'
 * - "Empty fields" detects WYSIWYG fields with < 20 chars after stripping HTML
 * - List table: show ACF image staff_image, remove Featured Image column
 */

/* ============================================================
 * 0) Screen guard
 * ============================================================ */
if (!function_exists('haifa_staff_is_edit_screen')) :
function haifa_staff_is_edit_screen(): bool {
    if (!is_admin()) return false;
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    return ($screen && $screen->id === 'edit-staff');
}
endif;

/* ============================================================
 * 1) Admin UI: CSS (filter row + hide other plugin dropdowns)
 * ============================================================ */
add_action('admin_head', function () {
    if (!haifa_staff_is_edit_screen()) return;

    echo '<style>
        .haifa-staff-filters-row{
            width:100%;
            display:flex;
            gap:8px;
            flex-wrap:nowrap;
            align-items:center;
            margin:8px 0 10px;
            padding:8px 10px;
            background:#fff;
            border:1px solid #dcdcde;
            border-radius:6px;
            overflow-x:auto;
            clear:both;
        }
        .haifa-staff-filters-row select.haifa-staff-filter{
            max-width:220px;
        }

        /* Hide extra filters but KEEP bulk actions */
		body.post-type-staff #posts-filter .tablenav.top .actions:not(.bulkactions) select:not(.haifa-staff-filter),
		body.post-type-staff #posts-filter .tablenav.top .actions:not(.bulkactions) input:not(.haifa-staff-filter),
		body.post-type-staff #posts-filter .tablenav.top .actions:not(.bulkactions) button:not(.haifa-staff-filter){
			display:none !important;
		}


        /* Narrow our ACF image column */
        .column-staff_image_acf { width:70px; }
    </style>';
});

/* ============================================================
 * 2) Admin UI: JS (show "סוג אקדמי" only when staff_type === 'academic')
 * ============================================================ */
add_action('admin_footer', function () {
    if (!haifa_staff_is_edit_screen()) return;
    ?>
    <script>
    (function(){
        const staffType = document.getElementById('haifa_staff_type');
        const academicBlock = document.getElementById('haifa_staff_academic_block');
        const academicSelect = document.getElementById('haifa_staff_academic_type');

        if (!staffType || !academicBlock) return;

        function toggle(){
            // Compare by stored value (not Hebrew label)
            const isAcademic = (staffType.value || '').trim() === 'academic';
            academicBlock.style.display = isAcademic ? '' : 'none';

            // If hidden, clear value so it won't keep filtering
            if (!isAcademic && academicSelect) {
                academicSelect.value = '';
            }
        }

        staffType.addEventListener('change', toggle);
        toggle();
    })();
    </script>
    <?php
});

/* ============================================================
 * 3) Filters Row: render on staff list table
 * ============================================================ */
add_action('restrict_manage_posts', function($post_type) {
    if ($post_type !== 'staff') return;

    $selected_tag   = isset($_GET['staff_tag']) ? (int) $_GET['staff_tag'] : 0;
    $selected_cat   = isset($_GET['staff_cat']) ? (int) $_GET['staff_cat'] : 0;
    $selected_empty = isset($_GET['staff_empty_field']) ? sanitize_key($_GET['staff_empty_field']) : '';

    // WYSIWYG fields list (dropdown)
    $wysiwyg_fields = [
        'about'               => 'אודות',
        'publications'        => 'פרסומים',
        'education'           => 'השכלה',
        'academic_background' => 'רקע אקדמי',
        'research_areas'      => 'תחומי מחקר',
        'media'               => 'מדיה',
        'prizes'              => 'פרסים',
        'additional_info'     => 'מידע נוסף',
    ];

    // Small helper: labeled block above each select
    $block_start = function(string $label) {
        echo '<div style="display:flex;flex-direction:column;gap:4px;min-width:190px;">';
        echo '<div style="font-size:12px;line-height:1;color:#50575e;">' . esc_html($label) . '</div>';
    };
    $block_end = function() {
        echo '</div>';
    };

    echo '<div class="haifa-staff-filters-row">';

    /* תגיות */
    $block_start('תגיות');
    echo '<select class="haifa-staff-filter" name="staff_tag">';
    echo '<option value="">' . esc_html__('בחר', 'haifa-staff') . '</option>';
    foreach (haifa_staff_get_terms_used_by_staff('post_tag') as $t) {
        printf(
            '<option value="%d" %s>%s (%d)</option>',
            (int) $t->term_id,
            selected($selected_tag, (int) $t->term_id, false),
            esc_html($t->name),
            (int) $t->count
        );
    }
    echo '</select>';
    $block_end();

    /* קטגוריות (with staff-only counts) */
    $block_start('קטגוריות');
    $cats = haifa_staff_get_terms_used_by_staff('category');
    $cat_counts = haifa_staff_get_term_counts_for_staff('category');

    echo '<select class="haifa-staff-filter" name="staff_cat">';
    echo '<option value="">' . esc_html__('בחר', 'haifa-staff') . '</option>';
    foreach ($cats as $c) {
        $staff_count = $cat_counts[(int) $c->term_id] ?? 0;
        printf(
            '<option value="%d" %s>%s (%d)</option>',
            (int) $c->term_id,
            selected($selected_cat, (int) $c->term_id, false),
            esc_html($c->name),
            (int) $staff_count
        );
    }
    echo '</select>';
    $block_end();

    /* סוג סגל (ACF) */
    $block_start('סוג סגל');
    haifa_staff_render_acf_select_filter('staff_type', 'בחר', 'haifa_staff_type');
    $block_end();

    /* סוג אקדמי (ACF) - hidden until staff_type === academic */
    echo '<div id="haifa_staff_academic_block" style="display:none;">';
    $block_start('סוג אקדמי');
    haifa_staff_render_acf_select_filter('staff_academic_type', 'בחר', 'haifa_staff_academic_type');
    $block_end();
    echo '</div>';

    /* שדות ריקים */
    $block_start('שדות ריקים');
    echo '<select class="haifa-staff-filter" name="staff_empty_field">';
    echo '<option value="">' . esc_html__('בחר', 'haifa-staff') . '</option>';
    foreach ($wysiwyg_fields as $key => $label) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($key),
            selected($selected_empty, $key, false),
            esc_html($label)
        );
    }
    echo '</select>';
    $block_end();

    /* Submit button */
    echo '<div style="display:flex;flex-direction:column;gap:4px;">';
    echo '<div style="font-size:12px;line-height:1;color:transparent;">.</div>'; // align with labels
    echo '<button type="submit" class="button haifa-staff-filter">סינון</button>';
    echo '</div>';
	
	echo '<div style="display:flex;flex-direction:column;gap:4px;">';
	echo '<div style="font-size:12px;line-height:1;color:transparent;">.</div>';
	echo '<a href="' . esc_url( admin_url('edit.php?post_type=staff') ) . '" 
			  class=""
			  style="text-align:center;">איפוס</a>';
	echo '</div>';


    echo '</div>';
}, 5);

/* ============================================================
 * 4) Helpers
 * ============================================================ */

/**
 * Get terms (tags/categories) that are used by staff posts only.
 */
if (!function_exists('haifa_staff_get_terms_used_by_staff')) :
function haifa_staff_get_terms_used_by_staff(string $taxonomy): array {
    $staff_ids = get_posts([
        'post_type'      => 'staff',
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
    ]);

    if (!$staff_ids) return [];

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'object_ids' => $staff_ids,
        'orderby'    => 'name',
    ]);

    return is_wp_error($terms) ? [] : $terms;
}
endif;

/**
 * Render ACF select dropdown by field NAME.
 */
if (!function_exists('haifa_staff_render_acf_select_filter')) :
function haifa_staff_render_acf_select_filter(string $field_name, string $label, string $id = ''): void {
    $param    = 'staff_filter_' . $field_name;
    $selected = $_GET[$param] ?? '';
    $choices  = [];

    $all_choices = [
        'staff_type'          => ['academic' => 'אקדמי', 'administrative' => 'מנהלי'],
        'staff_academic_type' => ['senior' => 'בכיר', 'junior' => 'זוטר'],
    ];
    $choices = $all_choices[$field_name] ?? [];

    $id_attr = $id ? ' id="' . esc_attr($id) . '"' : '';

    echo '<select class="haifa-staff-filter" name="' . esc_attr($param) . '"' . $id_attr . '>';
    echo '<option value="">' . esc_html($label) . '</option>'; // "בחר"

    foreach ($choices as $val => $lab) {
        printf(
            '<option value="%s" %s>%s</option>',
            esc_attr($val),
            selected($selected, $val, false),
            esc_html($lab)
        );
    }

    echo '</select>';
}
endif;

/**
 * Staff-only counts for a taxonomy (used for Categories)
 * Returns [term_id => count].
 */
if (!function_exists('haifa_staff_get_term_counts_for_staff')) :
function haifa_staff_get_term_counts_for_staff(string $taxonomy): array {
    global $wpdb;

    $cache_key = 'haifa_staff_term_counts_' . $taxonomy . '_v1';
    $cached = get_transient($cache_key);
    if (is_array($cached)) return $cached;

    $sql = $wpdb->prepare("
        SELECT tt.term_id, COUNT(DISTINCT p.ID) AS cnt
        FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt
            ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->posts} p
            ON p.ID = tr.object_id
        WHERE tt.taxonomy = %s
          AND p.post_type = 'staff'
          AND p.post_status IN ('publish','draft','pending','private')
        GROUP BY tt.term_id
    ", $taxonomy);

    $rows = $wpdb->get_results($sql);
    $map = [];

    foreach ($rows as $r) {
        $map[(int) $r->term_id] = (int) $r->cnt;
    }

    set_transient($cache_key, $map, 300); // 5 minutes
    return $map;
}
endif;

/* ============================================================
 * 5) Query logic: apply filters to staff list query
 * ============================================================ */
add_action('pre_get_posts', function(WP_Query $q) {
    if (!haifa_staff_is_edit_screen() || !$q->is_main_query()) return;

    /* Taxonomy filters */
    $tax = [];
    if (!empty($_GET['staff_tag'])) {
        $tax[] = ['taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => (int) $_GET['staff_tag']];
    }
    if (!empty($_GET['staff_cat'])) {
        $tax[] = ['taxonomy' => 'category', 'field' => 'term_id', 'terms' => (int) $_GET['staff_cat']];
    }
    if ($tax) {
        $q->set('tax_query', array_merge(['relation' => 'AND'], $tax));
    }

    /* ACF select filters (LIKE supports serialized/multi + plain) */
    $meta = [];
    foreach (['staff_type', 'staff_academic_type'] as $f) {
        if (!empty($_GET['staff_filter_' . $f])) {
            $meta[] = [
                'key'     => $f,
                'value'   => sanitize_text_field($_GET['staff_filter_' . $f]),
                'compare' => 'LIKE',
            ];
        }
    }
    if ($meta) {
        $q->set('meta_query', array_merge(['relation' => 'AND'], $meta));
    }

    /* WYSIWYG empty/short filter (< 20 chars after stripping HTML) */
    if (!empty($_GET['staff_empty_field'])) {
        $field = sanitize_key($_GET['staff_empty_field']);
        $ids   = [];

        $inner = new WP_Query([
            'post_type'      => 'staff',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => $q->get('tax_query'),
            'meta_query'     => $q->get('meta_query'),
        ]);

        foreach ($inner->posts as $id) {
            $text = wp_strip_all_tags(html_entity_decode(
                (string) get_post_meta($id, $field, true),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            ));

            $text = trim(preg_replace('/\s+/u', ' ', str_replace('&nbsp;', ' ', $text)));

            if (mb_strlen($text, 'UTF-8') < 20) {
                $ids[] = $id;
            }
        }

        $q->set('post__in', $ids ?: [0]);
    }
});

/* ============================================================
 * 6) List table columns: ACF image column + remove featured image
 * ============================================================ */

/**
 * Remove Featured Image column from staff list table (if present).
 */
add_filter('manage_staff_posts_columns', function ($columns) {
    unset($columns['thumbnail'], $columns['featured_image'], $columns['post_thumbnail'], $columns['post_type']);		
	// Remove ALL Yoast columns
    foreach ($columns as $key => $label) {
        if (strpos($key, 'wpseo-') === 0) {
            unset($columns[$key]);
        }
    }
    return $columns;
}, 999);

/**
 * Add our ACF image column "תמונה" after the checkbox.
 */
add_filter('manage_staff_posts_columns', function ($columns) {
    $new = [];
    foreach ($columns as $k => $label) {
        $new[$k] = $label;
        if ($k === 'cb') {
            $new['staff_image_acf'] = 'תמונה';
        }
    }
    if (!isset($new['staff_image_acf'])) {
        $new = ['staff_image_acf' => 'תמונה'] + $columns;
    }
    return $new;
}, 20);

/**
 * Render ACF image field: staff_image
 */
add_action('manage_staff_posts_custom_column', function ($column, $post_id) {
    if ($column !== 'staff_image_acf') return;

    $img = get_post_meta($post_id, 'staff_image', true);

    $url = '';
    $alt = '';

    if (is_array($img)) {
        $url = $img['sizes']['thumbnail'] ?? $img['url'] ?? '';
        $alt = $img['alt'] ?? '';
    } elseif (is_numeric($img)) {
        $url = wp_get_attachment_image_url((int) $img, 'thumbnail') ?: '';
        $alt = get_post_meta((int) $img, '_wp_attachment_image_alt', true);
    } elseif (is_string($img)) {
        $url = $img;
    }

    if ($url) {
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;" />';
    } else {
        echo '—';
    }
}, 10, 2);
