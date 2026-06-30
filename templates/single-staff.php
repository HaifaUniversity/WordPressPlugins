<?php
/**
 * Single Staff Member Template – Haifa Staff V2
 * No ACF dependency. Reads all fields via get_post_meta().
 */

add_filter('body_class', function($classes) {
    if (get_post_type() === 'staff') {
        global $post;
        $classes[] = 'elementor-page';
        $classes[] = 'elementor-page-' . $post->ID;
    }
    return $classes;
});


function is_university_network() {
    $ip = $_SERVER['REMOTE_ADDR'];
    foreach (['132.74.0.0/16'] as $range) {
        if (ip_in_range($ip, $range)) return true;
    }
    return false;
}

function ip_in_range($ip, $range) {
    if (strpos($range, '/') === false) return $ip === $range;
    list($subnet, $mask) = explode('/', $range);
    $ip_long  = ip2long($ip);
    $sub_long = ip2long($subnet);
    if ($ip_long === false || $sub_long === false) return false;
    $mask_long = -1 << (32 - (int)$mask);
    $sub_long &= $mask_long;
    return ($ip_long & $mask_long) === $sub_long;
}

add_action('wp_head', function() {
    echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '" />' . "\n";
}, 1);

// ── Language detection ────────────────────────────────────────────────────────
$staff_context = get_query_var('staff_context', '');

// WPML fallback: detect language from original URI before WPML rewrote it
if (empty($staff_context)) {
    $original_uri = $_SERVER['HAIFA_STAFF_ORIGINAL_URI'] ?? '';
    if (preg_match('#^/en/#', $original_uri)) {
        $staff_context = 'en';
    }
}

$lang = 'he';
if (preg_match('#^(en|ar)(/|$)#', $staff_context, $m)) {
    $lang = $m[1];
}

// ── Language-aware field getter (falls back to Hebrew if English is empty) ────
function haifa_v2_lang_field(string $field, int $pid, string $lang, bool $fallback = true): string {
    if ($lang !== 'he') {
        $val = get_post_meta($pid, $field . '_' . $lang, true);
        if (!empty($val)) return $val;
        if (!$fallback) return '';
    }
    return get_post_meta($pid, $field, true) ?: '';
}

// ── Label maps ────────────────────────────────────────────────────────────────
$section_labels = $lang === 'en'
    ? ['about' => 'About', 'publications' => 'Publications', 'education' => 'Education',
       'academic_background' => 'Academic Background', 'reserach_areas' => 'Research Areas',
       'media' => 'Media', 'prizes' => 'Awards', 'additional_info' => 'Additional Info']
    : ['about' => 'אודות', 'publications' => 'פרסומים', 'education' => 'השכלה',
       'academic_background' => 'רקע אקדמי', 'reserach_areas' => 'תחומי מחקר',
       'media' => 'מדיה', 'prizes' => 'פרסים', 'additional_info' => 'מידע נוסף'];

$contact_labels = $lang === 'en'
    ? ['email' => 'Email', 'phone' => 'Phone', 'ext' => 'Extension', 'room' => 'Room', 'hours' => 'Office Hours', 'personal' => 'Personal Site']
    : ['email' => 'דוא"ל', 'phone' => 'טלפון', 'ext' => 'טלפון פנימי', 'room' => 'חדר', 'hours' => 'שעות קבלה', 'personal' => 'אתר אישי'];

get_header(); ?>

