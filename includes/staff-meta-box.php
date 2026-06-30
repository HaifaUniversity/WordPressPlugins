<?php
if (!defined('ABSPATH')) exit;

// ── Field definitions (choices mirrors ACF JSON) ──────────────────────────────
function haifa_v2_field_choices(): array {
    return [
        'status' => [
            'alive'   => 'פעיל',
            'retired' => 'גימלאות',
            'dead'    => 'נפטר',
        ],
        'title_main' => [
            'mr'    => 'מר',
            'mrs'   => "גב'",
            'dr'    => 'ד"ר',
            'prof'  => "פרופ'",
            'other' => 'אחר',
        ],
        'staff_type' => [
            'academic'       => 'אקדמי',
            'administrative' => 'מנהלי',
        ],
        'staff_academic_type' => [
            'senior' => 'בכיר',
            'junior' => 'זוטר',
        ],
        'academic_degree_main' => [
            'professor'           => 'פרופסור מן המניין',
            'associate_professor' => 'פרופסור חבר',
            'professor_emeritus'  => 'פרופסור אמריטוס',
            'senior_lecturer'     => 'מרצה בכיר',
            'other'               => 'אחר',
        ],
        'socials_platform' => [
            'facebook'       => 'Facebook',
            'twitter'        => 'Twitter',
            'linkedin'       => 'LinkedIn',
            'instagram'      => 'Instagram',
            'youtube'        => 'YouTube',
            'researchgate'   => 'ResearchGate',
            'academia'       => 'Academia.edu',
            'orcid'          => 'ORCID',
            'google_scholar' => 'Google Scholar',
            'other'          => 'Other',
        ],
    ];
}

function haifa_v2_get_custom_title_options(): array {
    $raw  = get_option('haifa_staff_custom_title_main', '[]');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// ── Register meta boxes ───────────────────────────────────────────────────────
add_action('add_meta_boxes', function() {
    add_meta_box('haifa_staff_v2_details', 'פרטי סגל', 'haifa_v2_render_details_box', 'staff', 'normal', 'high');
});

// ── Enqueue meta box assets ───────────────────────────────────────────────────
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'staff') return;

    wp_enqueue_media();

    wp_enqueue_style(
        'haifa-staff-v2-meta',
        HAIFA_STAFF_V2_PLUGIN_URL . 'assets/admin-meta-box.css',
        [],
        HAIFA_STAFF_V2_VERSION
    );
    wp_enqueue_script(
        'haifa-staff-v2-meta',
        HAIFA_STAFF_V2_PLUGIN_URL . 'assets/admin-meta-box.js',
        ['jquery'],
        HAIFA_STAFF_V2_VERSION,
        true
    );
    wp_localize_script('haifa-staff-v2-meta', 'haifaStaffTitleOptions', [
        'nonce'   => wp_create_nonce('haifa_title_options'),
        'options' => haifa_v2_get_custom_title_options(),
    ]);
});

