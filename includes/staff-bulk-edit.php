<?php
if (!defined('ABSPATH')) exit;
if (!current_user_can('edit_posts')) wp_die('אין הרשאה');

// ── Save handler ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hsbe_nonce'])) {
    check_admin_referer('hsbe_save', 'hsbe_nonce');

    $url_keys   = ['cris_website', 'personal_website'];
    $email_keys = ['email'];
    $multi_keys = ['staff_type'];
    $allowed_keys = [
        'title_main','first_name','last_name','status','position',
        'staff_type','staff_academic_type','academic_degree_main',
        'email','external_phone','inner_phone','cris_website','personal_website',
    ];

    $data = $_POST['staff'] ?? [];
    foreach ($data as $post_id => $values) {
        $post_id = (int) $post_id;
        if (!$post_id || get_post_type($post_id) !== 'staff') continue;

        foreach ($values as $key => $val) {
            if (!in_array($key, $allowed_keys, true)) continue;

            if (in_array($key, $multi_keys, true)) {
                $clean = array_map('sanitize_text_field', (array)$val);
                update_post_meta($post_id, $key, $clean);
            } elseif (in_array($key, $url_keys, true)) {
                update_post_meta($post_id, $key, esc_url_raw($val));
            } elseif (in_array($key, $email_keys, true)) {
                update_post_meta($post_id, $key, sanitize_email($val));
            } else {
                update_post_meta($post_id, $key, sanitize_text_field($val));
            }
        }

        // staff_type: if not posted (no selection), save as empty array
        if (isset($data[$post_id]) && !isset($data[$post_id]['staff_type']) && array_key_exists('staff_type', $data[$post_id] ?? [])) {
            update_post_meta($post_id, 'staff_type', []);
        }
    }

    wp_redirect(admin_url('edit.php?post_type=staff&page=haifa-staff-bulk-edit&hsbe_saved=1'));
    exit;
}
// ──────────────────────────────────────────────────────────────

$fields = [
    'title_main'           => 'תואר',
    'first_name'           => 'שם פרטי',
    'last_name'            => 'שם משפחה',
    'status'               => 'סטטוס',
    'position'             => 'תפקיד',
    'staff_type'           => 'סוג סגל',
    'staff_academic_type'  => 'דרגה',
    'academic_degree_main' => 'דרגה אקדמית',
    'email'                => 'אימייל',
    'external_phone'       => 'טלפון חיצוני',
    'inner_phone'          => 'טלפון פנימי',
    'cris_website'         => 'קישור CRIS',
    'personal_website'     => 'אתר אישי',
];

$choices = [
    'title_main'           => ['' => '—', 'mr' => 'מר', 'mrs' => "גב'", 'dr' => 'ד"ר', 'prof' => "פרופ'", 'other' => 'אחר'],
    'status'               => ['' => '—', 'alive' => 'פעיל', 'retired' => 'גימלאות', 'dead' => 'נפטר'],
    'staff_type'           => ['academic' => 'אקדמי', 'administrative' => 'מנהלי'],
    'staff_academic_type'  => ['' => '—', 'senior' => 'בכיר', 'junior' => 'זוטר'],
    'academic_degree_main' => ['' => '—', 'professor' => 'פרופסור מן המניין', 'associate_professor' => 'פרופסור חבר', 'professor_emeritus' => 'פרופסור אמריטוס', 'senior_lecturer' => 'מרצה בכיר', 'other' => 'אחר'],
];

$multi        = ['staff_type'];
$url_fields   = ['cris_website', 'personal_website'];
$email_fields = ['email'];

$staff_posts = get_posts([
    'post_type'      => 'staff',
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'draft'],
    'meta_key'       => 'last_name',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
]);
?>
<div class="wrap" dir="rtl">
<h1>עדכון רשימת סגל</h1>