<div class="haifa-staff-single-wrapper" dir="<?php echo $lang === 'en' ? 'ltr' : 'rtl'; ?>">
    <?php while (have_posts()) : the_post();

        // Build breadcrumbs — prefer the ?from= referrer path (set by the staff grid widget
        // when linking here), falling back to the current URL structure if absent.
        $breadcrumb_items = [];
        $current_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $path_parts   = explode('/', $current_path);
        $staff_slug   = array_pop($path_parts);

        $from_param = isset($_GET['from']) ? trim(sanitize_text_field(wp_unslash($_GET['from'])), '/') : '';
        if ($from_param !== '') {
            // Validate: only allow internal-looking relative paths, never full URLs or protocol-relative.
            $from_param = preg_replace('#^https?://#i', '', $from_param);
            if (strpos($from_param, '//') === 0 || preg_match('#^[a-z]+://#i', $from_param)) {
                $from_param = '';
            }
        }
        if ($from_param !== '') {
            $path_parts = explode('/', $from_param);
        }

        if (!empty($path_parts)) {
            // Show only the immediate parent (last segment before the person slug)
            $parent_part = end($path_parts);
            $parent_path = implode('/', $path_parts);
            $page = get_page_by_path($parent_path, OBJECT, ['page', 'post']);

            // If Polylang is active — get the correct language version of the parent page
            if ( $page && function_exists( 'pll_get_post' ) ) {
                $localized = pll_get_post( $page->ID, $lang === 'he' ? pll_default_language() : $lang );
                if ( $localized ) {
                    $page = get_post( $localized );
                }
            }

            // Strip -en suffix from URL for clean display
            // Build clean parent URL — strip language prefix, ignore WP permalink
            $clean_parent = preg_replace( '#^(en|ar|he)/#', '', $parent_path );
            $clean_parent = preg_replace( '#-(en|ar|he)$#', '', $clean_parent );

            if ( $lang !== 'he' ) {
                $parent_url = site_url( '/' . $lang . '/' . $clean_parent . '/' );
            } else {
                $parent_url = site_url( '/' . $clean_parent . '/' );
            }

            $parent_title = $page ? $page->post_title : ucwords( str_replace( '-', ' ', urldecode( $parent_part ) ) );

            // If Polylang active — get translated page title
            if ( $page && function_exists( 'pll_get_post' ) ) {
                $localized = pll_get_post( $page->ID, $lang === 'he' ? pll_default_language() : $lang );
                if ( $localized ) $parent_title = get_the_title( $localized );
            }

            $breadcrumb_items[] = [ 'title' => $parent_title, 'url' => $parent_url ];
        }

        // Read all fields
        $pid                 = get_the_ID();
        $first_name          = haifa_v2_lang_field('first_name', $pid, $lang);
        $last_name           = haifa_v2_lang_field('last_name', $pid, $lang);
        $title_main_val      = get_post_meta($pid, 'title_main', true);
        $title_other         = haifa_v2_lang_field('title_other', $pid, $lang);
        $title_main_labels   = $lang === 'en'
                                ? ['mr' => 'Mr.', 'mrs' => 'Ms.', 'dr' => 'Dr.' , 'prof' => 'Prof.', 'other' => '']
                                : ['mr' => 'מר', 'mrs' => "גב'", 'dr' => 'ד"ר', 'prof' => "פרופ'", 'other' => 'אחר'];
        $title_main          = ($title_main_val === 'other' && !empty($title_other))
                                ? $title_other
                                : ($title_main_labels[$title_main_val] ?? '');
        $position            = haifa_v2_lang_field('position', $pid, $lang, false);
        $cp_count          = (int) get_post_meta($pid, 'context_positions', true);
        $context_positions = [];
        for ($i = 0; $i < $cp_count; $i++) {
            $cp_val = get_post_meta($pid, "context_positions_{$i}_value", true);
            if ($cp_val !== '') $context_positions[] = $cp_val;
        }
        $email               = get_post_meta($pid, 'email', true);
        $external_phone      = get_post_meta($pid, 'external_phone', true);
        $inner_phone         = get_post_meta($pid, 'inner_phone', true);
        $office_room         = haifa_v2_lang_field('office_room', $pid, $lang, false);
        $office_hours        = haifa_v2_lang_field('office_hours', $pid, $lang, false);
        $staff_image         = get_post_meta($pid, 'staff_image', true);
        $about               = get_post_meta($pid, 'about', true);
        $publications        = get_post_meta($pid, 'publications', true);
        $education           = get_post_meta($pid, 'education', true);
        $academic_background = get_post_meta($pid, 'academic_background', true);
        $research_areas      = get_post_meta($pid, 'reserach_areas', true);
        $media               = get_post_meta($pid, 'media', true);
        $prizes              = get_post_meta($pid, 'prizes', true);
        $additional_info     = get_post_meta($pid, 'additional_info', true);
        $personal_website    = get_post_meta($pid, 'personal_website', true);
        $cris_website        = get_post_meta($pid, 'cris_website', true);
        $staff_type          = get_post_meta($pid, 'staff_type', true);
        $status              = get_post_meta($pid, 'status', true);
        $is_academic         = is_array($staff_type) && in_array('academic', $staff_type, true);

        $full_name = trim($first_name . ' ' . $last_name);
        if ($status === 'dead' && $lang !== 'en') $full_name .= ' ז"ל';

        $image_url = '';
        if (is_array($staff_image) && isset($staff_image['url'])) {
            $image_url = $staff_image['url'];
        } elseif ($staff_image) {
            $image_url = wp_get_attachment_image_url((int)$staff_image, 'full') ?: '';
        }
        if (empty($image_url)) $image_url = HAIFA_STAFF_V2_PLUGIN_URL . 'assets/default-avatar.jpg';

        $has_links = $personal_website || $cris_website;
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('haifa-staff-single'); ?>>

        <!-- ① STICKY TOP BAR -->
        <div class="staff-sticky-bar">
            <div class="sticky-bar-photo">
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($full_name); ?>">
            </div>
            <div class="sticky-bar-identity">
                <?php if (!empty($breadcrumb_items)): ?>
                    <nav class="staff-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb-list">
                            <?php foreach ($breadcrumb_items as $item): ?>
                                <li class="breadcrumb-item">
                                    <a id="staff-breadcrumb-link" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['title']); ?></a>
                                    <span class="breadcrumb-separator">›</span>
                                </li>
                            <?php endforeach; ?>
                            <li class="breadcrumb-item breadcrumb-current" aria-current="page">
                                <?php if ($title_main): echo esc_html($title_main) . ' '; endif; ?><?php echo esc_html($full_name); ?>
                            </li>
                        </ol>
                    </nav>
                <?php endif; ?>
                <h1 class="staff-name"><?php if ($title_main): echo esc_html($title_main) . ' '; endif; ?><?php echo esc_html($full_name); ?></h1>
                <?php if ($position || !empty($context_positions)): ?>
                    <div class="staff-title">
                        <?php echo esc_html($position); ?>
                        <?php foreach ($context_positions as $cp): ?>
                            <span class="staff-title-sep">|</span><?php echo esc_html($cp); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            /// Check minimum English fields
            $has_english = !empty(get_post_meta($pid, 'first_name_en', true))
                        && !empty(get_post_meta($pid, 'last_name_en', true))
                        && !empty(get_post_meta($pid, 'about_en', true));

            // Build language switcher URLs
            $current_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
            $he_path = preg_replace('#^en/#', '', $current_path);
            $he_url  = home_url('/' . $he_path . '/');
            $en_url  = site_url('/en/' . $he_path . '/');
        ?>
        <?php if ($lang === 'en' || $has_english): ?>
        <div class="sticky-bar-links">
            <?php if ($lang === 'he'): ?>
                <span class="lang-current">עברית</span><span class="lang-sep"> | </span><a href="<?php echo esc_url($en_url); ?>" class="lang-alt">English</a>
            <?php else: ?>
                <a href="<?php echo esc_url($he_url); ?>" class="lang-alt">עברית</a><span class="lang-sep"> | </span><span class="lang-current">English</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- ② BODY: SIDEBAR + MAIN -->
        <div class="staff-body">

            <aside class="staff-sidebar">
                <div class="staff-contact">
                    <?php if ($email): ?>
                        <div class="contact-item"><span class="contact-label"><?php echo $contact_labels['email']; ?></span><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
                    <?php endif; ?>
                    <?php if ($external_phone): ?>
                        <?php
                        $phone_digits    = preg_replace('/[^0-9]/', '', $external_phone);
                        $phone_formatted = preg_replace('/^(0[2-9])(\d{7})$/', '$1-$2', $phone_digits);
                        if ($phone_formatted === $phone_digits) {
                            $phone_formatted = preg_replace('/^(05\d)(\d{7})$/', '$1-$2', $phone_digits);
                        }
                        ?>
                        <div class="contact-item"><span class="contact-label"><?php echo $contact_labels['phone']; ?></span><a href="tel:<?php echo esc_attr($phone_digits); ?>"><?php echo esc_html($phone_formatted); ?></a></div>
                    <?php endif; ?>
                    <?php if (is_university_network() && $inner_phone): ?>
                        <div class="contact-item"><span class="contact-label"><?php echo $contact_labels['ext']; ?></span><?php echo esc_html($inner_phone); ?></div>
                    <?php endif; ?>
                    <?php if ($office_room): ?>
                        <div class="contact-item"><span class="contact-label"><?php echo $contact_labels['room']; ?></span><?php echo esc_html($office_room); ?></div>
                    <?php endif; ?>
                    <?php if ($office_hours): ?>
                        <div class="contact-item"><span class="contact-label"><?php echo $contact_labels['hours']; ?></span><?php echo esc_html($office_hours); ?></div>
                    <?php endif; ?>
                    <?php if ($cris_website): ?>
                        <div class="contact-item"><a href="<?php echo esc_url($cris_website); ?>" target="_blank" rel="noopener" class="contact-label"><?php echo $lang === 'en' ? 'Research Profile (CRIS)' : 'פרופיל מחקר (CRIS)'; ?></a></div>
                    <?php endif; ?>
                    <?php if ($personal_website): ?>
                        <div class="contact-item"><a href="<?php echo esc_url($personal_website); ?>" target="_blank" rel="noopener" class="contact-label"><?php echo $contact_labels['personal']; ?></a></div>
                    <?php endif; ?>
                </div>
            </aside>

            <main class="staff-main">

                <?php
                $accordion_fields = ['about', 'publications', 'education', 'academic_background', 'reserach_areas', 'prizes', 'media', 'additional_info'];
                $first = true;
                foreach ($accordion_fields as $key):
                    $value = haifa_v2_lang_field($key, $pid, $lang, $key === 'about' ? true : false);
                    if ($value && in_array($key, ['about', 'additional_info', 'prizes', 'media'])) {
                        $value = wpautop($value);
                    }
                    if (!$value) continue;
                ?>
                <div class="accordion-item <?php echo $first ? 'active' : ''; ?>">
                    <h2 class="accordion-header" onclick="toggleAccordion(this)"><span><?php echo esc_html($section_labels[$key]); ?></span><span class="accordion-icon">▼</span></h2>
                    <div class="accordion-content"><?php echo wp_kses_post($value); ?></div>
                </div>
                <?php $first = false; endforeach; ?>

            </main>
        </div>
    </article>

    <?php endwhile; ?>