// ── Render: Details box ───────────────────────────────────────────────────────
function haifa_v2_render_details_box($post) {
    wp_nonce_field('haifa_staff_v2_save', 'haifa_staff_v2_nonce');
    $choices = haifa_v2_field_choices();

    $f = [];
    $fields_to_load = ['status','date_of_death','staff_type','staff_academic_type','first_name','last_name',
        'title_main','title_other','position','academic_degree_main','academic_degree_other',
        'email','inner_phone','external_phone','office_room','office_hours',
        'staff_image','cris_website','personal_website'];
    foreach ($fields_to_load as $k) $f[$k] = get_post_meta($post->ID, $k, true);

    // Normalize legacy v1 title_main keys → v2 keys
    $legacy_title_map = ['professor' => 'mr', 'associate_professor' => 'mrs', 'assistant_professor' => 'dr', 'lecturer' => 'prof'];
    if (isset($legacy_title_map[$f['title_main']])) $f['title_main'] = $legacy_title_map[$f['title_main']];

    $img_url = '';
    if ($f['staff_image']) $img_url = wp_get_attachment_image_url((int)$f['staff_image'], 'medium') ?: '';

    $socials_count = (int) get_post_meta($post->ID, 'socials', true);
    $socials_rows  = [];
    for ($i = 0; $i < $socials_count; $i++) {
        $socials_rows[] = [
            'platform' => get_post_meta($post->ID, "socials_{$i}_platform", true),
            'url'      => get_post_meta($post->ID, "socials_{$i}_url", true),
        ];
    }

    $context_positions_count = (int) get_post_meta($post->ID, 'context_positions', true);
    $context_positions_rows  = [];
    for ($i = 0; $i < $context_positions_count; $i++) {
        $context_positions_rows[] = [
            'key'   => get_post_meta($post->ID, "context_positions_{$i}_key", true),
            'value' => get_post_meta($post->ID, "context_positions_{$i}_value", true),
        ];
    }

    $platform_options = '';
    foreach ($choices['socials_platform'] as $val => $lab) {
        $platform_options .= '<option value="' . esc_attr($val) . '">' . esc_html($lab) . '</option>';
    }
    ?>
    <div class="hsv2-tabs">


        <div class="hsv2-row multi-fields" style="width:50%">
            <div class="hsv2-row">
                <label>סטטוס</label>
                <select name="status">
                    <?php foreach ($choices['status'] as $val => $lab): ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($f['status'], $val); ?>><?php echo esc_html($lab); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hsv2-row" id="hsv2-row-date-of-death" style="<?php echo ($f['status'] !== 'dead') ? 'display:none' : ''; ?>">
                <label>תאריך פטירה</label>
                <input type="date" name="date_of_death" value="<?php echo esc_attr($f['date_of_death']); ?>">
            </div>
        </div>

        <nav class="hsv2-tab-nav">
            <button type="button" class="hsv2-tab-btn active" data-tab="details">כללי</button>
            <button type="button" class="hsv2-tab-btn" data-tab="details2" 
                style="<?php echo (!is_array($f['staff_type']) || !in_array('academic', $f['staff_type'], true)) ? 'display:none' : ''; ?>">
                פרטים
            </button>
            <button type="button" class="hsv2-tab-btn" data-tab="english">English</button>
        </nav>

        <!-- Tab: כללי -->
        <div class="hsv2-tab-panel active" id="hsv2-tab-details">
            <div class="hsv2-grid">
                <div class="hsv2-col">
                    <div class="hsv2-col-inner">
                        <div>
                            <div class="hsv2-image-picker">
                                <input type="hidden" name="staff_image" id="hsv2-image-id" value="<?php echo esc_attr($f['staff_image']); ?>">
                                <div id="hsv2-image-preview">
                                    <?php if ($img_url): ?>
                                        <img src="<?php echo esc_url($img_url); ?>" style="max-width:180px;height:auto;border-radius:4px;">
                                    <?php endif; ?>
                                </div>
                                <div class="hsv2-image-btns">
                                    <button type="button" id="hsv2-select-image" class="button button-secondary">בחר תמונה</button>
                                    <button type="button" id="hsv2-remove-image" class="button" <?php echo $img_url ? '' : 'style="display:none"'; ?>>הסר תמונה</button>
                                </div>
                            </div>                            
                            <p style="font-size:12px" class="description">מידות: 360×360 פיקסלים. עד: 200KB.</p>
                        </div>
                        <div>  
                            <div class="hsv2-row multi-fields">
                                <div class="hsv2-row">
                                    <label>שם פרטי <span class="req">*</span></label>
                                    <input type="text" name="first_name" value="<?php echo esc_attr($f['first_name']); ?>">
                                </div>

                                <div class="hsv2-row">
                                    <label>שם משפחה <span class="req">*</span></label>
                                    <input type="text" name="last_name" value="<?php echo esc_attr($f['last_name']); ?>">
                                </div>
                            </div>
                            <div class="hsv2-row">
                                <label>סוג סגל <span class="req">*</span></label>
                                <div class="hsv2-checkboxes">
                                    <?php foreach ($choices['staff_type'] as $val => $lab): ?>
                                        <label class="hsv2-cb">
                                            <input type="checkbox" name="staff_type[]" value="<?php echo esc_attr($val); ?>"
                                                <?php echo (is_array($f['staff_type']) && in_array($val, $f['staff_type'], true)) ? 'checked' : ''; ?>>
                                            <?php echo esc_html($lab); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>


                            <div class="hsv2-row multi-fields">                               

                                <div class="hsv2-row" style="<?php echo (!is_array($f['staff_type']) || !in_array('academic', $f['staff_type'], true)) ? 'display:none' : ''; ?>">
                                    <label>סוג אקדמי <span class="req">*</span></label>
                                    <div class="hsv2-radios">
                                        <?php foreach ($choices['staff_academic_type'] as $val => $lab): ?>
                                            <label class="hsv2-cb"><input type="radio" name="staff_academic_type" value="<?php echo esc_attr($val); ?>" <?php checked($f['staff_academic_type'], $val); ?>> <?php echo esc_html($lab); ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="hsv2-row" style="<?php echo (!is_array($f['staff_type']) || !in_array('academic', $f['staff_type'], true)) ? 'display:none' : ''; ?>">
                                    <label>דרגה אקדמית</label>
                                    <select name="academic_degree_main">
                                        <option value=""></option>
                                        <?php foreach ($choices['academic_degree_main'] as $val => $lab): ?>
                                            <option value="<?php echo esc_attr($val); ?>" <?php selected($f['academic_degree_main'], $val); ?>><?php echo esc_html($lab); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="hsv2-row" id="hsv2-row-degree-other" style="<?php echo ($f['academic_degree_main'] !== 'other') ? 'display:none' : ''; ?>">
                                    <label>דרגה אחרת</label>
                                    <input type="text" name="academic_degree_other" value="<?php echo esc_attr($f['academic_degree_other']); ?>">
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>תואר <span class="req">*</span></label>
                            <select name="title_main" id="hsv2-title-main-select" required>
                                <option value=""></option>
                                <?php foreach ($choices['title_main'] as $val => $lab): ?>
                                    <?php if ($val === 'other') continue; ?>
                                    <option value="<?php echo esc_attr($val); ?>" <?php selected($f['title_main'], $val); ?>><?php echo esc_html($lab); ?></option>
                                <?php endforeach; ?>
                                <?php foreach (haifa_v2_get_custom_title_options() as $opt): ?>
                                    <option value="<?php echo esc_attr($opt['value']); ?>" <?php selected($f['title_main'], $opt['value']); ?>><?php echo esc_html($opt['label']); ?></option>
                                <?php endforeach; ?>
                                <option value="__add_new__">— הוסף תואר —</option>
                                <option value="other" <?php selected($f['title_main'], 'other'); ?>>אחר</option>
                            </select>
                        </div>

                        <div class="hsv2-row" id="hsv2-row-title-other" style="<?php echo ($f['title_main'] !== 'other') ? 'display:none' : ''; ?>">
                            <label>תואר אחר</label>
                            <input type="text" name="title_other" value="<?php echo esc_attr($f['title_other']); ?>">
                        </div>

                        <div class="hsv2-row">
                            <label>תפקיד</label>
                            <input type="text" name="position" value="<?php echo esc_attr($f['position']); ?>">
                        </div>
                    </div>

                    
                    <div class="hsv2-row hsv2-row-full">
                        <label>תפקידים נוספים</label>
                        <div id="hsv2-context-positions-repeater">
                            <?php if (empty($context_positions_rows)): ?>
                                <p class="hsv2-no-rows">אין רשומות. לחץ על "הוסף שורה".</p>
                            <?php else: ?>
                                <?php foreach ($context_positions_rows as $idx => $row): ?>
                                <div class="hsv2-repeater-row">
                                    <input type="text" name="context_positions_rows[<?php echo $idx; ?>][key]" placeholder="מפתח הקשר (לדוג׳: faculty-board)" value="<?php echo esc_attr($row['key']); ?>" style="flex:1">
                                    <input type="text" name="context_positions_rows[<?php echo $idx; ?>][value]" placeholder="תפקיד" value="<?php echo esc_attr($row['value']); ?>" style="flex:2">
                                    <button type="button" class="hsv2-remove-row button-link-delete">✕</button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="hsv2-add-context-position" class="button button-secondary">+ הוספת תפקיד</button>
                        <p class="description">כל שורה מגדירה תפקיד חלופי לפי מפתח הקשר. הווידג'ט של Elementor יציג את התפקיד המתאים למפתח שהוגדר בו.</p>
                    </div>

                </div>

                <div class="hsv2-col">
                    <div class="hsv2-row">
                        <label>דוא"ל <span class="req">*</span></label>
                        <input type="email" name="email" value="<?php echo esc_attr($f['email']); ?>" required>
                    </div>                   

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>טלפון חיצוני</label>
                            <input type="text" name="external_phone" value="<?php echo esc_attr($f['external_phone']); ?>">
                        </div>

                        <div class="hsv2-row">
                            <label>שלוחה פנימית</label>
                            <input type="text" name="inner_phone" value="<?php echo esc_attr($f['inner_phone']); ?>">
                        </div>
                    </div>

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>חדר</label>
                            <input type="text" name="office_room" value="<?php echo esc_attr($f['office_room']); ?>">
                        </div>

                        <div class="hsv2-row">
                            <label>שעות קבלה</label>
                            <input type="text" name="office_hours" value="<?php echo esc_textarea($f['office_hours']); ?>">
                        </div>
                    </div>


                    

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>CRIS</label>
                            <input type="url" name="cris_website" value="<?php echo esc_attr($f['cris_website']); ?>">
                        </div>

                        <div class="hsv2-row">
                            <label>אתר אישי</label>
                            <input type="url" name="personal_website" value="<?php echo esc_attr($f['personal_website']); ?>">
                        </div>
                    </div>

                    <div class="hsv2-row hsv2-row-full">
                        <label>רשתות חברתיות</label>
                        <div id="hsv2-socials-repeater">
                            <?php if (empty($socials_rows)): ?>
                                <p class="hsv2-no-rows">אין רשומות. לחץ על "הוסף שורה".</p>
                            <?php else: ?>
                                <?php foreach ($socials_rows as $idx => $row): ?>
                                <div class="hsv2-repeater-row">
                                    <select name="socials_rows[<?php echo $idx; ?>][platform]">
                                        <?php foreach ($choices['socials_platform'] as $val => $lab): ?>
                                            <option value="<?php echo esc_attr($val); ?>" <?php selected($row['platform'], $val); ?>><?php echo esc_html($lab); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="url" name="socials_rows[<?php echo $idx; ?>][url]" placeholder="https://..." value="<?php echo esc_attr($row['url']); ?>">
                                    <button type="button" class="hsv2-remove-row button-link-delete">✕</button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="hsv2-add-social" class="button button-secondary">+ הוסף שורה</button>
                    </div>


                </div>

            </div><!-- .hsv2-grid -->
        </div><!-- tab: כללי -->

        <!-- Tab: פרטים -->
        <div class="hsv2-tab-panel" id="hsv2-tab-details2">
            <div class="hsv2-editors">
            <?php
            $sections = [
                'about'               => 'אודות',
                'publications'        => 'פרסומים',
                'education'           => 'השכלה',
                'academic_background' => 'רקע אקדמי',
                'reserach_areas'      => 'תחומי מחקר',
                'media'               => 'מדיה',
                'prizes'              => 'פרסים',
                'additional_info'     => 'מידע נוסף',
            ];
            foreach ($sections as $key => $label):
                $value = get_post_meta($post->ID, $key, true);
            ?>
                <div class="hsv2-wysiwyg-section">
                    <h4 class="hsv2-section-label"><?php echo esc_html($label); ?></h4>
                    <?php wp_editor($value, 'hsv2_' . $key, [
                        'textarea_name' => $key,
                        'media_buttons' => true,
                        'teeny'         => false,
                        'textarea_rows' => 8,
                    ]); ?>
                </div>
            <?php endforeach; ?>
            </div>
        </div><!-- tab: פרטים -->

        <!-- Tab: English -->
        <div class="hsv2-tab-panel" id="hsv2-tab-english">
            <div class="hsv2-grid">
                <div class="hsv2-col">
                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>First Name</label>
                            <input type="text" name="first_name_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'first_name_en', true)); ?>">
                        </div>
                        <div class="hsv2-row">
                            <label>Last Name</label>
                            <input type="text" name="last_name_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'last_name_en', true)); ?>">
                        </div>
                    </div>

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>Position</label>
                            <input type="text" name="position_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'position_en', true)); ?>">
                        </div>
                        <div class="hsv2-row">
                            <label>Title (if "other")</label>
                            <input type="text" name="title_other_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'title_other_en', true)); ?>">
                        </div>
                    </div>

                    <div class="hsv2-row multi-fields">
                        <div class="hsv2-row">
                            <label>Academic Degree (if "other")</label>
                            <input type="text" name="academic_degree_other_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'academic_degree_other_en', true)); ?>">
                        </div>
                        <div class="hsv2-row">
                            <label>Office Room</label>
                            <input type="text" name="office_room_en" value="<?php echo esc_attr(get_post_meta($post->ID, 'office_room_en', true)); ?>">
                        </div>
                        <div class="hsv2-row">
                            <label>Office Hours</label>
                            <textarea name="office_hours_en" rows="3"><?php echo esc_textarea(get_post_meta($post->ID, 'office_hours_en', true)); ?></textarea>
                        </div>
                    </div>
                </div>
            </div><!-- .hsv2-grid -->

            <div class="hsv2-editors">
                <?php
                $sections_en = [
                    'about'               => 'About',
                    'publications'        => 'Publications',
                    'education'           => 'Education',
                    'academic_background' => 'Academic Background',
                    'reserach_areas'      => 'Research Areas',
                    'media'               => 'Media',
                    'prizes'              => 'Prizes',
                    'additional_info'     => 'Additional Info',
                ];
                foreach ($sections_en as $key => $label):
                    $value_en = get_post_meta($post->ID, $key . '_en', true);
                ?>
                    <div class="hsv2-wysiwyg-section">
                        <h4 class="hsv2-section-label"><?php echo esc_html($label); ?></h4>
                        <?php wp_editor($value_en, 'hsv2_' . $key . '_en', [
                            'textarea_name' => $key . '_en',
                            'media_buttons' => true,
                            'teeny'         => false,
                            'textarea_rows' => 8,
                            'tinymce'       => [
                                'directionality' => 'ltr',
                                'content_style'  => 'body { direction: ltr; text-align: left; }',
                            ],
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div><!-- tab: English -->

    </div><!-- .hsv2-tabs -->

    <!-- Hidden template for JS repeater rows -->
    <script id="hsv2-social-row-template" type="text/html">
        <div class="hsv2-repeater-row">
            <select name="socials_rows[__IDX__][platform]">
                <?php echo $platform_options; ?>
            </select>
            <input type="url" name="socials_rows[__IDX__][url]" placeholder="https://...">
            <button type="button" class="hsv2-remove-row button-link-delete">✕</button>
        </div>
    </script>

    <script id="hsv2-context-position-row-template" type="text/html">
        <div class="hsv2-repeater-row">
            <input type="text" name="context_positions_rows[__IDX__][key]" placeholder="מפתח הקשר (לדוג׳: faculty-board)" style="flex:1">
            <input type="text" name="context_positions_rows[__IDX__][value]" placeholder="תפקיד" style="flex:2">
            <button type="button" class="hsv2-remove-row button-link-delete">✕</button>
        </div>
    </script>
    <script>
    (function($){
        var cpIdx = <?php echo max(count($context_positions_rows), 0); ?>;
        $('#hsv2-add-context-position').on('click', function(){
            var tpl = $('#hsv2-context-position-row-template').html().replace(/__IDX__/g, cpIdx++);
            var $repeater = $('#hsv2-context-positions-repeater');
            $repeater.find('.hsv2-no-rows').remove();
            $repeater.append(tpl);
        });
        $(document).on('click', '#hsv2-context-positions-repeater .hsv2-remove-row', function(){
            $(this).closest('.hsv2-repeater-row').remove();
            if (!$('#hsv2-context-positions-repeater .hsv2-repeater-row').length) {
                $('#hsv2-context-positions-repeater').html('<p class="hsv2-no-rows">אין רשומות. לחץ על "הוסף שורה".</p>');
            }
        });
    })(jQuery);
    </script>
    <?php
}

// ── Save ──────────────────────────────────────────────────────────────────────
add_action('save_post', function($post_id) {
    if (!isset($_POST['haifa_staff_v2_nonce'])) return;
    if (!wp_verify_nonce($_POST['haifa_staff_v2_nonce'], 'haifa_staff_v2_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'staff') return;

    // Simple text fields
    $text_fields = ['first_name','last_name','title_main','title_other','position',
        'academic_degree_main','academic_degree_other','inner_phone','external_phone',
        'office_room','status','staff_academic_type'];
    foreach ($text_fields as $k) {
        update_post_meta($post_id, $k, sanitize_text_field($_POST[$k] ?? ''));
    }

    // English text fields
    $text_fields_en = ['first_name_en','last_name_en','position_en','title_other_en','academic_degree_other_en','office_room_en'];
    foreach ($text_fields_en as $k) {
        update_post_meta($post_id, $k, sanitize_text_field($_POST[$k] ?? ''));
    }

    // English textarea
    update_post_meta($post_id, 'office_hours_en', sanitize_textarea_field($_POST['office_hours_en'] ?? ''));

    // Resolve and store the title label as a plain string
    $title_val = sanitize_text_field($_POST['title_main'] ?? '');
    $custom    = json_decode(get_option('haifa_staff_custom_title_main', '[]'), true);
    $label_map = ['mr' => 'מר', 'mrs' => "גב'", 'dr' => 'ד"ר', 'prof' => "פרופ'", 'other' => 'אחר'];
    $resolved  = $label_map[$title_val] ?? '';
    if (!$resolved && is_array($custom)) {
        foreach ($custom as $opt) {
            if (($opt['value'] ?? '') === $title_val) { $resolved = $opt['label']; break; }
        }
    }
    update_post_meta($post_id, 'title_main_label', $resolved);

    // Email
    update_post_meta($post_id, 'email', sanitize_email($_POST['email'] ?? ''));

    // Date
    update_post_meta($post_id, 'date_of_death', sanitize_text_field($_POST['date_of_death'] ?? ''));

    // Textarea
    update_post_meta($post_id, 'office_hours', sanitize_textarea_field($_POST['office_hours'] ?? ''));

    // URLs
    foreach (['cris_website', 'personal_website'] as $k) {
        update_post_meta($post_id, $k, esc_url_raw($_POST[$k] ?? ''));
    }

    // Checkbox array (staff_type)
    $staff_type = array_map('sanitize_text_field', (array)($_POST['staff_type'] ?? []));
    update_post_meta($post_id, 'staff_type', $staff_type);

    // Image (attachment ID)
    update_post_meta($post_id, 'staff_image', absint($_POST['staff_image'] ?? 0) ?: '');

    // WYSIWYG fields
    $wysiwyg_fields = ['about','publications','education','academic_background','reserach_areas','media','prizes','additional_info'];
    foreach ($wysiwyg_fields as $k) {
        update_post_meta($post_id, $k, wp_kses_post($_POST[$k] ?? ''));
    }

    // English WYSIWYG fields
    foreach ($wysiwyg_fields as $k) {
        update_post_meta($post_id, $k . '_en', wp_kses_post($_POST[$k . '_en'] ?? ''));
    }

    // Socials repeater
    $old_count = (int) get_post_meta($post_id, 'socials', true);
    for ($i = 0; $i < $old_count + 5; $i++) {
        delete_post_meta($post_id, "socials_{$i}_platform");
        delete_post_meta($post_id, "socials_{$i}_url");
    }
    $new_rows = $_POST['socials_rows'] ?? [];
    $count    = 0;
    foreach ($new_rows as $row) {
        $platform = sanitize_text_field($row['platform'] ?? '');
        $url      = esc_url_raw($row['url'] ?? '');
        if ($platform && $url) {
            update_post_meta($post_id, "socials_{$count}_platform", $platform);
            update_post_meta($post_id, "socials_{$count}_url", $url);
            $count++;
        }
    }
    update_post_meta($post_id, 'socials', $count);

    // Context positions repeater
    $old_cp_count = (int) get_post_meta($post_id, 'context_positions', true);
    for ($i = 0; $i < $old_cp_count + 5; $i++) {
        delete_post_meta($post_id, "context_positions_{$i}_key");
        delete_post_meta($post_id, "context_positions_{$i}_value");
    }
    $new_cp_rows = $_POST['context_positions_rows'] ?? [];
    $cp_count    = 0;
    foreach ($new_cp_rows as $row) {
        $key = sanitize_text_field($row['key'] ?? '');
        $val = sanitize_text_field($row['value'] ?? '');
        if ($key !== '') {
            update_post_meta($post_id, "context_positions_{$cp_count}_key",   $key);
            update_post_meta($post_id, "context_positions_{$cp_count}_value", $val);
            $cp_count++;
        }
    }
    update_post_meta($post_id, 'context_positions', $cp_count);

}, 10);


// ── Custom title options AJAX ──────────────────────────────────────────────────

add_action('wp_ajax_haifa_add_title_option', function() {
    check_ajax_referer('haifa_title_options', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('אין הרשאה');
    $label    = sanitize_text_field($_POST['label'] ?? '');
    $label_en = sanitize_text_field($_POST['label_en'] ?? '');
    if (!$label) wp_send_json_error('חסרה תווית');
    $options   = haifa_v2_get_custom_title_options();
    $value     = 'custom_' . time();
    $options[] = ['value' => $value, 'label' => $label, 'label_en' => $label_en];
    update_option('haifa_staff_custom_title_main', wp_json_encode($options));
    wp_send_json_success(['value' => $value, 'label' => $label, 'label_en' => $label_en]);
});

add_action('wp_ajax_haifa_update_title_option', function() {
    check_ajax_referer('haifa_title_options', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('אין הרשאה');
    $value     = sanitize_text_field($_POST['value'] ?? '');
    $new_label = sanitize_text_field($_POST['label'] ?? '');
    $new_label_en = sanitize_text_field($_POST['label_en'] ?? '');
    if (!$value || !$new_label) wp_send_json_error('נתונים חסרים');
    $options = haifa_v2_get_custom_title_options();
    foreach ($options as &$opt) {
        if ($opt['value'] === $value) { $opt['label'] = $new_label; $opt['label_en'] = $new_label_en; break; }
    }
    unset($opt);
    update_option('haifa_staff_custom_title_main', wp_json_encode($options));
    wp_send_json_success();
});

add_action('wp_ajax_haifa_delete_title_option', function() {
    check_ajax_referer('haifa_title_options', 'nonce');
    if (!current_user_can('edit_posts')) wp_send_json_error('אין הרשאה');
    $value = sanitize_text_field($_POST['value'] ?? '');
    if (!$value) wp_send_json_error('ערך חסר');
    $count = (new WP_Query([
        'post_type'      => 'staff',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [['key' => 'title_main', 'value' => $value, 'compare' => '=']],
    ]))->found_posts;
    if ($count > 0) wp_send_json_error(sprintf('לא ניתן למחוק — %d אנשי סגל משתמשים באפשרות זו', $count));
    $options = array_values(array_filter(haifa_v2_get_custom_title_options(), fn($o) => $o['value'] !== $value));
    update_option('haifa_staff_custom_title_main', wp_json_encode($options));
    wp_send_json_success();
});