<?php if (!empty($_GET['hsbe_saved'])): ?>
<div class="notice notice-success is-dismissible"><p>הרשימה עודכנה בהצלחה.</p></div>
<?php endif; ?>

<div id="hsbe-field-selector">
    <strong>שדות להצגה:</strong>
    <div id="hsbe-toggles">
        <?php foreach ($fields as $key => $label): ?>
        <label class="hsbe-toggle" data-field="<?php echo esc_attr($key); ?>">
            <input type="checkbox" value="<?php echo esc_attr($key); ?>">
            <?php echo esc_html($label); ?>
        </label>
        <?php endforeach; ?>
    </div>
</div>

<div id="hsbe-search-wrap">
    <input type="text" id="hsbe-search" placeholder="חיפוש לפי שם..." autocomplete="off">
</div>

<div id="hsbe-list-wrap">
    <p id="hsbe-no-fields">יש לבחור שדות להצגה למעלה.</p>

    <form method="post" id="hsbe-form" style="display:none">
        <?php wp_nonce_field('hsbe_save', 'hsbe_nonce'); ?>

        <div id="hsbe-save-bar">
            <button type="submit" class="button button-primary button-large">שמור הכל</button>
            <span id="hsbe-count"><?php echo count($staff_posts); ?> אנשי סגל</span>
        </div>

        <div id="hsbe-staff-list">
        <?php foreach ($staff_posts as $p):
            $pid = $p->ID;
            $fn  = get_post_meta($pid, 'first_name', true);
            $ln  = get_post_meta($pid, 'last_name',  true);
        ?>
        <div class="hsbe-item">
            <div class="hsbe-item-header">
                <strong><?php echo esc_html(trim("$fn $ln") ?: $p->post_title); ?></strong>
                <a href="<?php echo get_edit_post_link($pid); ?>" class="hsbe-edit-link" target="_blank" title="עריכה מורחבת"><span class="dashicons dashicons-edit"></span></a>
            </div>
            <div class="hsbe-fields">
                <?php foreach ($fields as $key => $label):
                    $is_multi = in_array($key, $multi);
                    $val      = get_post_meta($pid, $key, true);
                    if ($key === 'title_main') {
                        $legacy_map = [
                            'professor'           => 'mr',
                            'associate_professor' => 'mrs',
                            'assistant_professor' => 'dr',
                            'lecturer'            => 'prof',
                        ];
                        if (isset($legacy_map[$val])) $val = $legacy_map[$val];
                    }
                    $arr_val  = $is_multi ? (array)($val ?: []) : [];
                ?>
                <div class="hsbe-cell" data-field="<?php echo esc_attr($key); ?>">
                    <label><?php echo esc_html($label); ?></label>
                    <?php if (isset($choices[$key])): ?>
                        <select name="staff[<?php echo $pid; ?>][<?php echo $key; ?>]<?php echo $is_multi ? '[]' : ''; ?>"
                                <?php echo $is_multi ? 'multiple size="2"' : ''; ?>>
                            <?php foreach ($choices[$key] as $cv => $cl): ?>
                            <option value="<?php echo esc_attr($cv); ?>"
                                <?php echo $is_multi ? (in_array($cv, $arr_val) ? 'selected' : '') : selected($val, $cv, false); ?>>
                                <?php echo esc_html($cl); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else:
                        $type = in_array($key, $url_fields) ? 'url' : (in_array($key, $email_fields) ? 'email' : 'text');
                    ?>
                        <input type="<?php echo $type; ?>"
                               name="staff[<?php echo $pid; ?>][<?php echo $key; ?>]"
                               value="<?php echo esc_attr($val); ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <div id="hsbe-save-bar-bottom">
            <button type="submit" class="button button-primary button-large">שמור הכל</button>
        </div>
    </form>
</div>
</div>