</div>

<style>
.haifa-staff-single-wrapper { margin:0 auto; margin-bottom:2em; min-height: 70vh;}
.haifa-staff-single-wrapper:before {content: ''; position: fixed; height: 100vh; background: #F5F5F9; right: 0; width: 350px;}
.haifa-staff-single-wrapper[dir="ltr"].haifa-staff-single-wrapper:before { right: auto; left: 0; }
.staff-breadcrumbs { padding:14px 0; margin-bottom:0; position: absolute; top:1em; margin-inline-start:4em}
.breadcrumb-list { display:flex; flex-wrap:wrap; align-items:center; list-style:none; margin:0; padding:0; gap:6px; }
.breadcrumb-item { display:flex; align-items:center; gap:6px; font-size:13px; color:#666; }
.breadcrumb-item a { color:#0073aa; text-decoration:none; }
.breadcrumb-item a:hover { color:#005177; text-decoration:underline; }
.breadcrumb-separator { color:#000; font-size:120%}
.breadcrumb-current { color:#333; font-weight:600; }
.staff-sticky-bar { position:sticky; top:0; z-index:100; background:transparent; color:#1F3343; display:flex; align-items:center; gap:16px; padding:10px 24px; pointer-events: none;}
.staff-sticky-bar  > * {pointer-events: auto;}
.staff-sticky-bar::before { content:''; position:absolute; top:0; bottom:0; left:0;right:0; height:50%; background:#1F3343; z-index: -1; margin: auto;}
.sticky-bar-photo { flex-shrink:0; width:auto; height:260px; overflow:hidden; border:2px solid #e0e0e0; }
.sticky-bar-photo img { width:100%; height:100%; object-fit:cover; display:block; }
.sticky-bar-identity { min-width:0; }
.sticky-bar-identity .staff-name {background: #08BBE7; color: #fff; display: inline-block; padding: 0.2em 0.5em; font-size: 40px; font-weight: 500; position: absolute; top:.4em; transform: translate(0.9em, 75%); text-align: start; line-height: 1em;}
.haifa-staff-single-wrapper[dir="ltr"] .sticky-bar-identity .staff-name {right: auto; transform: translate(-0.9em, 75%);}
.sticky-bar-identity .staff-title { display: inline-block; color: #fff;font-size: 24px; position: absolute;}
.sticky-bar-links { position:absolute; left:1em; top:15%; display:flex; align-items:center; gap:4px; background:transparent; padding:0; gap:1em}
.haifa-staff-single-wrapper[dir="ltr"] .sticky-bar-links { left:auto; right:1em; }
.sticky-bar-links .lang-current { color:#000; font-weight:700; font-size:15px; }
.sticky-bar-links .lang-sep {  font-size:15px; }
.sticky-bar-links .lang-alt { color:#1698B9; font-weight:700; font-size:15px; text-decoration:none; }
.sticky-bar-links .lang-alt:hover { text-decoration:underline; }
.ext-link { display:inline-flex; align-items:center; justify-content:center; text-decoration:none; transition:opacity 0.2s; padding:8px 14px; border-radius:4px; line-height:0; }
.staff-body { display:flex; align-items:flex-start; gap:30px; margin-top:30px; }
.staff-sidebar { flex-shrink:0; position:sticky; top:0; align-self:flex-start; padding-inline-start:2em; width:300px}
.staff-contact { text-align:right; }
.haifa-staff-single-wrapper[dir="ltr"] .staff-contact { text-align:left; }
.haifa-staff-single-wrapper[dir="ltr"] .staff-main { text-align:left; padding-inline-end:0; width:100%}
.contact-item { margin:0 0 14px 0; font-size:15px; color:#4a4a4a; line-height:1em; }
.contact-item:last-child { margin-bottom:0; }
.contact-label { display:inline; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#4a4a4a; margin-inline-end:4px; }
span.contact-label::after { content:':'; }
.contact-item a { color:#4a4a4a; text-decoration:none; word-break:break-all; text-decoration:underline; }
.contact-item a:hover { color:#1698B9}
.staff-main { flex:1; min-width:0; text-align:right; max-width:1200px; margin:auto;}
.staff-main:before {content: ""; background: #fff; height: 200px; position: fixed; top: 0; left: 0; width: 80%;}
.accordion-item { overflow:hidden; transition:box-shadow 0.2s; border-bottom: 1px solid #08BBE7;}
.accordion-item:first-child { margin-bottom:0; border-top: 1px solid #08BBE7;}
.accordion-header { font-size:17px; font-weight:600; color:#333; margin:0; padding:16px 20px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; transition:background 0.2s, color 0.2s; user-select:none; }
.accordion-header:hover { background:#eef1f4; }
.accordion-item.active .accordion-header { background:#D8FDFF; }
.accordion-icon { font-size:13px; transition:transform 0.3s ease; display:inline-block; }
.accordion-item.active .accordion-icon { transform:rotate(180deg); }
.accordion-content { max-height:0; overflow-x:auto; word-break:break-word;  transition:max-height 0.4s ease, padding 0.3s ease; padding:0 20px; background:#fff; }
.accordion-item.active .accordion-content { max-height:300px; padding:20px; overflow:auto}
.accordion-content p { margin:0 0 12px 0; font-size:17px; line-height:1.7; color:#444; }
.accordion-content p:last-child { margin-bottom:0; }
.accordion-content a { color:#0066cc; text-decoration:none; }
.accordion-content a:hover { text-decoration:underline; }
.accordion-content ul, .accordion-content ol { margin:10px 0; padding-right:20px; }
.accordion-content li { margin:6px 0; font-size:17px; line-height:1.6; color:#444; }

.accordion-solo .accordion-icon { display:none; }
.accordion-solo .accordion-header { cursor:default; }
.accordion-solo .accordion-header:hover { background:#D8FDFF; }

.staff-title-sep { margin:0 6px; opacity:0.6; }

@media (max-width:768px) {
    .staff-sticky-bar { padding:8px 14px; gap:10px; }
    .sticky-bar-photo { width:100px; height:100px; }
    .sticky-bar-identity .staff-name { font-size:22px; top: 1em;}
    .sticky-bar-identity .staff-title { transform: translate(0, -120%); font-size: 12px; }
    .personal-link { padding:6px 10px; font-size:12px; }
    .staff-body { flex-direction:column; gap:20px; }
    .staff-sidebar { width:100%; position:static; }
    .staff-contact { display:flex; flex-wrap:wrap; gap:12px 24px; }
    .contact-item { margin:0; }
    .haifa-staff-single-wrapper:before {display:none}
    .staff-main:before {z-index: 1; width: 100%; height: 50px;}
    .sticky-bar-links {height: auto; padding: 0.5em 0; top: 0;}
    .breadcrumb-list {display:none}
}
</style>

<script>
function toggleAccordion(header) {
    const allItems = document.querySelectorAll('.accordion-item');
    if (allItems.length === 1) return;
    const item      = header.parentElement;
    const wasActive = item.classList.contains('active');
    allItems.forEach(i => i.classList.remove('active'));
    if (!wasActive) item.classList.add('active');
}

// Mark solo accordion on load
document.addEventListener('DOMContentLoaded', function() {
    const allItems = document.querySelectorAll('.accordion-item');
    if (allItems.length === 1) allItems[0].classList.add('accordion-solo');
});

function setSidebarTop() {
    const bar     = document.querySelector('.staff-sticky-bar');
    const sidebar = document.querySelector('.staff-sidebar');
    if (bar && sidebar) sidebar.style.top = bar.offsetHeight + 'px';
}
setSidebarTop();
window.addEventListener('resize', setSidebarTop);


</script>

<?php get_footer(); ?>
