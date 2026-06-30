<?php
/**
 * Archive Staff Template
 * Card-based grid layout — no Elementor required.
 * Renders all published staff members with basic contact info.
 */

// These helpers may already be defined by single-staff.php in some loading
// scenarios. Guards prevent fatal redeclaration errors.
if (!function_exists('is_university_network')) {
    function is_university_network() {
        $ip = $_SERVER['REMOTE_ADDR'];
        return ip_in_range($ip, '132.74.0.0/16');
    }
}

if (!function_exists('ip_in_range')) {
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
}

get_header(); ?>

<div class="haifa-staff-archive-wrapper">

    <header class="staff-archive-header">
        <h1 class="archive-title">
            <?php is_post_type_archive() ? post_type_archive_title() : _e('Staff Directory', 'haifa-staff'); ?>
        </h1>
    </header>

    <?php if (have_posts()): ?>

        <div class="staff-cards-grid">
            <?php while (have_posts()): the_post();
                $pid        = get_the_ID();
                $first_name = get_post_meta($pid, 'first_name', true);
                $last_name  = get_post_meta($pid, 'last_name',  true);
                $position   = get_post_meta($pid, 'position',   true);
                $email      = get_post_meta($pid, 'email',      true);
                $ext_phone  = get_post_meta($pid, 'external_phone', true);
                $inn_phone  = get_post_meta($pid, 'inner_phone',    true);
                $staff_image = get_post_meta($pid, 'staff_image',   true);

                $full_name = trim($first_name . ' ' . $last_name);

                $image_url = '';
                if (is_array($staff_image) && isset($staff_image['url'])) {
                    $image_url = $staff_image['url'];
                } elseif (is_numeric($staff_image)) {
                    $image_url = wp_get_attachment_image_url($staff_image, 'medium');
                }
                if (empty($image_url)) $image_url = HAIFA_STAFF_V2_PLUGIN_URL . 'assets/default-avatar.jpg';
            ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('staff-grid-card'); ?>>
                    <div class="card-image-wrapper">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($full_name); ?>" class="card-image">
                    </div>
                    <div class="card-content">
                        <h2 class="card-name"><?php echo esc_html($full_name); ?></h2>
                        <?php if ($position): ?>
                            <div class="card-position"><?php echo esc_html($position); ?></div>
                        <?php endif; ?>
                        <div class="card-contact">
                            <?php if ($email): ?>
                                <div class="contact-line"><span class="contact-label">דוא"ל:</span><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></div>
                            <?php endif; ?>
                            <?php if ($ext_phone): ?>
                                <div class="contact-line"><span class="contact-label">טלפון:</span><span><?php echo esc_html($ext_phone); ?></span></div>
                            <?php endif; ?>
                            <?php if (is_university_network() && $inn_phone): ?>
                                <div class="contact-line"><span class="contact-label">שלוחה:</span><span><?php echo esc_html($inn_phone); ?></span></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="card-button"><span class="button-icon">«</span> מידע נוסף</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>

        <?php if (get_next_posts_link() || get_previous_posts_link()): ?>
            <div class="staff-pagination">
                <?php the_posts_pagination(['mid_size' => 2, 'prev_text' => __('« הקודם', 'haifa-staff'), 'next_text' => __('הבא »', 'haifa-staff')]); ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-staff-message"><p><?php _e('לא נמצאו חברי סגל.', 'haifa-staff'); ?></p></div>
    <?php endif; ?>

</div>

<style>
.haifa-staff-archive-wrapper { max-width:1200px; margin:40px auto; padding:0 20px; }
.staff-archive-header { text-align:center; margin-bottom:40px; }
.archive-title { font-size:36px; font-weight:700; color:#333; margin:0; }
.staff-cards-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:30px; margin-bottom:50px; }
.staff-grid-card { background:#fff; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.1); transition:transform .3s,box-shadow .3s; }
.staff-grid-card:hover { transform:translateY(-5px); box-shadow:0 4px 16px rgba(0,0,0,.15); }
.card-image-wrapper { width:100%; height:320px; overflow:hidden; background:#f5f5f5; }
.card-image { width:100%; height:100%; object-fit:cover; display:block; }
.card-content { padding:25px; text-align:center; direction:rtl; }
.card-name { font-size:22px; font-weight:700; color:#333; margin:0 0 10px; line-height:1.2; }
.card-position { font-size:16px; color:#666; margin-bottom:20px; font-weight:500; }
.card-contact { margin:20px 0; text-align:right; }
.contact-line { margin:8px 0; font-size:14px; color:#555; }
.contact-label { font-weight:600; margin-left:6px; }
.contact-line a { color:#0066cc; text-decoration:none; }
.contact-line a:hover { text-decoration:underline; }
.card-button { display:inline-flex; align-items:center; gap:8px; background:#00b8d4; color:#fff; text-decoration:none; padding:10px 25px; border-radius:4px; font-size:15px; font-weight:600; transition:background .3s; margin-top:15px; direction:rtl; }
.card-button:hover { background:#00a0ba; color:#fff; }
.staff-pagination { text-align:center; margin-top:40px; }
.staff-pagination .nav-links { display:flex; justify-content:center; align-items:center; gap:10px; flex-wrap:wrap; }
.staff-pagination .nav-links a, .staff-pagination .nav-links span { display:inline-block; padding:10px 16px; background:#f5f5f5; color:#333; text-decoration:none; border-radius:4px; font-weight:500; transition:background .3s; }
.staff-pagination .nav-links a:hover { background:#00b8d4; color:#fff; }
.staff-pagination .nav-links .current { background:#00b8d4; color:#fff; }
.no-staff-message { text-align:center; padding:60px 20px; background:#f9f9f9; border-radius:8px; }
.no-staff-message p { font-size:18px; color:#666; margin:0; }
@media (max-width:768px) {
    .haifa-staff-archive-wrapper { padding:0 15px; margin:20px auto; }
    .archive-title { font-size:28px; }
    .staff-cards-grid { grid-template-columns:1fr; gap:20px; }
    .card-image-wrapper { height:280px; }
    .card-content { padding:20px; }
}
@media (max-width:480px) {
    .card-image-wrapper { height:240px; }
    .card-button { padding:8px 20px; font-size:14px; }
}
</style>

<?php get_footer(); ?>