<style>
#hsbe-field-selector { background:#fff; border:1px solid #ddd; border-radius:4px; padding:14px 18px; margin:18px 0; display:flex; align-items:flex-start; gap:12px; }
#hsbe-field-selector strong { white-space:nowrap; padding-top:6px; }
#hsbe-toggles { display:flex; flex-wrap:wrap; gap:8px; }
.hsbe-toggle { display:inline-flex; align-items:center; gap:5px; background:#f0f0f1; border:1px solid #c3c4c7; border-radius:3px; padding:4px 10px; cursor:pointer; font-size:13px; user-select:none; }
.hsbe-toggle input { margin:0; }
.hsbe-toggle.active { background:#2271b1; color:#fff; border-color:#2271b1; }

#hsbe-staff-list { display:flex; flex-direction:column; gap:12px; margin-top:16px; }
.hsbe-item { background:#fff; border:1px solid #ddd; border-radius:4px; overflow:hidden; }
.hsbe-item-header { background:#f6f7f7; border-bottom:1px solid #ddd; padding:8px 14px; display:flex; align-items:center; gap:10px; }
.hsbe-item-header strong { font-size:14px; }
.hsbe-edit-link { color:#999; font-size:12px; }
.hsbe-fields { display:flex; flex-wrap:wrap; }
.hsbe-cell { display:none; box-sizing:border-box; padding:10px 12px; border-left:1px solid #f0f0f0; }
.hsbe-cell.hsbe-visible { display:block; }
.hsbe-cell label { display:block; font-size:11px; color:#888; margin-bottom:4px; font-weight:600; text-transform:uppercase; }
.hsbe-cell input, .hsbe-cell select { width:100%; font-size:13px; }
.hsbe-cell select[multiple] { resize:none; }

#hsbe-save-bar { display:flex; align-items:center; gap:14px; padding:12px 0; border-bottom:1px solid #ddd; margin-bottom:8px; }
#hsbe-save-bar-bottom { padding:16px 0; }
#hsbe-count { color:#888; font-size:13px; }
#hsbe-no-fields { color:#999; }

#hsbe-search-wrap { margin-bottom:14px; }
#hsbe-search { width:280px; font-size:14px; padding:6px 10px; border:1px solid #c3c4c7; border-radius:3px; }
</style>

<script>
(function(){
    const STORAGE_KEY = 'haifa_bulk_edit_fields';
    const list        = document.getElementById('hsbe-staff-list');
    const form        = document.getElementById('hsbe-form');
    const noFields    = document.getElementById('hsbe-no-fields');
    const toggles     = document.querySelectorAll('.hsbe-toggle');

    function loadSaved() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; } catch(e) { return []; }
    }
    function saveCurrent(active) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(active));
    }

    function updateList(active) {
        // Show/hide form
        const any = active.length > 0;
        form.style.display    = any ? '' : 'none';
        noFields.style.display = any ? 'none' : '';

        if (!any) return;

        // Cell width: max 6 per row
        const cols  = Math.min(active.length, 6);
        const width = (100 / cols).toFixed(4) + '%';

        document.querySelectorAll('.hsbe-cell').forEach(cell => {
            const on = active.includes(cell.dataset.field);
            cell.classList.toggle('hsbe-visible', on);
            cell.style.width = on ? width : '';
        });
    }

    // Init
    const saved = loadSaved();
    const active = [];

    toggles.forEach(label => {
        const input = label.querySelector('input');
        if (saved.includes(input.value)) {
            input.checked = true;
            label.classList.add('active');
            active.push(input.value);
        }
        label.addEventListener('click', function() {
            setTimeout(() => {
                label.classList.toggle('active', input.checked);
                const current = [...document.querySelectorAll('.hsbe-toggle input:checked')].map(i => i.value);
                saveCurrent(current);
                updateList(current);
            }, 0);
        });
    });

    document.getElementById('hsbe-search').addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.hsbe-item').forEach(item => {
            const name = item.querySelector('.hsbe-item-header strong').textContent.toLowerCase();
            item.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    });

    updateList(active);
})();
</script>