/**
 * Haifa Staff — Remote Picker for Elementor Editor
 *
 * When an editor opens the "סגל מרוחק" repeater:
 * - Selecting a faculty (source_url SELECT) triggers a fetch to that site's REST API
 * - In "by_tag" mode: populates a native <select> with available tags
 * - In "by_slug" mode: populates a native <select multiple> with staff names
 * - Selections are written back to hidden Elementor text inputs so they save normally
 */
(function ($) {
    'use strict';

    var WIDGET_NAME = 'haifa_staff_grid';

    function debounce(fn, delay) {
        var timer;
        return function () { clearTimeout(timer); timer = setTimeout(fn, delay); };
    }

    function restBase(url) {
        if (!url) return '';
        return url.replace(/\/+$/, '') + '/wp-json/haifa-staff/v1/';
    }

    function setupRow($row) {
        if (!$row.find('[data-setting="fetch_mode"]').length) return;
        if ($row.data('haifa-remote-init')) return;
        $row.data('haifa-remote-init', true);

        var $sourceSelect = $row.find('select[data-setting="source_url"]');
        var $modeSelect   = $row.find('select[data-setting="fetch_mode"]');
        var $tagCtrl      = $row.find('.elementor-control-filter_tag');
        var $slugCtrl     = $row.find('.elementor-control-filter_slugs');
        var $tagInput     = $row.find('input[data-setting="filter_tag"]');
        var $slugInput    = $row.find('input[data-setting="filter_slugs"]');

        if (!$sourceSelect.length) return;

        // Inject native pickers
        var $tagPicker  = $('<select class="haifa-remote-picker" style="width:100%;margin-top:6px;padding:4px 8px;border:1px solid #ccc;border-radius:3px;font-size:13px;"><option value="">— בחר תגית —</option></select>');
        var $slugPicker = $('<select class="haifa-remote-picker" multiple style="width:100%;margin-top:6px;padding:4px 8px;border:1px solid #ccc;border-radius:3px;font-size:13px;min-height:120px;"></select>');
        var $slugNote   = $('<p style="font-size:11px;color:#888;margin:3px 0 0;">Ctrl+לחיצה לבחירה מרובה</p>');

        $tagInput.after($tagPicker);
        $slugInput.after($slugNote).after($slugPicker);

        // Sync pickers → Elementor hidden inputs
        $tagPicker.on('change', function () {
            $tagInput.val($(this).val() || '').trigger('input').trigger('change');
        });
        $slugPicker.on('change', function () {
            var vals = Array.from(this.selectedOptions).map(function(o){ return o.value; });
            $slugInput.val(vals.join(',')).trigger('input').trigger('change');
        });

        function updateVisibility() {
            var mode = $modeSelect.val();
            $tagCtrl.toggle(mode === 'by_tag');
            $slugCtrl.toggle(mode === 'by_slug');
        }

        function loadTags() {
            var base = restBase($sourceSelect.val());
            if (!base) return;
            var currentVal = $tagInput.val() || '';
            $tagPicker.prop('disabled', true).find('option:not([value=""])').remove();
            $tagPicker.find('option[value=""]').text('טוען תגיות...');
            $.getJSON(base + 'tags')
                .done(function(tags) {
                    $tagPicker.find('option[value=""]').text('— בחר תגית —');
                    $.each(tags, function(i, t) {
                        $tagPicker.append(new Option(t.name + ' (' + t.count + ')', t.slug, false, t.slug === currentVal));
                    });
                    if (currentVal) $tagPicker.val(currentVal);
                    $tagPicker.prop('disabled', false);
                })
                .fail(function() {
                    $tagPicker.find('option[value=""]').text('— שגיאה בטעינה —');
                    $tagPicker.prop('disabled', false);
                });
        }

        function loadStaff() {
            var base = restBase($sourceSelect.val());
            if (!base) return;
            var currentVals = ($slugInput.val() || '').split(',').map(function(s){ return s.trim(); }).filter(Boolean);
            $slugPicker.prop('disabled', true).empty().append('<option disabled>טוען רשימת סגל...</option>');
            $.getJSON(base + 'staff')
                .done(function(staff) {
                    $slugPicker.empty();
                    $.each(staff, function(i, p) {
                        var name = ((p.title_main_label ? p.title_main_label + ' ' : '') + (p.first_name || '') + ' ' + (p.last_name || '')).trim();
                        var sel  = currentVals.indexOf(p.slug) > -1;
                        $slugPicker.append(new Option(name, p.slug, sel, sel));
                    });
                    $slugPicker.prop('disabled', false);
                })
                .fail(function() {
                    $slugPicker.empty().append('<option disabled>שגיאה בטעינת הסגל</option>');
                    $slugPicker.prop('disabled', false);
                });
        }

        function loadForMode() {
            if (!$sourceSelect.val()) return;
            var mode = $modeSelect.val();
            if (mode === 'by_tag')  loadTags();
            if (mode === 'by_slug') loadStaff();
        }

        $sourceSelect.on('change', loadForMode);
        $modeSelect.on('change', function() { updateVisibility(); loadForMode(); });

        updateVisibility();
        if ($sourceSelect.val()) loadForMode();
    }

    function observePanel($panel) {
        function scanRows() {
            $panel.find('.elementor-repeater-fields').each(function() { setupRow($(this)); });
        }
        scanRows();
        var observer = new MutationObserver(debounce(scanRows, 200));
        observer.observe($panel[0], { childList: true, subtree: true });
    }

    $(window).on('elementor:init', function() {
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model) {
            if (model.get('widgetType') !== WIDGET_NAME) return;
            setTimeout(function() { observePanel(panel.$el); }, 400);
        });
    });

})(jQuery);
