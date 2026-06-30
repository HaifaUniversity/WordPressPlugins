<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Elementor Staff Grid Widget
 *
 * Displays a filterable grid of staff members.
 * Supports filtering by staff type, academic type, position, status, etc.
 */
class Haifa_Staff_Grid_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'haifa_staff_grid'; }
    public function get_title()      { return __( 'רשת סגל', 'haifa-staff' ); }
    public function get_icon()       { return 'eicon-gallery-grid'; }
    public function get_categories() { return [ 'university' ]; }
    public function get_keywords()   { return [ 'staff', 'grid', 'team', 'faculty', 'filter', 'סגל', 'רשת' ]; }

    // =========================================================================
    // CONTROLS
    // =========================================================================
    protected function register_controls() {

        // ── Display mode ──────────────────────────────────────────────────────
        $this->start_controls_section( 'section_mode', [
            'label' => __( 'מצב תצוגה', 'haifa-staff' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'display_mode', [
            'label'   => __( 'מצב', 'haifa-staff' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'tabs',
            'options' => [
                'tabs'   => __( 'לשוניות (ברירת מחדל)', 'haifa-staff' ),
                'manual' => __( 'בחירה ידנית', 'haifa-staff' ),
            ],
        ] );
        $this->end_controls_section();

        // ── Pre-filter (query-level) ───────────────────────────────────────────
        $this->start_controls_section( 'section_filters', [
            'label'     => __( 'סינון מקדים', 'haifa-staff' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'display_mode' => 'tabs' ],
        ] );

        $this->add_control( 'sorting_heading', [
            'label'     => __( 'מיון', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
        $this->add_control( 'order_by', [
            'label'   => __( 'מיין לפי', 'haifa-staff' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'last_name',
            'options' => [
                'last_name'  => __( 'שם משפחה', 'haifa-staff' ),
                'first_name' => __( 'שם פרטי', 'haifa-staff' ),
                'date'       => __( 'תאריך', 'haifa-staff' ),
                'title'      => __( 'כותרת', 'haifa-staff' ),
                'menu_order' => __( 'סדר תפריט', 'haifa-staff' ),
            ],
        ] );
        $this->add_control( 'order', [
            'label'   => __( 'סדר', 'haifa-staff' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'ASC',
            'options' => [
                'ASC'  => __( 'עולה (א-ת)', 'haifa-staff' ),
                'DESC' => __( 'יורד (ת-א)', 'haifa-staff' ),
            ],
        ] );

        $this->add_control( 'filtering_heading', [
            'label'     => __( 'סינון', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
        $this->add_control( 'filter_staff_type', [
            'label'       => __( 'סוג סגל', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'default'     => '',
            'options'     => [
                ''               => __( 'הכל', 'haifa-staff' ),
                'academic'       => __( 'אקדמי', 'haifa-staff' ),
                'administrative' => __( 'מנהלי', 'haifa-staff' ),
            ],
            'description' => __( 'סנן מראש לפי סוג סגל', 'haifa-staff' ),
        ] );
        $this->add_control( 'filter_academic_type', [
            'label'     => __( 'סוג אקדמי', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => '',
            'options'   => [
                ''       => __( 'הכל', 'haifa-staff' ),
                'senior' => __( 'בכיר', 'haifa-staff' ),
                'junior' => __( 'זוטר', 'haifa-staff' ),
            ],
            'condition' => [ 'filter_staff_type' => 'academic' ],
        ] );
        $this->add_control( 'filter_status', [
            'label'   => __( 'סטטוס', 'haifa-staff' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                ''        => __( 'הכל', 'haifa-staff' ),
                'alive'   => __( 'פעיל', 'haifa-staff' ),
                'retired' => __( 'גימלאות', 'haifa-staff' ),
                'dead'    => __( 'נפטר', 'haifa-staff' ),
            ],
        ] );

        // Taxonomy options
        $taxonomy_options = [];
        $cats = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false ] );
        $tags = get_terms( [ 'taxonomy' => 'post_tag',  'hide_empty' => false ] );
        if ( ! is_wp_error( $cats ) ) foreach ( $cats as $c ) $taxonomy_options[ 'category:' . $c->slug ] = '📁 ' . $c->name . ' (קטגוריה)';
        if ( ! is_wp_error( $tags ) ) foreach ( $tags as $t ) $taxonomy_options[ 'tag:' . $t->slug ]      = '🏷️ ' . $t->name . ' (תגית)';

        $this->add_control( 'filter_taxonomy', [
            'label'         => __( 'קטגוריות ותגיות', 'haifa-staff' ),
            'type'          => \Elementor\Controls_Manager::SELECT2,
            'multiple'      => true,
            'options'       => $taxonomy_options,
            'default'       => [],
            'label_block'   => true,
            'select2options' => [ 'placeholder' => __( 'בחר...', 'haifa-staff' ), 'allowClear' => true ],
            'description'   => __( 'סנן לפי קטגוריות או תגיות', 'haifa-staff' ),
        ] );

        $this->end_controls_section();

        // ── Frontend filters / tabs ────────────────────────────────────────────
        $this->start_controls_section( 'section_frontend_filters', [
            'label'     => __( 'סינון בממשק', 'haifa-staff' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'display_mode' => 'tabs' ],
        ] );

        $this->add_control( 'show_search', [
            'label'        => __( 'הצג תיבת חיפוש', 'haifa-staff' ),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
            'description'  => __( 'מציג/מסתיר את תיבת החיפוש בלבד — לא משפיע על הסינון בלשוניות.', 'haifa-staff' ),
        ] );
        $this->add_control( 'show_filters', [
            'label'     => __( 'הצג סרגל סינון (לשוניות)', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => __( 'הצג', 'haifa-staff' ),
            'label_off' => __( 'הסתר', 'haifa-staff' ),
            'return_value' => 'yes',
        ] );

        // Hidden internals
        $this->add_control( 'filter_by_staff_type', [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'filter_by_position',   [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'filter_layout',        [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'tabs' ] );

        // ── Default tabs (NO condition on show_filters — always visible in editor) ──
        $this->add_control( 'default_tabs_heading', [
            'label'     => __( 'לשוניות ברירת מחדל', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
        $this->add_control( 'default_tabs_description', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => __( 'גרור לשינוי סדר. השתמש בעין להסתיר/להציג לשוניות.', 'haifa-staff' ),
            'content_classes' => 'elementor-descriptor',
        ] );

        // Build staff options for select2
        $staff_options = [];
        $all_staff = get_posts( [ 'post_type' => 'staff', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
        foreach ( $all_staff as $s ) {
            $fn = get_post_meta( $s->ID, 'first_name', true );
            $ln = get_post_meta( $s->ID, 'last_name',  true );
            $dn = trim( $fn . ' ' . $ln ) ?: $s->post_title;
            $staff_options[ $s->ID ] = $dn . ' (' . $s->ID . ')';
        }

        // Default tabs repeater
        $default_tabs_repeater = new \Elementor\Repeater();
        $default_tabs_repeater->add_control( 'tab_id',    [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => '' ] );
        $default_tabs_repeater->add_control( 'tab_label', [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => '' ] );
        $default_tabs_repeater->add_control( 'visible', [
            'label'     => __( '👁️ הצג לשונית', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => __( 'כן', 'haifa-staff' ),
            'label_off' => __( 'לא', 'haifa-staff' ),
        ] );
        $default_tabs_repeater->add_control( 'featured_staff', [
            'label'       => __( 'בחר מנהלים מודגשים', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $staff_options,
            'default'     => [],
            'label_block' => true,
            'description' => __( 'בחר אנשי סגל. הסדר יהיה לפי שם משפחה אלא אם תגדיר סדר מותאם למטה ↓', 'haifa-staff' ),
        ] );
        $default_tabs_repeater->add_control( 'featured_staff_order', [
            'label'       => __( 'סדר מותאם אישית (אופציונלי)', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '292, 311, 294',
            'label_block' => true,
            'description' => __( 'הזן IDs בסדר הרצוי, מופרדים בפסיקים.', 'haifa-staff' ),
        ] );

        // Term filter options (reused below for custom tabs too)
        $filter_options = [];
        $cats_dt = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false ] );
        $tags_dt = get_terms( [ 'taxonomy' => 'post_tag',  'hide_empty' => false ] );
        if ( ! is_wp_error( $cats_dt ) ) foreach ( $cats_dt as $c ) $filter_options[ 'category:' . $c->slug ] = '📁 ' . $c->name . ' (קטגוריה)';
        if ( ! is_wp_error( $tags_dt ) ) foreach ( $tags_dt as $t ) $filter_options[ 'tag:' . $t->slug ]      = '🏷️ ' . $t->name . ' (תגית)';

        $default_tabs_repeater->add_control( 'tab_term_filter', [
            'label'          => __( 'סנן גם לפי (אופציונלי)', 'haifa-staff' ),
            'type'           => \Elementor\Controls_Manager::SELECT2,
            'multiple'       => true,
            'options'        => $filter_options,
            'default'        => [],
            'label_block'    => true,
            'select2options' => [ 'placeholder' => __( 'ללא סינון נוסף', 'haifa-staff' ), 'allowClear' => true ],
            'description'    => __( 'הוסף סינון לפי קטגוריה/תגית בנוסף לסינון ברירת המחדל של הלשונית', 'haifa-staff' ),
        ] );

        $this->add_control( 'default_tabs', [
            'label'       => __( 'לשוניות', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $default_tabs_repeater->get_controls(),
            'default'     => [
                [ 'tab_id' => 'senior',         'tab_label' => 'סגל אקדמי בכיר', 'visible' => 'yes', 'featured_staff' => [] ],
                [ 'tab_id' => 'administrative', 'tab_label' => 'סגל מנהלי',       'visible' => 'yes', 'featured_staff' => [] ],
                [ 'tab_id' => 'junior',         'tab_label' => 'סגל אקדמי זוטר',  'visible' => 'yes', 'featured_staff' => [] ],
                [ 'tab_id' => 'emeritus',       'tab_label' => 'אמריטוס',          'visible' => 'yes', 'featured_staff' => [] ],
                [ 'tab_id' => 'deceased',       'tab_label' => 'לזכרם',            'visible' => 'yes', 'featured_staff' => [] ],
            ],
            'title_field' => '<# var labels={"senior":"סגל אקדמי בכיר | Senior Academic Staff","administrative":"סגל מנהלי | Administrative Staff","junior":"סגל אקדמי זוטר | Junior Academic Staff","emeritus":"אמריטוס | Emeritus","deceased":"לזכרם | In Memoriam"}; #>{{{ labels[tab_id]||tab_id }}}',
            'item_actions' => [ 'add' => false, 'duplicate' => false, 'remove' => false, 'sort' => true ],
        ] );

        // ── Custom tabs (NO condition on show_filters) ────────────────────────
        $this->add_control( 'custom_tabs_heading', [
            'label'     => __( 'לשוניות מותאמות אישית', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ] );
        $this->add_control( 'custom_tabs_only', [
            'label'       => __( 'הצג רק לשוניות מותאמות', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => __( 'כן', 'haifa-staff' ),
            'label_off'   => __( 'לא', 'haifa-staff' ),
            'description' => __( 'הסתר לשוניות ברירת מחדל והצג רק לשוניות מותאמות אישית', 'haifa-staff' ),
        ] );
        $this->add_control( 'custom_tabs_description', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => __( 'הוסף לשוניות מותאמות אישית עם סינון לפי קטגוריות או תגיות', 'haifa-staff' ),
            'content_classes' => 'elementor-descriptor',
        ] );

        // Custom tabs repeater
        // Custom tabs repeater (uses $filter_options built above, extended with meta options)
        $filter_options = array_merge( [
            'staff_type:administrative' => '👔 סגל מנהלי (סוג)',
            'academic_type:junior'      => '🎓 אקדמי זוטר (דרגה)',
            'academic_type:senior'      => '🎓 אקדמי בכיר (דרגה)',
            'status:active'             => '✅ פעיל (סטטוס)',
            'status:inactive'           => '⏸️ לא פעיל (סטטוס)',
            'status:dead'               => '💐 נפטר (סטטוס)',
        ], $filter_options );

        $repeater = new \Elementor\Repeater();
        $repeater->add_control( 'visible', [
            'label'     => __( '👁️ הצג לשונית', 'haifa-staff' ),
            'type'      => \Elementor\Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'label_on'  => __( 'כן', 'haifa-staff' ),
            'label_off' => __( 'לא', 'haifa-staff' ),
        ] );
        $repeater->add_control( 'tab_name', [
            'label'       => __( 'שם הלשונית', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => __( 'לשונית מותאמת', 'haifa-staff' ),
            'label_block' => true,
        ] );
        $repeater->add_control( 'tab_name_en', [
            'label'       => __( 'Tab Name (English)', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
            'placeholder' => 'e.g. Custom Tab',
        ] );
        $repeater->add_control( 'insert_before', [
            'label'       => __( '📍 הצג לפני', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'default'     => 'end',
            'label_block' => true,
            'options'     => [
                ''               => '--- בחר מיקום ---',
                'senior'         => 'סגל אקדמי בכיר',
                'administrative' => 'סגל מנהלי',
                'junior'         => 'סגל אקדמי זוטר',
                'emeritus'       => 'אמריטוס',
                'deceased'       => 'לזכרם',
                'end'            => 'בסוף (אחרי כל הלשוניות)',
            ],
            'description' => __( 'בחר לפני איזו לשונית להציג את הלשונית המותאמת', 'haifa-staff' ),
        ] );
        $repeater->add_control( 'taxonomy_term', [
            'label'          => __( 'בחר סינון', 'haifa-staff' ),
            'type'           => \Elementor\Controls_Manager::SELECT2,
            'multiple'       => true,
            'options'        => $filter_options,
            'label_block'    => true,
            'select2options' => [ 'placeholder' => __( 'בחר קטגוריה, תגית, או שדה...', 'haifa-staff' ), 'allowClear' => true ],
            'description'    => __( 'בחר סוג סגל, דרגה, סטטוס, קטגוריה או תגית — ניתן לבחור מספר', 'haifa-staff' ),
        ] );
        $repeater->add_control( 'featured_staff', [
            'label'       => __( 'בחר מנהלים מודגשים', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $staff_options,
            'default'     => [],
            'label_block' => true,
            'description' => __( 'בחר אנשי סגל מודגשים', 'haifa-staff' ),
        ] );
        $repeater->add_control( 'featured_staff_order', [
            'label'       => __( 'סדר מותאם אישית (אופציונלי)', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '292, 311, 294',
            'label_block' => true,
            'description' => __( 'הזן IDs בסדר הרצוי, מופרדים בפסיקים.', 'haifa-staff' ),
        ] );

        $this->add_control( 'custom_tabs', [
            'label'        => __( 'לשוניות', 'haifa-staff' ),
            'type'         => \Elementor\Controls_Manager::REPEATER,
            'fields'       => $repeater->get_controls(),
            'default'      => [],
            'title_field'  => '{{{ tab_name }}}',
            'prevent_empty' => false,
            'item_actions' => [ 'add' => true, 'duplicate' => true, 'remove' => true, 'sort' => true ],
        ] );

        $this->end_controls_section();

        // ── Display settings ──────────────────────────────────────────────────
        $this->start_controls_section( 'section_display', [
            'label' => __( 'הגדרות תצוגה', 'haifa-staff' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'show_image',    [ 'label' => __( 'הצג תמונה',  'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'show_position', [ 'label' => __( 'הצג תפקיד', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'show_email',    [ 'label' => __( 'הצג אימייל', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'show_phone',    [ 'label' => __( 'הצג טלפון',  'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'show_office',   [ 'label' => __( 'הצג משרד',   'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'yes' ] );
        $this->add_control( 'button_text',    [ 'label' => __( 'טקסט כפתור',         'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => __( 'מידע נוסף', 'haifa-staff' ) ] );
        $this->add_control( 'button_text_en', [ 'label' => __( 'Button Text (English)', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => 'More Info', 'placeholder' => 'More Info' ] );
        $this->add_control( 'page_title',    [ 'label' => __( 'כותרת הדף (עברית)', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true, 'placeholder' => 'אנשי סגל' ] );
        $this->add_control( 'page_title_en', [ 'label' => __( 'Page Title (English)', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '', 'label_block' => true, 'placeholder' => 'Staff' ] );
        
        
        $this->add_control( 'start_in_english', [
            'label'       => __( 'אתחול באנגלית', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => __( 'כן', 'haifa-staff' ),
            'label_off'   => __( 'לא', 'haifa-staff' ),
            'description' => __( 'הצג את הגריד בגרסה האנגלית ללא תלות ב-URL', 'haifa-staff' ),
        ] );
        $this->add_control( 'show_lang_switcher', [
            'label'       => __( 'הצג בורר שפה', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => '',
            'label_on'    => __( 'כן', 'haifa-staff' ),
            'label_off'   => __( 'לא', 'haifa-staff' ),
            'description' => __( 'מציג כפתור EN/HE מעל הגריד', 'haifa-staff' ),
        ] );
        $this->end_controls_section();

        // ── Position context ──────────────────────────────────────────────────
        $this->start_controls_section( 'section_position_context', [
            'label' => __( 'תפקידים נוספים', 'haifa-staff' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );
        $this->add_control( 'position_context_key', [
            'label'       => __( 'מפתח הקשר', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'faculty-board',
            'label_block' => true,
            'description' => __( 'הזן את מפתח ההקשר שהוגדר בפרופיל חבר הסגל.', 'haifa-staff' ),
        ] );
        $this->end_controls_section();

        // ── Manual selection ──────────────────────────────────────────────────
        $this->start_controls_section( 'section_manual', [
            'label'     => __( 'בחירה ידנית', 'haifa-staff' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'display_mode' => 'manual' ],
        ] );
        $_manual_staff_all = get_posts( [ 'post_type' => 'staff', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
        $_manual_opts = [];
        foreach ( $_manual_staff_all as $_s ) {
            $fn = get_post_meta( $_s->ID, 'first_name', true );
            $ln = get_post_meta( $_s->ID, 'last_name',  true );
            $_manual_opts[ $_s->ID ] = ( trim( $fn . ' ' . $ln ) ?: $_s->post_title ) . ' (' . $_s->ID . ')';
        }
        $this->add_control( 'manual_staff', [
            'label'       => __( 'בחר אנשי סגל', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => $_manual_opts,
            'default'     => [],
            'label_block' => true,
            'description' => __( 'בחר את אנשי הסגל להצגה.', 'haifa-staff' ),
        ] );
        $this->add_control( 'manual_staff_order', [
            'label'       => __( 'סדר מותאם אישית (אופציונלי)', 'haifa-staff' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '292, 311, 294',
            'label_block' => true,
            'description' => __( 'הזן IDs מופרדים בפסיקים.', 'haifa-staff' ),
        ] );
        $this->end_controls_section();

        // ── Remote staff (hidden) ─────────────────────────────────────────────
        $this->start_controls_section( 'section_remote', [
            'label'     => __( 'סגל מרוחק', 'haifa-staff' ),
            'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
            'condition' => [ 'display_mode' => '__never__' ],
        ] );
        $remote_repeater = new \Elementor\Repeater();
        $_faculty_options = [ '' => __( '— בחר פקולטה —', 'haifa-staff' ) ];
        if ( defined( 'HAIFA_STAFF_SITES' ) && is_array( HAIFA_STAFF_SITES ) ) {
            foreach ( HAIFA_STAFF_SITES as $url => $label ) $_faculty_options[ $url ] = $label;
        }
        $remote_repeater->add_control( 'source_url',        [ 'label' => __( 'פקולטה', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => $_faculty_options, 'label_block' => true ] );
        $remote_repeater->add_control( 'fetch_mode',        [ 'label' => __( 'מצב שליפה', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'all', 'options' => [ 'all' => __( 'כל הסגל', 'haifa-staff' ), 'by_staff_type' => __( 'לפי סוג סגל', 'haifa-staff' ), 'by_tag' => __( 'לפי תגית', 'haifa-staff' ), 'by_slug' => __( 'בחירה ידנית (slug)', 'haifa-staff' ) ] ] );
        $remote_repeater->add_control( 'filter_staff_type', [ 'label' => __( 'סוג סגל', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'academic', 'options' => [ 'academic' => __( 'אקדמי', 'haifa-staff' ), 'administrative' => __( 'מנהלי', 'haifa-staff' ) ], 'condition' => [ 'fetch_mode' => 'by_staff_type' ] ] );
        $remote_repeater->add_control( 'filter_tag',        [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => '' ] );
        $remote_repeater->add_control( 'filter_slugs',      [ 'type' => \Elementor\Controls_Manager::HIDDEN, 'default' => '' ] );
        $remote_repeater->add_control( 'cache_hours',       [ 'label' => __( 'זמן מטמון (שעות)', 'haifa-staff' ), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 72 ] );
        $this->add_control( 'remote_staff_rows', [
            'label'      => __( 'אתרי מקור', 'haifa-staff' ),
            'type'       => \Elementor\Controls_Manager::REPEATER,
            'fields'     => $remote_repeater->get_controls(),
            'default'    => [],
            'title_field' => '{{{ source_url }}}',
        ] );
        $this->end_controls_section();
    }

    // =========================================================================
    // SETTINGS MIGRATION
    // =========================================================================
    public function get_settings_for_display( $setting_key = null ) {
        $settings = parent::get_settings_for_display( $setting_key );
        if ( $setting_key !== null ) return $settings;

        $default_tab_labels = [
            'senior'         => 'סגל אקדמי בכיר | Senior Academic Staff',
            'administrative' => 'סגל מנהלי | Administrative Staff',
            'junior'         => 'סגל אקדמי זוטר | Junior Academic Staff',
            'emeritus'       => 'אמריטוס | Emeritus',
            'deceased'       => 'לזכרם | In Memoriam',
        ];

        if ( ! empty( $settings['default_tabs'] ) ) {
            // Inject emeritus tab if missing (migration)
            $has_emeritus = false;
            foreach ( $settings['default_tabs'] as $tab ) {
                if ( isset( $tab['tab_id'] ) && $tab['tab_id'] === 'emeritus' ) { $has_emeritus = true; break; }
            }
            if ( ! $has_emeritus ) {
                $new_tabs = [];
                foreach ( $settings['default_tabs'] as $tab ) {
                    if ( isset( $tab['tab_id'] ) && $tab['tab_id'] === 'deceased' ) {
                        $new_tabs[] = [ 'tab_id' => 'emeritus', 'tab_label' => $default_tab_labels['emeritus'], 'visible' => 'yes', 'featured_staff' => [], 'featured_staff_order' => '' ];
                    }
                    $new_tabs[] = $tab;
                }
                $settings['default_tabs'] = $new_tabs;
            }
            // Always sync tab_label from hardcoded map
            foreach ( $settings['default_tabs'] as &$tab ) {
                if ( ! empty( $tab['tab_id'] ) && isset( $default_tab_labels[ $tab['tab_id'] ] ) ) {
                    $tab['tab_label'] = $default_tab_labels[ $tab['tab_id'] ];
                }
            }
            unset( $tab );
        }

        return $settings;
    }

    // =========================================================================
    // RENDER
    // =========================================================================
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Detect language from URL
        $original_uri = $_SERVER['HAIFA_STAFF_ORIGINAL_URI'] ?? $_SERVER['REQUEST_URI'] ?? '';
        $lang = preg_match( '#(^|/)en/#', $original_uri ) ? 'en' : 'he';
        if ( ! empty( $settings['start_in_english'] ) && $settings['start_in_english'] === 'yes' ) $lang = 'en';

        // ── Manual mode ───────────────────────────────────────────────────────
        if ( $settings['display_mode'] === 'manual' ) {
            $manual_ids = ! empty( $settings['manual_staff'] ) ? array_map( 'intval', $settings['manual_staff'] ) : [];
            echo '<div class="haifa-staff-grid-widget">';
            if ( empty( $manual_ids ) ) {
                echo '<p class="no-staff-found">' . __( 'לא נבחרו חברי סגל.', 'haifa-staff' ) . '</p></div>';
                $this->render_styles( $settings );
                return;
            }
            if ( ! empty( $settings['manual_staff_order'] ) ) {
                $custom    = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $settings['manual_staff_order'] ) ), 'is_numeric' ) );
                $ordered   = array_values( array_intersect( $custom, $manual_ids ) );
                $remaining = array_diff( $manual_ids, $ordered );
                usort( $remaining, fn( $a, $b ) => strcmp( get_post_meta( $a, 'last_name', true ), get_post_meta( $b, 'last_name', true ) ) );
                $manual_ids = array_merge( $ordered, $remaining );
            } else {
                usort( $manual_ids, fn( $a, $b ) => strcmp( get_post_meta( $a, 'last_name', true ), get_post_meta( $b, 'last_name', true ) ) );
            }
            echo '<div class="staff-grid-container">';
            $remote_items  = $this->fetch_remote_staff_items( $settings['remote_staff_rows'] ?? [] );
            $unified_items = $this->build_unified_staff_items( $manual_ids, $remote_items );
            foreach ( $unified_items as $unified ) {
                if ( $unified['type'] === 'local' ) {
                    $post_obj = get_post( $unified['id'] );
                    if ( ! $post_obj || $post_obj->post_status !== 'publish' ) continue;
                    global $post; $post = $post_obj; setup_postdata( $post );
                    $this->render_staff_card( $settings, false, [], $lang );
                } else {
                    $this->render_remote_staff_card( $unified['item'], $settings, $lang );
                }
            }
            wp_reset_postdata();
            echo '</div></div>';
            $this->render_styles( $settings );
            return;
        }

        // ── Tabs mode ─────────────────────────────────────────────────────────

        // Legacy migration
        if ( empty( $settings['default_tabs'] ) && ( isset( $settings['tab_name_administrative'] ) || isset( $settings['featured_administrative'] ) ) ) {
            $settings['default_tabs'] = [
                [ 'tab_id' => 'senior',         'visible' => 'yes', 'featured_staff' => $settings['featured_senior']         ?? [] ],
                [ 'tab_id' => 'administrative', 'visible' => 'yes', 'featured_staff' => $settings['featured_administrative'] ?? [] ],
                [ 'tab_id' => 'junior',         'visible' => 'yes', 'featured_staff' => $settings['featured_junior']         ?? [] ],
                [ 'tab_id' => 'emeritus',       'visible' => 'yes', 'featured_staff' => [] ],
                [ 'tab_id' => 'deceased',       'visible' => 'yes', 'featured_staff' => [] ],
            ];
        }

        $args  = $this->build_query_args( $settings );
        $query = new \WP_Query( $args );
        $positions = $this->get_all_positions();

        // English: filter to only translated staff
        if ( $lang !== 'he' && $query->have_posts() ) {
            $query->posts = array_values( array_filter( $query->posts, function( $post ) {
                return ! empty( get_post_meta( $post->ID, 'first_name_en', true ) )
                    && ! empty( get_post_meta( $post->ID, 'last_name_en',  true ) );
            } ) );
            $query->post_count = count( $query->posts );
        }

        $available_types = $this->get_available_staff_types( $query );

        // Check for any English content
        $has_any_english = false;
        foreach ( $query->posts as $p ) {
            if ( ! empty( get_post_meta( $p->ID, 'first_name_en', true ) ) && ! empty( get_post_meta( $p->ID, 'last_name_en', true ) ) ) {
                $has_any_english = true; break;
            }
        }

        // Redirect English page with no translated staff back to Hebrew
        if ( $lang === 'en' && ! $has_any_english && ! is_admin() ) {
            $current_path = trim( parse_url( $original_uri, PHP_URL_PATH ), '/' );
            wp_redirect( site_url( '/' . preg_replace( '#^en/#', '', $current_path ) . '/' ), 302 );
            exit;
        }

        // Shorthand flags used throughout render
        $show_filters = ( $settings['show_filters'] ?? 'yes' ) !== '';
        $show_search  = ( $settings['show_search']  ?? 'yes' ) === 'yes';

        $visible_ids = array_map( fn( $p ) => $p->ID, $query->posts );

        ?>
        <?php
        // Build allowed tabs for flat-list mode (when tab buttons are hidden)
        $allowed_tabs = [];
        $tab_filter_map = [ 'senior' => ['filter'=>'academic_type','value'=>'senior'], 'junior' => ['filter'=>'academic_type','value'=>'junior'], 'administrative' => ['filter'=>'staff_type','value'=>'administrative'], 'emeritus' => ['filter'=>'status','value'=>'retired'], 'deceased' => ['filter'=>'status','value'=>'dead'] ];
        $is_custom_only = ! empty( $settings['custom_tabs_only'] ) && $settings['custom_tabs_only'] === 'yes';
        if ( ! $is_custom_only ) {
            foreach ( $settings['default_tabs'] ?? [] as $tab ) {
                if ( empty( $tab['visible'] ) || $tab['visible'] !== 'yes' || empty( $tab['tab_id'] ) ) continue;
                if ( isset( $tab_filter_map[ $tab['tab_id'] ] ) ) $allowed_tabs[] = $tab_filter_map[ $tab['tab_id'] ];
            }
        }
        foreach ( $settings['custom_tabs'] ?? [] as $ct ) {
            if ( isset( $ct['visible'] ) && $ct['visible'] !== 'yes' ) continue;
            if ( empty( $ct['taxonomy_term'] ) ) continue;
            $terms = is_array( $ct['taxonomy_term'] ) ? $ct['taxonomy_term'] : [ $ct['taxonomy_term'] ];
            $allowed_tabs[] = [ 'filter' => 'taxonomy', 'terms' => $terms ];
        }
        ?>
        <div class="haifa-staff-grid-widget"<?php echo ( ! $show_filters && empty( $allowed_tabs ) ) ? ' data-no-filters="1"' : ''; ?> data-allowed-tabs="<?php echo esc_attr( json_encode( $allowed_tabs ) ); ?>">

            <?php $this->render_tabs( $settings, $available_types, $lang, $visible_ids, $has_any_english, $show_filters, $show_search ); ?>

            <?php
            // Build featured map + ordered IDs
            $featured_map        = $this->get_featured_staff_mapping( $settings );
            $ordered_featured_ids = $this->build_ordered_featured_ids( $settings );

            // Collect all query IDs
            $all_staff_ids = [];
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) { $query->the_post(); $all_staff_ids[] = get_the_ID(); }
                wp_reset_postdata();
            }
            ?>

            <?php if ( ! empty( $featured_map ) ) : ?>
            <div class="staff-featured-section">
                <div class="staff-featured-container">
                    <?php
                    foreach ( $ordered_featured_ids as $staff_id ) {
                        if ( ! isset( $featured_map[ $staff_id ] ) ) continue;
                        global $post; $post = get_post( $staff_id ); setup_postdata( $post );
                        $this->render_staff_card( $settings, true, $featured_map[ $staff_id ], $lang );
                    }
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $all_staff_ids ) ) : ?>
            <div class="staff-grid-container">
                <?php
                $remote_items  = $this->fetch_remote_staff_items( $settings['remote_staff_rows'] ?? [] );
                $unified_items = $this->build_unified_staff_items( $all_staff_ids, $remote_items );
                foreach ( $unified_items as $unified ) {
                    if ( $unified['type'] === 'local' ) {
                        global $post; $post = get_post( $unified['id'] ); setup_postdata( $post );
                        $this->render_staff_card( $settings, false, [], $lang );
                    } else {
                        $this->render_remote_staff_card( $unified['item'], $settings, $lang );
                    }
                }
                wp_reset_postdata();
                ?>
            </div>
            <?php else : ?>
            <p class="no-staff-found"><?php _e( 'לא נמצאו חברי סגל.', 'haifa-staff' ); ?></p>
            <?php endif; ?>

            <?php if ( $query->max_num_pages > 1 ) : ?>
            <div class="staff-pagination">
                <?php echo paginate_links( [ 'total' => $query->max_num_pages, 'prev_text' => __( '« הקודם', 'haifa-staff' ), 'next_text' => __( 'הבא »', 'haifa-staff' ) ] ); ?>
            </div>
            <?php endif; ?>

        </div>

        <?php $this->render_styles( $settings, $lang ); ?>
        <?php $this->render_scripts( $settings, $show_filters ); ?>
        <?php
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function build_ordered_featured_ids( $settings ) {
        $ids = [];
        $tabs = array_merge( $settings['default_tabs'] ?? [], $settings['custom_tabs'] ?? [] );
        foreach ( $tabs as $tab ) {
            if ( empty( $tab['featured_staff'] ) || ! is_array( $tab['featured_staff'] ) ) continue;
            $list = array_map( 'intval', $tab['featured_staff'] );
            if ( ! empty( $tab['featured_staff_order'] ) && is_string( $tab['featured_staff_order'] ) ) {
                $custom_order = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $tab['featured_staff_order'] ) ), 'is_numeric' ) );
                $ordered  = array_values( array_filter( $custom_order, fn( $id ) => in_array( $id, $list ) ) );
                $remaining = array_diff( $list, $ordered );
                usort( $remaining, fn( $a, $b ) => strcmp( get_post_meta( $a, 'last_name', true ), get_post_meta( $b, 'last_name', true ) ) );
                $list = array_merge( $ordered, $remaining );
            } else {
                usort( $list, fn( $a, $b ) => strcmp( get_post_meta( $a, 'last_name', true ), get_post_meta( $b, 'last_name', true ) ) );
            }
            foreach ( $list as $id ) { if ( ! in_array( $id, $ids ) ) $ids[] = $id; }
        }
        return $ids;
    }

    private function build_query_args( $settings ) {
        $args = [ 'post_type' => 'staff', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [ 'relation' => 'AND' ] ];
        $order_by = $settings['order_by'] ?? 'last_name';
        $order    = $settings['order']    ?? 'ASC';
        if ( in_array( $order_by, [ 'last_name', 'first_name' ] ) ) { $args['meta_key'] = $order_by; $args['orderby'] = 'meta_value'; }
        else { $args['orderby'] = $order_by; }
        $args['order'] = $order;

        if ( ! empty( $settings['filter_staff_type'] ) )    $args['meta_query'][] = [ 'key' => 'staff_type',          'value' => $settings['filter_staff_type'],    'compare' => 'LIKE' ];
        if ( ! empty( $settings['filter_academic_type'] ) ) $args['meta_query'][] = [ 'key' => 'staff_academic_type', 'value' => $settings['filter_academic_type'], 'compare' => '=' ];
        if ( ! empty( $settings['filter_status'] ) )        $args['meta_query'][] = [ 'key' => 'status',              'value' => $settings['filter_status'],         'compare' => '=' ];

        if ( ! empty( $settings['filter_taxonomy'] ) && ( ( $settings['show_filters'] ?? 'yes' ) === 'no' || ( $settings['filter_layout'] ?? 'tabs' ) === 'dropdowns' ) ) {
            $args['tax_query'] = [ 'relation' => 'OR' ];
            $cat_terms = []; $tag_terms = [];
            foreach ( $settings['filter_taxonomy'] as $item ) {
                $parts = explode( ':', $item, 2 );
                if ( count( $parts ) === 2 ) {
                    if ( $parts[0] === 'category' ) $cat_terms[] = $parts[1];
                    elseif ( $parts[0] === 'tag'  ) $tag_terms[] = $parts[1];
                }
            }
            if ( ! empty( $cat_terms ) ) $args['tax_query'][] = [ 'taxonomy' => 'category', 'field' => 'slug', 'terms' => $cat_terms, 'operator' => 'IN' ];
            if ( ! empty( $tag_terms  ) ) $args['tax_query'][] = [ 'taxonomy' => 'post_tag',  'field' => 'slug', 'terms' => $tag_terms,  'operator' => 'IN' ];
        }

        return $args;
    }

    // =========================================================================
    // RENDER TABS
    // $show_filters — whether to render the tab buttons
    // $show_search  — whether to render the search input
    // If both false → render nothing at all
    // =========================================================================
    private function render_tabs( $settings, $available_types, $lang, $visible_ids, $has_any_english, $show_filters, $show_search ) {
        $has_lang_switcher = ! empty( $settings['show_lang_switcher'] ) && $settings['show_lang_switcher'] === 'yes' && $has_any_english;
        if ( ! $show_filters && ! $show_search && ! $has_lang_switcher ) return;
        ?>
        <div class="staff-filters-tabs">

            <?php if ( ! empty( $settings['show_lang_switcher'] ) && $settings['show_lang_switcher'] === 'yes' && $has_any_english ) :
                $current_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
                $clean_path   = preg_replace( '#^en/#', '', $current_path );
            ?>
            <div class="staff-lang-switcher">
                <a href="<?php echo esc_url( site_url( '/' . $clean_path . '/' ) ); ?>" class="staff-lang-btn<?php echo $lang === 'he' ? ' active' : ''; ?>">עברית</a>
                <a href="<?php echo esc_url( site_url( '/en/' . $clean_path . '/' ) ); ?>" class="staff-lang-btn<?php echo $lang === 'en' ? ' active' : ''; ?>">English</a>
            </div>
            <?php endif; ?>

            <div class="staff-grid-top-row">

                <?php if ( $show_filters ) : ?>
                <div class="staff-tabs-container">
                    <?php
                    $first_tab_rendered  = false;
                    $default_tabs        = $settings['default_tabs']  ?? [];
                    $custom_tabs         = $settings['custom_tabs']   ?? [];
                    $is_custom_tabs_only = ! empty( $settings['custom_tabs_only'] ) && $settings['custom_tabs_only'] === 'yes';

                    $all_tabs = [];
                    if ( ! $is_custom_tabs_only ) {
                        $pos = 0;
                        foreach ( $default_tabs as $tab ) {
                            if ( empty( $tab['visible'] ) || $tab['visible'] !== 'yes' || empty( $tab['tab_id'] ) ) continue;
                            $tab_id = $tab['tab_id'];
                            foreach ( $custom_tabs as $ct ) {
                                if ( isset( $ct['visible'] ) && $ct['visible'] !== 'yes' ) continue;
                                $ib = $ct['insert_before'] ?? 'end';
                                if ( $ib === $tab_id ) $all_tabs[] = [ 'type' => 'custom', 'data' => $ct, 'position' => $pos - 0.5 ];
                            }
                            $all_tabs[] = [ 'type' => 'default', 'tab_id' => $tab_id, 'data' => $tab, 'position' => $pos ];
                            $pos++;
                        }
                    }
                    foreach ( $custom_tabs as $ct ) {
                        if ( isset( $ct['visible'] ) && $ct['visible'] !== 'yes' ) continue;
                        $ib = $ct['insert_before'] ?? 'end';
                        if ( $ib === 'end' || $ib === '' || $is_custom_tabs_only ) $all_tabs[] = [ 'type' => 'custom', 'data' => $ct, 'position' => 999 ];
                    }

                    $default_tab_labels = [
                        'senior'         => [ 'he' => 'סגל אקדמי בכיר', 'en' => 'Senior Academic Staff' ],
                        'administrative' => [ 'he' => 'סגל מנהלי',       'en' => 'Administrative Staff'  ],
                        'junior'         => [ 'he' => 'סגל אקדמי זוטר',  'en' => 'Junior Academic Staff' ],
                        'emeritus'       => [ 'he' => 'אמריטוס',          'en' => 'Emeritus'              ],
                        'deceased'       => [ 'he' => 'לזכרם',            'en' => 'In Memoriam'           ],
                    ];

                    foreach ( $all_tabs as $tab_info ) :
                        if ( $tab_info['type'] === 'default' ) :
                            $tab_id   = $tab_info['tab_id'];
                            $tab_name = $default_tab_labels[ $tab_id ][ $lang ] ?? $default_tab_labels[ $tab_id ]['he'] ?? '';

                            if      ( $tab_id === 'administrative' && $available_types['has_administrative'] ) : $df = 'staff_type';   $dv = 'administrative';
                            elseif  ( $tab_id === 'senior'         && $available_types['has_senior']         ) : $df = 'academic_type'; $dv = 'senior';
                            elseif  ( $tab_id === 'junior'         && $available_types['has_junior']         ) : $df = 'academic_type'; $dv = 'junior';
                            elseif  ( $tab_id === 'emeritus'       && $available_types['has_emeritus']       ) : $df = 'status';        $dv = 'retired';
                            elseif  ( $tab_id === 'deceased'       && $available_types['has_deceased']       ) : $df = 'status';        $dv = 'dead';
                            else    : continue;
                            endif;
                            ?>
                           <?php
                            $extra_terms_attr = '';
                            $tab_has_match = true;
                            $tab_term_filter = $tab_info['data']['tab_term_filter'] ?? [];
                            if ( ! empty( $tab_term_filter ) && is_array( $tab_term_filter ) ) {
                                $extra_terms_attr = esc_attr( json_encode( $tab_term_filter ) );
                                $tab_has_match = false;
                                foreach ( $visible_ids as $vid ) {
                                    // Must also match this tab's base status/type filter ($df/$dv), not just the extra term
                                    $base_match = false;
                                    if ( $df === 'status' )        $base_match = ( get_post_meta( $vid, 'status', true ) === $dv );
                                    elseif ( $df === 'academic_type' ) $base_match = ( get_post_meta( $vid, 'staff_academic_type', true ) === $dv );
                                    elseif ( $df === 'staff_type' ) { $st = get_post_meta( $vid, 'staff_type', true ); $base_match = is_array( $st ) && in_array( $dv, $st ); }
                                    if ( ! $base_match ) continue;

                                    $cs = array_map( fn($s) => 'category:' . $s, wp_get_post_terms( $vid, 'category', [ 'fields' => 'slugs' ] ) );
                                    $ts = array_map( fn($s) => 'tag:' . $s,      wp_get_post_terms( $vid, 'post_tag',  [ 'fields' => 'slugs' ] ) );
                                    if ( ! empty( array_intersect( $tab_term_filter, array_merge( $cs, $ts ) ) ) ) { $tab_has_match = true; break; }
                                }
                                if ( ! $tab_has_match ) continue;
                            }
                            ?>
                            <button class="staff-tab<?php echo ! $first_tab_rendered ? ' active' : ''; ?>" data-filter="<?php echo $df; ?>" data-value="<?php echo $dv; ?>"<?php echo $extra_terms_attr ? ' data-extra-terms="' . $extra_terms_attr . '"' : ''; ?>><?php echo esc_html( $tab_name ); ?></button>
                            <?php $first_tab_rendered = true;

                        elseif ( $tab_info['type'] === 'custom' ) :
                            $ct = $tab_info['data'];
                            if ( empty( $ct['taxonomy_term'] ) ) continue;
                            $terms = is_array( $ct['taxonomy_term'] ) ? $ct['taxonomy_term'] : [ $ct['taxonomy_term'] ];
                            $all_terms = [];
                            foreach ( $terms as $term ) { $parts = explode( ':', $term, 2 ); if ( count( $parts ) === 2 ) $all_terms[] = $term; }
                            if ( empty( $all_terms ) ) continue;
                            if ( $lang !== 'he' && ! empty( $visible_ids ) ) {
                                $has_match = false;
                                foreach ( $visible_ids as $vid ) {
                                    $cs = array_map( fn($s) => 'category:' . $s, wp_get_post_terms( $vid, 'category', [ 'fields' => 'slugs' ] ) );
                                    $ts = array_map( fn($s) => 'tag:' . $s,      wp_get_post_terms( $vid, 'post_tag',  [ 'fields' => 'slugs' ] ) );
                                    if ( ! empty( array_intersect( $all_terms, array_merge( $cs, $ts ) ) ) ) { $has_match = true; break; }
                                }
                                if ( ! $has_match ) continue;
                            }
                            $ct_name = ( $lang === 'en' && ! empty( $ct['tab_name_en'] ) ) ? $ct['tab_name_en'] : ( $ct['tab_name'] ?: __( 'לשונית מותאמת', 'haifa-staff' ) );
                            ?>
                            <button class="staff-tab<?php echo ! $first_tab_rendered ? ' active' : ''; ?>"
                                    data-filter="taxonomy"
                                    data-value="<?php echo esc_attr( implode( '|', $all_terms ) ); ?>"
                                    data-taxonomy-terms="<?php echo esc_attr( json_encode( $all_terms ) ); ?>">
                                <?php echo esc_html( $ct_name ); ?>
                            </button>
                            <?php $first_tab_rendered = true;
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php endif; // $show_filters ?>

                <div class="staff-tab-search"<?php echo ! $show_search ? ' style="display:none;"' : ''; ?>>
                    <input type="text" class="staff-search-input" placeholder="<?php echo $lang === 'en' ? 'Search...' : 'חיפוש...'; ?>" />
                    <button class="staff-search-clear" title="<?php esc_attr_e( 'נקה', 'haifa-staff' ); ?>">×</button>
                </div>

            </div><!-- /.staff-grid-top-row -->
        </div><!-- /.staff-filters-tabs -->
        <?php
    }

    private function render_dropdowns( $settings, $positions, $lang = 'he' ) {
        ?>
        <div class="staff-filters-bar">
            <?php if ( ( $settings['show_search'] ?? 'yes' ) === 'yes' ) : ?>
            <input type="text" class="staff-search-input" placeholder="<?php echo $lang === 'en' ? 'Search...' : 'חיפוש...'; ?>" />
            <?php endif; ?>
            <?php if ( ( $settings['filter_by_staff_type'] ?? 'yes' ) === 'yes' ) : ?>
            <select class="staff-filter-select" data-filter="staff_type">
                <option value=""><?php _e( 'כל סוגי הסגל', 'haifa-staff' ); ?></option>
                <option value="academic"><?php _e( 'אקדמי', 'haifa-staff' ); ?></option>
                <option value="administrative"><?php _e( 'מנהלי', 'haifa-staff' ); ?></option>
            </select>
            <?php endif; ?>
            <?php if ( ( $settings['filter_by_position'] ?? 'yes' ) === 'yes' && ! empty( $positions ) ) : ?>
            <select class="staff-filter-select" data-filter="position">
                <option value=""><?php _e( 'כל התפקידים', 'haifa-staff' ); ?></option>
                <?php foreach ( $positions as $p ) : ?><option value="<?php echo esc_attr( $p ); ?>"><?php echo esc_html( $p ); ?></option><?php endforeach; ?>
            </select>
            <?php endif; ?>
            <button class="staff-reset-filters"><?php _e( 'איפוס', 'haifa-staff' ); ?></button>
        </div>
        <?php
    }

    private function get_featured_staff_mapping( $settings ) {
        $map = [];
        $tab_to_filter = [ 'administrative' => 'staff_type:administrative', 'junior' => 'academic_type:junior', 'senior' => 'academic_type:senior', 'emeritus' => 'status:retired', 'deceased' => 'status:dead' ];
        foreach ( $settings['default_tabs'] ?? [] as $tab ) {
            if ( empty( $tab['featured_staff'] ) || ! is_array( $tab['featured_staff'] ) ) continue;
            $fi = $tab_to_filter[ $tab['tab_id'] ] ?? '';
            if ( ! $fi ) continue;
            foreach ( array_map( 'intval', $tab['featured_staff'] ) as $sid ) {
                if ( $sid ) { $map[ $sid ][] = $fi; }
            }
        }
        foreach ( $settings['custom_tabs'] ?? [] as $ct ) {
            if ( empty( $ct['featured_staff'] ) || ! is_array( $ct['featured_staff'] ) || empty( $ct['taxonomy_term'] ) ) continue;
            $terms = is_array( $ct['taxonomy_term'] ) ? $ct['taxonomy_term'] : [ $ct['taxonomy_term'] ];
            $fi    = 'taxonomy:' . implode( '|', $terms );
            foreach ( array_map( 'intval', $ct['featured_staff'] ) as $sid ) {
                if ( $sid ) { $map[ $sid ][] = $fi; }
            }
        }
        return $map;
    }

    private function get_available_staff_types( $query ) {
        $types = [ 'has_academic' => false, 'has_administrative' => false, 'has_senior' => false, 'has_junior' => false, 'has_emeritus' => false, 'has_deceased' => false ];
        if ( ! $query->have_posts() ) return $types;
        foreach ( $query->posts as $p ) {
            $st = get_post_meta( $p->ID, 'staff_type',          true );
            $at = get_post_meta( $p->ID, 'staff_academic_type', true );
            $ss = get_post_meta( $p->ID, 'status',              true );
            if ( $ss === 'retired' ) $types['has_emeritus']  = true;
            if ( $ss === 'dead'    ) $types['has_deceased']  = true;
            if ( is_array( $st ) ) {
                if ( in_array( 'academic',       $st ) ) { $types['has_academic'] = true; if ( $at === 'senior' ) $types['has_senior'] = true; if ( $at === 'junior' ) $types['has_junior'] = true; }
                if ( in_array( 'administrative', $st ) ) $types['has_administrative'] = true;
            }
        }
        return $types;
    }

    private function get_all_positions() {
        global $wpdb;
        return $wpdb->get_col( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID=pm.post_id WHERE pm.meta_key='position' AND pm.meta_value!='' AND p.post_type='staff' AND p.post_status='publish' ORDER BY meta_value ASC" );
    }

    private function get_contextual_staff_permalink( $post_id, $lang = 'he' ) {
        $staff_post = get_post( $post_id );
        if ( ! $staff_post ) return get_permalink( $post_id );
        $staff_slug = $staff_post->post_name;

        // Determine the current page path from the request URI instead of relying on
        // is_page()/is_singular()/get_queried_object_id(), which can be unreliable
        // depending on how/where the widget is rendered (e.g. inside Elementor templates).
        $request_path = trim( parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
        $path_parts   = $request_path !== '' ? explode( '/', $request_path ) : [];

        // Strip a leading language prefix (en/ar) if present, we re-add it below.
        if ( ! empty( $path_parts ) && in_array( $path_parts[0], [ 'en', 'ar' ], true ) ) {
            array_shift( $path_parts );
        }

        if ( empty( $path_parts ) ) {
            return get_permalink( $post_id );
        }

        $referrer_path = implode( '/', $path_parts ); // the page the grid widget is actually on (before adding /staff/)

        // If the current page path doesn't already end in "staff", append it.
        if ( end( $path_parts ) !== 'staff' ) {
            $path_parts[] = 'staff';
        }

        $prefix = $lang !== 'he' ? $lang . '/' : '';
        return site_url( '/' . $prefix . implode( '/', $path_parts ) . '/' . $staff_slug . '/' ) . '?from=' . urlencode( $referrer_path );
    }

    private function is_university_network() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $fwd = trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] );
            if ( filter_var( $fwd, FILTER_VALIDATE_IP ) ) $ip = $fwd;
        }
        return $this->ip_in_range( $ip, '132.74.0.0/16' );
    }

    private function ip_in_range( $ip, $range ) {
        if ( strpos( $range, '/' ) === false ) return $ip === $range;
        [ $subnet, $mask ] = explode( '/', $range );
        $ip_long     = ip2long( $ip );
        $subnet_long = ip2long( $subnet );
        if ( $ip_long === false || $subnet_long === false ) return false;
        $mask_long    = -1 << ( 32 - (int) $mask );
        $subnet_long &= $mask_long;
        return ( $ip_long & $mask_long ) === $subnet_long;
    }

    private function get_title_label( $val ) {
        if ( function_exists( 'haifa_v2_title_label' ) ) return haifa_v2_title_label( $val );
        return [ 'mr' => 'מר', 'mrs' => "גב'", 'dr' => 'ד"ר', 'prof' => "פרופ'", 'other' => 'אחר' ][ $val ] ?? $val;
    }

    // =========================================================================
    // STAFF CARD
    // =========================================================================
    private function render_staff_card( $settings, $is_featured = false, $featured_in_tabs = [], $lang = 'he' ) {
        $_pid = get_the_ID();

        if ( $lang !== 'he' ) {
            if ( empty( get_post_meta( $_pid, 'first_name_en', true ) ) || empty( get_post_meta( $_pid, 'last_name_en', true ) ) ) return;
            $en_fields    = [ 'about_en', 'publications_en', 'education_en', 'academic_background_en', 'reserach_areas_en', 'prizes_en', 'media_en', 'additional_info_en' ];
            $has_en_content = false;
            foreach ( $en_fields as $f ) { if ( ! empty( get_post_meta( $_pid, $f, true ) ) ) { $has_en_content = true; break; } }
        }

        $first_name  = $lang !== 'he' ? ( get_post_meta( $_pid, 'first_name_en', true ) ?: get_post_meta( $_pid, 'first_name', true ) ) : get_post_meta( $_pid, 'first_name', true );
        $last_name   = $lang !== 'he' ? ( get_post_meta( $_pid, 'last_name_en',  true ) ?: get_post_meta( $_pid, 'last_name',  true ) ) : get_post_meta( $_pid, 'last_name',  true );
        $title_main  = get_post_meta( $_pid, 'title_main', true );
        $title_other = $lang !== 'he' ? ( get_post_meta( $_pid, 'title_other_en', true ) ?: get_post_meta( $_pid, 'title_other', true ) ) : get_post_meta( $_pid, 'title_other', true );
        $position    = $lang !== 'he' ? get_post_meta( $_pid, 'position_en', true ) : get_post_meta( $_pid, 'position', true );

        // Context position override
        $ctx_key = trim( $settings['position_context_key'] ?? '' );
        if ( $ctx_key !== '' ) {
            $cp_count = (int) get_post_meta( $_pid, 'context_positions', true );
            for ( $i = 0; $i < $cp_count; $i++ ) {
                if ( get_post_meta( $_pid, "context_positions_{$i}_key", true ) === $ctx_key ) {
                    $cpv = get_post_meta( $_pid, "context_positions_{$i}_value", true );
                    if ( $cpv !== '' ) { $position = $cpv; break; }
                }
            }
        }

        $email          = get_post_meta( $_pid, 'email',          true );
        $external_phone = get_post_meta( $_pid, 'external_phone', true );
        $inner_phone    = get_post_meta( $_pid, 'inner_phone',    true );
        $office_room    = $lang !== 'he' ? get_post_meta( $_pid, 'office_room_en', true ) : get_post_meta( $_pid, 'office_room', true );
        $staff_image    = get_post_meta( $_pid, 'staff_image',    true );
        $staff_type     = get_post_meta( $_pid, 'staff_type',     true );
        $academic_type  = get_post_meta( $_pid, 'staff_academic_type', true );
        $status         = get_post_meta( $_pid, 'status',         true );

        $title_label = '';
        if ( $title_main === 'other' && ! empty( $title_other ) ) {
            $title_label = $title_other;
        } elseif ( $title_main ) {
            $en_labels   = [ 'mr' => 'Mr.', 'mrs' => 'Ms.', 'dr' => 'Dr.', 'prof' => 'Prof.', 'other' => '' ];
            $title_label = $lang === 'en' ? ( $en_labels[ $title_main ] ?? $title_main ) : $this->get_title_label( $title_main );
        }

        $full_name = trim( $first_name . ' ' . $last_name );
        if ( $status === 'dead' && $lang !== 'en' ) $full_name .= ' ז"ל';
        $display_name    = $title_label ? $title_label . ' ' . $full_name : $full_name;
        $searchable_name = strtolower( $display_name );

        $staff_type_attr       = is_array( $staff_type ) ? implode( ' ', $staff_type ) : '';
        $is_administrative_only = is_array( $staff_type ) && in_array( 'administrative', $staff_type ) && ! in_array( 'academic', $staff_type );

        $image_url = '';
        if ( is_array( $staff_image ) && isset( $staff_image['url'] ) ) $image_url = $staff_image['url'];
        elseif ( is_numeric( $staff_image ) ) $image_url = wp_get_attachment_image_url( $staff_image, 'full' );
        if ( empty( $image_url ) ) $image_url = HAIFA_STAFF_V2_PLUGIN_URL . 'assets/default-avatar.jpg';

        $category_slugs = implode( ' ', wp_get_post_terms( $_pid, 'category', [ 'fields' => 'slugs' ] ) ?: [] );
        $tag_slugs      = implode( ' ', wp_get_post_terms( $_pid, 'post_tag',  [ 'fields' => 'slugs' ] ) ?: [] );

        $he_fields = [ 'about', 'publications', 'education', 'academic_background', 'reserach_areas', 'media', 'prizes', 'additional_info' ];
        $has_content = false;
        foreach ( $he_fields as $f ) { if ( get_post_meta( $_pid, $f, true ) ) { $has_content = true; break; } }

        $has_profile = $lang === 'he' ? $has_content : ( $has_en_content ?? false );
        $show_link   = ! $is_administrative_only && $has_profile;
        ?>
        <article class="staff-grid-card<?php echo $is_featured ? ' staff-featured-card' : ''; ?>"
                 data-staff-type="<?php echo esc_attr( $staff_type_attr ); ?>"
                 data-academic-type="<?php echo esc_attr( $academic_type ); ?>"
                 data-position="<?php echo esc_attr( $position ); ?>"
                 data-status="<?php echo esc_attr( $status ); ?>"
                 data-category="<?php echo esc_attr( $category_slugs ); ?>"
                 data-tag="<?php echo esc_attr( $tag_slugs ); ?>"
                 data-title="<?php echo esc_attr( strtolower( $title_label ) ); ?>"
                 data-name="<?php echo esc_attr( $searchable_name ); ?>"
                 data-is-featured="<?php echo $is_featured ? '1' : '0'; ?>"
                 data-featured-tabs="<?php echo esc_attr( implode( ',', $featured_in_tabs ) ); ?>">

            <?php if ( $settings['show_image'] === 'yes' ) : ?>
            <?php if ( $show_link ) : ?>
            <a href="<?php echo esc_url( $this->get_contextual_staff_permalink( $_pid, $lang ) ); ?>" class="card-image-wrapper">
            <?php else : ?>
            <div class="card-image-wrapper card-image-no-link">
            <?php endif; ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="card-image">
            <?php echo $show_link ? '</a>' : '</div>'; ?>
            <?php endif; ?>

            <div class="card-content">
                <h3 class="card-name"><?php echo esc_html( $display_name ); ?></h3>
                <?php if ( $settings['show_position'] === 'yes' && $position ) : ?>
                <div class="card-position"><?php echo esc_html( $position ); ?></div>
                <?php endif; ?>
                <div class="card-contact">
                    <?php if ( $settings['show_email'] === 'yes' && $email ) : ?>
                    <div class="contact-line"><span class="contact-label"><?php echo $lang === 'en' ? 'Email:' : 'דוא"ל:'; ?></span> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div>
                    <?php endif; ?>
                    <?php if ( $settings['show_phone'] === 'yes' && $external_phone ) : ?>
                    <div class="contact-line"><span class="contact-label"><?php echo $lang === 'en' ? 'Phone:' : 'טלפון:'; ?></span> <?php echo esc_html( $external_phone ); ?></div>
                    <?php endif; ?>
                    <?php if ( $this->is_university_network() && $settings['show_office'] === 'yes' && $inner_phone ) : ?>
                    <div class="contact-line"><span class="contact-label"><?php echo $lang === 'en' ? 'Ext:' : 'טלפון פנימי:'; ?></span> <?php echo esc_html( $inner_phone ); ?></div>
                    <?php endif; ?>
                    <?php if ( $office_room ) : ?>
                    <div class="contact-line"><span class="contact-label"><?php echo $lang === 'en' ? 'Office:' : 'משרד:'; ?></span> <?php echo esc_html( $office_room ); ?></div>
                    <?php endif; ?>
                </div>
                <?php if ( $show_link ) : ?>
                <a href="<?php echo esc_url( $this->get_contextual_staff_permalink( $_pid, $lang ) ); ?>" class="card-button">
                    <?php echo esc_html( $lang === 'en' && ! empty( $settings['button_text_en'] ) ? $settings['button_text_en'] : $settings['button_text'] ); ?>
                    <span class="button-icon" dir="ltr"><?php echo $lang === 'en' ? '»' : '«'; ?></span>
                </a>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

    // =========================================================================
    // REMOTE STAFF
    // =========================================================================
    private function fetch_remote_staff_items( array $rows ): array {
        $all_items = [];
        foreach ( $rows as $row ) {
            $source_url = trailingslashit( esc_url_raw( $row['source_url'] ?? '' ) );
            if ( ! $source_url || $source_url === '/' ) continue;
            $cache_hours = max( 1, intval( $row['cache_hours'] ?? 6 ) );

            if ( ! empty( $row['filter_slugs'] ) ) {
                $slugs = array_filter( array_map( 'trim', explode( ',', urldecode( $row['filter_slugs'] ) ) ) );
                foreach ( $slugs as $slug ) {
                    $endpoint  = $source_url . 'wp-json/haifa-staff/v1/staff/' . urlencode( $slug );
                    $cache_key = 'hstaff_r_' . md5( $endpoint );
                    $cached    = get_transient( $cache_key );
                    if ( $cached !== false ) { if ( ! empty( $cached ) ) $all_items[] = $cached; continue; }
                    $response = wp_remote_get( $endpoint, [ 'timeout' => 10 ] );
                    if ( is_wp_error( $response ) ) continue;
                    $body = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( ! empty( $body ) && ! isset( $body['error'] ) ) { set_transient( $cache_key, $body, $cache_hours * HOUR_IN_SECONDS ); $all_items[] = $body; }
                }
                continue;
            }

            $endpoint = $source_url . 'wp-json/haifa-staff/v1/staff';
            $params   = [];
            if ( ! empty( $row['filter_staff_type'] ) && ( $row['fetch_mode'] ?? '' ) === 'by_staff_type' ) $params['staff_type'] = $row['filter_staff_type'];
            if ( ! empty( $row['filter_tag'] )         && ( $row['fetch_mode'] ?? '' ) === 'by_tag'        ) $params['tag']        = $row['filter_tag'];
            if ( ! empty( $params ) ) $endpoint .= '?' . http_build_query( $params );

            $cache_key = 'hstaff_r_' . md5( $endpoint );
            $cached    = get_transient( $cache_key );
            if ( $cached !== false ) { $all_items = array_merge( $all_items, $cached ); continue; }
            $response = wp_remote_get( $endpoint, [ 'timeout' => 15 ] );
            if ( is_wp_error( $response ) ) continue;
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( is_array( $body ) ) { set_transient( $cache_key, $body, $cache_hours * HOUR_IN_SECONDS ); $all_items = array_merge( $all_items, $body ); }
        }
        return $all_items;
    }

    private function build_unified_staff_items( array $local_ids, array $remote_items ): array {
        $items = [];
        foreach ( $local_ids   as $id )   $items[] = [ 'type' => 'local',  'id'   => $id,   'last_name' => get_post_meta( $id, 'last_name', true ) ];
        foreach ( $remote_items as $item ) $items[] = [ 'type' => 'remote', 'item' => $item, 'last_name' => $item['last_name'] ?? '' ];
        usort( $items, fn( $a, $b ) => strcmp( $a['last_name'], $b['last_name'] ) );
        return $items;
    }

    private function render_remote_staff_card( array $item, array $settings, string $lang = 'he' ): void {
        $first_name    = $item['first_name']           ?? '';
        $last_name     = $item['last_name']            ?? '';
        $title_label   = $item['title_main_label']     ?? '';
        $position      = $item['position']             ?? '';
        $email         = $item['email']                ?? '';
        $status        = $item['status']               ?? '';
        $staff_type    = $item['staff_type']           ?? [];
        $academic_type = $item['staff_academic_type']  ?? '';
        $profile_url   = $item['profile_url']          ?? '';
        $image_url     = $item['photo_url']            ?? HAIFA_STAFF_V2_PLUGIN_URL . 'assets/default-avatar.jpg';
        $tags          = $item['tags']                 ?? [];

        $full_name = trim( $first_name . ' ' . $last_name );
        if ( $status === 'dead' && $lang !== 'en' ) $full_name .= ' ז"ל';
        $display_name           = $title_label ? $title_label . ' ' . $full_name : $full_name;
        $staff_type_attr        = is_array( $staff_type ) ? implode( ' ', $staff_type ) : '';
        $is_administrative_only = is_array( $staff_type ) && $staff_type === [ 'administrative' ];
        $tag_slugs              = implode( ' ', array_column( $tags, 'slug' ) );
        ?>
        <article class="staff-grid-card staff-remote-card"
                 data-staff-type="<?php echo esc_attr( $staff_type_attr ); ?>"
                 data-academic-type="<?php echo esc_attr( $academic_type ); ?>"
                 data-position="<?php echo esc_attr( $position ); ?>"
                 data-status="<?php echo esc_attr( $status ); ?>"
                 data-tag="<?php echo esc_attr( $tag_slugs ); ?>"
                 data-title="<?php echo esc_attr( strtolower( $title_label ) ); ?>"
                 data-name="<?php echo esc_attr( strtolower( $display_name ) ); ?>"
                 data-is-featured="0" data-featured-tabs="">
            <?php if ( $settings['show_image'] === 'yes' ) : ?>
            <?php if ( ! $is_administrative_only && $profile_url ) : ?>
            <a href="<?php echo esc_url( $profile_url ); ?>" class="card-image-wrapper">
            <?php else : ?>
            <div class="card-image-wrapper card-image-no-link">
            <?php endif; ?>
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="card-image">
            <?php echo ( ! $is_administrative_only && $profile_url ) ? '</a>' : '</div>'; ?>
            <?php endif; ?>
            <div class="card-content">
                <h3 class="card-name"><?php echo esc_html( $display_name ); ?></h3>
                <?php if ( $settings['show_position'] === 'yes' && $position ) : ?><div class="card-position"><?php echo esc_html( $position ); ?></div><?php endif; ?>
                <div class="card-contact">
                    <?php if ( $settings['show_email'] === 'yes' && $email ) : ?><div class="contact-line"><span class="contact-label">דוא"ל:</span> <a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></div><?php endif; ?>
                </div>
                <?php if ( ! $is_administrative_only && $profile_url ) : ?>
                <a href="<?php echo esc_url( $profile_url ); ?>" class="card-button"><?php echo esc_html( $settings['button_text'] ); ?> <span class="button-icon" dir="ltr">«</span></a>
                <?php endif; ?>
            </div>
        </article>
        <?php
    }

    // =========================================================================
    // STYLES
    // =========================================================================
    private function render_styles( $settings, $lang = 'he' ) { ?>
        <style>
        .haifa-staff-grid-widget { direction:rtl; text-align:right; position:relative; z-index:0; }
        <?php if ( $lang === 'en' ) : ?>.haifa-staff-grid-widget { direction:ltr; text-align:left; }<?php endif; ?>
        .staff-filters-bar { display:flex; gap:15px; margin-bottom:30px; flex-wrap:wrap; align-items:center; }
        .staff-search-input,.staff-filter-select { padding:10px 15px; border:1px solid #e0e0e0; border-radius:4px; font-size:14px; min-width:300px; }
        .staff-reset-filters { padding:10px 20px; background:#f5f5f5; border:1px solid #e0e0e0; border-radius:4px; cursor:pointer; font-size:14px; }
        .staff-reset-filters:hover { background:#e0e0e0; }
        .staff-filters-tabs { margin-bottom:30px; position:sticky; top:var(--header-height,0px); z-index:100; background:#fff; padding-top:10px; padding-bottom:10px; box-shadow:0 15px 12px -14px rgba(0,0,0,.15); }
        .staff-lang-switcher { display:flex; gap:0; align-items:center; justify-content:flex-end; margin-bottom:8px; }
        .staff-lang-btn { padding:4px 8px; text-decoration:none; font-size:16px; font-weight:600; color:#37495a; background:none; border:none; transition:color .2s; }
        .staff-lang-btn:hover,.staff-lang-btn.active { color:#00b8d4; }
        .staff-lang-switcher .staff-lang-btn:not(:last-child)::after { content:'|'; margin-inline-start:8px; color:#ccc; font-weight:300; }
        .staff-grid-top-row { display:flex; align-items:center; gap:16px; }
        .staff-tabs-container { display:flex; gap:0; flex:1; overflow:hidden; justify-content:flex-start; }
        .staff-tab { flex:0 0 auto; padding:10px 18px; background:transparent; color:#37495a; border:none; border-bottom:3px solid transparent; cursor:pointer; font-size:20px; font-weight:600; transition:color .2s,border-color .2s; }
        .staff-tab:hover,.staff-tab:focus { color:#00b8d4; background:none; }
        .staff-tab.active { color:#00b8d4; border-bottom-color:#00b8d4; }
        .staff-tab-search { display:flex; align-items:center; flex-shrink:0; position:relative; margin-inline-start:auto; }
        .staff-tab-search .staff-search-input { width:220px; padding-inline-end:35px; }
        .staff-search-clear { position:absolute; inset-inline-end:10px; top:50%; transform:translateY(-50%); background:none; border:none; font-size:18px; color:#999; cursor:pointer; padding:5px; display:none; }
        .staff-search-clear.visible { display:block; }
        .staff-search-clear:hover { color:#333; }
        .staff-featured-section { margin-bottom:40px; }
        .staff-featured-container { display:flex; gap:1em; justify-content:center; padding-bottom:10px; flex-wrap:wrap; }
        .staff-grid-container { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:30px; margin-bottom:40px; }
        .staff-grid-card { align-items:center; transition:transform .3s,box-shadow .3s; display:flex; flex-direction:column; }
        .staff-grid-card.hidden { display:none !important; }
        .card-image-wrapper { width:100%; overflow:hidden; background:#f5f5f5; cursor:pointer; transition:opacity .3s; display:block; text-decoration:none; }
        .card-image-wrapper:hover { opacity:.9; }
        .card-image-wrapper.card-image-no-link { cursor:default; }
        .card-image-wrapper.card-image-no-link:hover { opacity:1; }
        .card-image { width:100%; height:100%; object-fit:cover; display:block; }
        .card-content { padding:1em; text-align:start; display:flex; flex-direction:column; flex:1; z-index:10; margin-top:-10%; width:80%; box-shadow:-5px 5px 0 0 #A1F8FD; background:#fff; line-height:normal}
        [dir="ltr"] .card-content { box-shadow:5px 5px 0 0 #A1F8FD; }
        .card-name { font-size:22px; font-weight:700 !important; margin:0 0 10px; }
        .card-position { font-size:16px; margin-bottom:20px; font-weight:700; height:1em}
        .card-contact { margin:20px 0; text-align:start; flex:1; }
        .contact-line { margin:8px 0; font-size:17px; }
        .contact-label { font-weight:600; margin-left:6px; }
        .contact-line a { color:#0066cc; text-decoration:none; }
        .card-button { display:inline-flex; align-items:center; background:#08BBE7 !important; color:#fff !important; gap:8px; text-decoration:none !important; padding:10px 25px; border-radius:4px; font-size:16px; font-weight:600; transition:background .3s; margin-top:15px; justify-content:center; }
        .card-button:hover { background:#066f8a !important; color:#fff !important; }
        .no-staff-found { text-align:center; padding:60px 20px; background:#f9f9f9; border-radius:8px; grid-column:1/-1; }
        .staff-pagination { text-align:center; margin-top:40px; }
        .staff-pagination .page-numbers { display:inline-block; padding:10px 16px; background:#f5f5f5; color:#333; text-decoration:none; border-radius:4px; margin:0 5px; }
        .staff-pagination .current { background:#00b8d4; color:#fff; }
        @media(max-width:1024px){
            .staff-grid-top-row{flex-wrap:wrap;}
            .staff-tab-search{width:100%;margin-inline-start:0;margin-top:8px;}
            .staff-tab-search .staff-search-input{width:100%;}
            .staff-grid-container{grid-template-columns:repeat(auto-fill,minmax(240px,1fr));}
            .staff-tabs-container{flex-wrap:wrap;}
            .staff-tab{flex:1 1 calc(50% - 5px);min-width:150px;}
            .staff-lang-switcher{margin-inline-start:0;width:100%;margin-bottom:10px;order:-1;}
        }
        @media(max-width:768px){
            .staff-grid-container{grid-template-columns:1fr;}
            .staff-filters-bar{flex-direction:column;align-items:stretch;}
            .staff-search-input,.staff-filter-select{width:100%;}
            .staff-tabs-container{flex-direction:row;flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;}
            .staff-tabs-container::-webkit-scrollbar{height:4px;}
            .staff-tabs-container::-webkit-scrollbar-thumb{background:#00b8d4;border-radius:2px;}
            .staff-tab{flex:0 0 auto;min-width:150px;white-space:nowrap;}
            .staff-featured-container{flex-wrap:wrap;}
            .card-image-wrapper{width:100px;height:100px;align-self:flex-start;}
            .staff-grid-card{flex-flow:row;width:100%;margin:0;box-shadow:0 0 8px 0 #38485a;border-radius:0 !important;}
            .card-content{margin:0;box-shadow:none;padding:0 1em;}
            .card-contact{display:none;}
            .card-name{font-size:18px;margin:0;}
            .card-button{margin:1em 0;font-size:14px;width:fit-content;align-self:end;}
            .card-position{margin-bottom:0;}
        }
        </style>
    <?php }

    // =========================================================================
    // SCRIPTS
    // =========================================================================
    private function render_scripts( $settings, $show_filters ) {
        if ( $show_filters === 'explicitly_disabled_never' ) { ?>
        <script></script>
        <?php return; } ?>
        <script>
        (function(){
            function initStaffFilter(){
                var container=null;
                if(document.currentScript) container=document.currentScript.closest('.haifa-staff-grid-widget');
                if(!container){var all=document.querySelectorAll('.haifa-staff-grid-widget');if(all.length)container=all[all.length-1];}
                if(!container) container=document.querySelector('.haifa-staff-grid-widget');
                if(!container) return;

                // No filters AND no search → show everything
                if(container.dataset.noFilters==='1'){
                    container.querySelectorAll('.staff-grid-card').forEach(function(c){c.style.removeProperty('display');c.classList.remove('hidden');});
                    var fs=container.querySelector('.staff-featured-section');if(fs)fs.style.removeProperty('display');
                    return;
                }

                var searchInput=container.querySelector('.staff-search-input');
                var filterSelects=container.querySelectorAll('.staff-filter-select');
                var resetButton=container.querySelector('.staff-reset-filters');
                var tabs=container.querySelectorAll('.staff-tab');
                var cards=container.querySelectorAll('.staff-grid-card');
                var activeFilters={};

                function filterCards(){
                    var searchTerm=searchInput?searchInput.value.toLowerCase().trim():'';
                    cards.forEach(function(card){
                        var show=true;
                        var cardStatus=card.dataset.status||'';
                        var isDeceasedFilter=activeFilters['status']==='dead';
                        var isRetiredFilter=activeFilters['status']==='retired';
                        if(cardStatus==='dead'){ if(!isDeceasedFilter) show=false; }
                        else if(cardStatus==='retired'){ if(!isRetiredFilter) show=false; }
                        else { if(isDeceasedFilter||isRetiredFilter) show=false; }
                        if(show&&searchTerm){
                            var name=(card.dataset.name||'').toLowerCase();
                            var pos=(card.dataset.position||'').toLowerCase();
                            if(!name.includes(searchTerm)&&!pos.includes(searchTerm)) show=false;
                        }
                        if(show&&activeFilters['_extraTerms']){
                            var extraMatch=activeFilters['_extraTerms'].some(function(term){
                                var parts=term.split(':');
                                if(parts.length!==2) return false;
                                var ttype=parts[0],tval=parts[1];
                                if(ttype==='category') return (card.dataset.category||'').split(' ').filter(Boolean).includes(tval);
                                if(ttype==='tag')      return (card.dataset.tag||'').split(' ').filter(Boolean).includes(tval);
                                return false;
                            });
                            if(!extraMatch) show=false;
                        }
                        if(show){
                            for(var ft in activeFilters){
                                var fv=activeFilters[ft];
                                if(!fv||ft==='status'||ft==='_extraTerms') continue;
                                var cv='';
                                if(ft==='academic_type') cv=card.dataset.academicType||'';
                                else if(ft==='staff_type') cv=card.dataset.staffType||'';
                                else if(ft==='category') cv=card.dataset.category||'';
                                else if(ft==='tag') cv=card.dataset.tag||'';
                                else if(ft==='taxonomy'){
                                    var terms=fv.split('|');
                                    var matchesAny=false;
                                    for(var i=0;i<terms.length;i++){
                                        var parts=terms[i].split(':');
                                        if(parts.length!==2) continue;
                                        var ttype=parts[0],tval=parts[1];
                                        if(ttype==='category'||ttype==='tag'){
                                            if((card.dataset[ttype]||'').split(' ').filter(Boolean).includes(tval)){matchesAny=true;break;}
                                        } else if(ttype==='staff_type'){
                                            if((card.dataset.staffType||'')===tval){matchesAny=true;break;}
                                        } else if(ttype==='academic_type'){
                                            if((card.dataset.academicType||'')===tval){matchesAny=true;break;}
                                        } else if(ttype==='status'){
                                            if((card.dataset.status||'')===tval){matchesAny=true;break;}
                                        }
                                    }
                                    if(!matchesAny) show=false;
                                    continue;
                                } else { cv=card.dataset[ft]||''; }
                                if(ft==='category'||ft==='tag'){if(!(cv.split(' ').filter(Boolean).includes(fv))) show=false;}
                                else if(ft!=='taxonomy'){if(!cv.includes(fv)) show=false;}
                            }
                        }
                        // Featured card: hide if not in current tab
                        if(show&&card.dataset.isFeatured==='1'){
                            var featuredTabs=(card.dataset.featuredTabs||'').split(',').filter(Boolean);
                            var curId='';
                            for(var ft2 in activeFilters){if(activeFilters[ft2]){curId=(ft2==='taxonomy'?'taxonomy:':ft2+':')+activeFilters[ft2];break;}}
                            if(curId&&!featuredTabs.includes(curId)) show=false;
                        }
                        // Regular card: hide if featured in current tab
                        if(show&&card.dataset.isFeatured==='0'){
                            var curId2='';
                            for(var ft3 in activeFilters){if(activeFilters[ft3]){curId2=(ft3==='taxonomy'?'taxonomy:':ft3+':')+activeFilters[ft3];break;}}
                            if(curId2){
                                container.querySelectorAll('.staff-featured-card').forEach(function(fc){
                                    if(fc.dataset.name===card.dataset.name){
                                        var fts=(fc.dataset.featuredTabs||'').split(',').filter(Boolean);
                                        if(fts.includes(curId2)) show=false;
                                    }
                                });
                            }
                        }
                        if(show){card.classList.remove('hidden');card.style.display='';}
                        else{card.classList.add('hidden');card.style.display='none';}
                    });
                    var featSection=container.querySelector('.staff-featured-section');
                    if(featSection){
                        var anyVisible=Array.from(featSection.querySelectorAll('.staff-featured-card')).some(function(c){return c.style.display!=='none'&&!c.classList.contains('hidden');});
                        featSection.style.display=anyVisible?'':'none';
                    }
                }

                // Search
                if(searchInput){
                    searchInput.addEventListener('input',function(){updateClearBtn();filterCards();});
                    searchInput.addEventListener('keydown',function(e){if(e.key==='Escape'){this.value='';updateClearBtn();filterCards();}});
                }

                // Clear button
                var clearBtn=container.querySelector('.staff-search-clear');
                function updateClearBtn(){if(!clearBtn||!searchInput)return;clearBtn.classList.toggle('visible',!!searchInput.value);}
                if(clearBtn&&searchInput){clearBtn.addEventListener('click',function(){searchInput.value='';updateClearBtn();filterCards();});}
                updateClearBtn();

                // Dropdowns
                filterSelects.forEach(function(sel){sel.addEventListener('change',function(){activeFilters[this.dataset.filter]=this.value;filterCards();});});

                // Reset
                if(resetButton){resetButton.addEventListener('click',function(){
                    if(searchInput) searchInput.value='';
                    filterSelects.forEach(function(s){s.value='';});
                    activeFilters={};
                    cards.forEach(function(c){c.classList.remove('hidden');c.style.display='';});
                    updateClearBtn();
                });}

                // Tabs
                tabs.forEach(function(tab){
                    tab.addEventListener('click',function(){
                        var filterType=this.dataset.filter,filterValue=this.dataset.value;
                        var extraTerms=this.dataset.extraTerms?JSON.parse(this.dataset.extraTerms):null;
                        // Scroll
                        var featSection2=container.querySelector('.staff-featured-section');
                        var gridCont=container.querySelector('.staff-grid-container');
                        var isFeatVis=featSection2&&featSection2.offsetParent!==null&&featSection2.offsetHeight>0;
                        var scrollTarget=(isFeatVis?featSection2:gridCont)||container;
                        var tabsBar=container.querySelector('.staff-filters-tabs');
                        var tabsH=tabsBar?tabsBar.offsetHeight:0;
                        var headerH=parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--header-height'))||0;
                        window.scrollTo({top:scrollTarget.getBoundingClientRect().top+window.scrollY-headerH-tabsH-10,behavior:'smooth'});
                        // Save
                        sessionStorage.setItem('staff_active_tab_'+window.location.pathname,JSON.stringify({filterType:filterType,filterValue:filterValue}));
                        // Clear search
                        if(searchInput){searchInput.value='';updateClearBtn();}
                        // Activate
                        tabs.forEach(function(t){t.classList.remove('active');});
                        this.classList.add('active');
                        activeFilters={};
                        if(filterValue) activeFilters[filterType]=filterValue;
                        if(extraTerms&&extraTerms.length) activeFilters['_extraTerms']=extraTerms;
                        filterCards();
                    });
                });

                // Restore tab from session
                if(tabs.length>0){
                    var saved=sessionStorage.getItem('staff_active_tab_'+window.location.pathname);
                    if(saved){try{
                        var td=JSON.parse(saved);
                        tabs.forEach(function(t){
                            if(t.dataset.filter===td.filterType&&t.dataset.value===td.filterValue){
                                tabs.forEach(function(x){x.classList.remove('active');});
                                t.classList.add('active');
                                t.dataset.restored='1';
                                activeFilters={};
                                if(td.filterValue) activeFilters[td.filterType]=td.filterValue;
                                var savedExtra=t.dataset.extraTerms?JSON.parse(t.dataset.extraTerms):null;
                                if(savedExtra&&savedExtra.length) activeFilters['_extraTerms']=savedExtra;
                                filterCards();
                            }
                        });
                    }catch(e){}}
                }

                // Initialize: activate first tab — only if restore didn't already handle it
                var restoredActive=container.querySelector('.staff-tab.active[data-restored="1"]');
                if(tabs.length>0&&!restoredActive){
                    var activeTab=container.querySelector('.staff-tab.active')||tabs[0];
                    tabs.forEach(function(t){t.classList.remove('active');});
                    activeTab.classList.add('active');
                    activeFilters={};
                    if(activeTab.dataset.value) activeFilters[activeTab.dataset.filter]=activeTab.dataset.value;
                    var initExtra=activeTab.dataset.extraTerms?JSON.parse(activeTab.dataset.extraTerms):null;
                    if(initExtra&&initExtra.length) activeFilters['_extraTerms']=initExtra;
                    filterCards();
                } else if(tabs.length===0){
                    // No tab buttons shown (show_filters=off) — show only cards that belong
                    // to at least one visible tab, based on PHP-rendered data attributes.
                    // The allowed tabs are embedded as a JSON array on the widget container.
                    var allowedTabsRaw=container.dataset.allowedTabs;
                    if(allowedTabsRaw){
                        var allowedTabs=JSON.parse(allowedTabsRaw); // array of {filter, value} or {taxonomy, terms[]}
                        cards.forEach(function(card){
                            var show=false;
                            for(var i=0;i<allowedTabs.length;i++){
                                var t=allowedTabs[i];
                                if(t.filter==='staff_type'){
                                    if((card.dataset.staffType||'').split(' ').includes(t.value)){show=true;break;}
                                } else if(t.filter==='academic_type'){
                                    if((card.dataset.academicType||'')===t.value){show=true;break;}
                                } else if(t.filter==='status'){
                                    if((card.dataset.status||'')===t.value){show=true;break;}
                                } else if(t.filter==='taxonomy'){
                                    var matched=t.terms.some(function(term){
                                        var parts=term.split(':');
                                        if(parts.length!==2) return false;
                                        var ttype=parts[0],tval=parts[1];
                                        if(ttype==='category') return (card.dataset.category||'').split(' ').filter(Boolean).includes(tval);
                                        if(ttype==='tag')      return (card.dataset.tag||'').split(' ').filter(Boolean).includes(tval);
                                        if(ttype==='staff_type')    return (card.dataset.staffType||'')===tval;
                                        if(ttype==='academic_type') return (card.dataset.academicType||'')===tval;
                                        if(ttype==='status')        return (card.dataset.status||'')===tval;
                                        return false;
                                    });
                                    if(matched){show=true;break;}
                                }
                            }
                            if(show){card.classList.remove('hidden');card.style.display='';}
                            else{card.classList.add('hidden');card.style.display='none';}
                        });
                        var fs2=container.querySelector('.staff-featured-section');
                        if(fs2){
                            var anyFeat=Array.from(fs2.querySelectorAll('.staff-featured-card')).some(function(c){return c.style.display!=='none'&&!c.classList.contains('hidden');});
                            fs2.style.display=anyFeat?'':'none';
                        }
                    } else {
                        // No allowed tabs data — show everything
                        cards.forEach(function(c){c.classList.remove('hidden');c.style.display='';});
                        var fs3=container.querySelector('.staff-featured-section');if(fs3)fs3.style.removeProperty('display');
                    }
                }
            }

            initStaffFilter();
            if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',initStaffFilter);

            function setHeaderOffset(){
                var h=(document.querySelector('header')?document.querySelector('header').getBoundingClientRect().height:0)
                     +(document.getElementById('wpadminbar')?document.getElementById('wpadminbar').getBoundingClientRect().height:0);
                document.documentElement.style.setProperty('--header-height',h+'px');
            }
            setHeaderOffset();
            window.addEventListener('resize',setHeaderOffset);

            try{if(typeof elementorFrontend!=='undefined'){
                elementorFrontend.hooks.addAction('frontend/element_ready/widget',function($scope){
                    try{if($scope&&$scope.find&&$scope.find('.haifa-staff-grid-widget').length) setTimeout(initStaffFilter,100);}catch(e){}
                });
            }}catch(e){}
        })();
        </script>
        <?php
    }

    // =========================================================================
    // CONSTRUCTOR — editor dimming script
    // =========================================================================
    public function __construct( $data = [], $args = null ) {
        parent::__construct( $data, $args );
        add_action( 'elementor/editor/after_enqueue_scripts', function() { ?>
            <style>
            .elementor-control-default_tabs .elementor-repeater-row-item,
            .elementor-control-custom_tabs .elementor-repeater-row-item { transition:opacity .3s; }
            .elementor-repeater-row-item.tab-hidden .elementor-repeater-row-tools::after { content:'\e8f4'; font-family:'eicons'; position:absolute; right:5px; top:50%; transform:translateY(-50%); font-size:12px; color:#d32f2f; pointer-events:none; }
            .elementor-tab-control-advanced { display:none !important; }
            .elementor-repeater-row-tools { position:relative; }
            </style>
            <script>
            (function(){
                'use strict';
                function initDimming(){
                    if(typeof jQuery==='undefined'){setTimeout(initDimming,100);return;}
                    var $=jQuery;
                    function updateRowOpacity(){
                        ['.elementor-control-default_tabs','.elementor-control-custom_tabs'].forEach(function(sel){
                            $(sel+' .elementor-repeater-fields').each(function(){
                                var $row=$(this).closest('.elementor-repeater-row-item');
                                var $sw=$(this).find('.elementor-control-visible input[type="checkbox"]');
                                var checked=$sw.prop('checked');
                                $row.css('opacity',checked?'1':'0.5').toggleClass('tab-hidden',!checked);
                            });
                        });
                    }
                    $(document).ready(function(){
                        [500,1000,2000,3000,4000].forEach(function(t){setTimeout(updateRowOpacity,t);});
                        $(document).on('change','.elementor-control-visible input[type="checkbox"]',function(){setTimeout(updateRowOpacity,50);});
                        $(document).on('click','.elementor-control-visible .elementor-switch, .elementor-repeater-tool-duplicate, .elementor-repeater-row-item-title',function(){setTimeout(updateRowOpacity,150);});
                        if(typeof elementor!=='undefined'){
                            elementor.on('panel:init',function(){setTimeout(updateRowOpacity,300);});
                            elementor.channels.editor.on('section:activated',function(){setTimeout(updateRowOpacity,200);});
                        }
                        var panel=document.querySelector('#elementor-panel-content-wrapper');
                        if(panel){new MutationObserver(updateRowOpacity).observe(panel,{childList:true,subtree:true,attributes:true,attributeFilter:['style','class']});}
                    });
                }
                initDimming();
            })();
            </script>
        <?php } );
    }
}