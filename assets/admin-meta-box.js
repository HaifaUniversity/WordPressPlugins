(function($) {
    'use strict';

    // ── Tabs ──────────────────────────────────────────────────────────────────
    $(document).on('click', '.hsv2-tab-btn', function() {
        var tab = $(this).data('tab');
        $(this).closest('.hsv2-tabs').find('.hsv2-tab-btn').removeClass('active');
        $(this).addClass('active');
        $(this).closest('.hsv2-tabs').find('.hsv2-tab-panel').removeClass('active');
        $('#hsv2-tab-' + tab).addClass('active');
        localStorage.setItem('hsv2_active_tab', tab);
    });

    // Restore last active tab on load
    var savedTab = localStorage.getItem('hsv2_active_tab');
    if (savedTab && $('button[data-tab="' + savedTab + '"]').length && !$('button[data-tab="' + savedTab + '"]').is(':hidden')) {
        $('button[data-tab="' + savedTab + '"]').trigger('click');
    }

    // ── Image picker ──────────────────────────────────────────────────────────
    var mediaFrame;

    $('#hsv2-select-image').on('click', function(e) {
        e.preventDefault();
        if (mediaFrame) { mediaFrame.open(); return; }
        mediaFrame = wp.media({
            title:    'בחר תמונה',
            button:   { text: 'השתמש בתמונה' },
            multiple: false
        });
        mediaFrame.on('select', function() {
            var attachment = mediaFrame.state().get('selection').first().toJSON();

            var maxBytes = 200 * 1024;
            var filesize = attachment.filesizeInBytes || 0;
            var width    = attachment.width  || 0;
            var height   = attachment.height || 0;

            if (width < 360 || height < 360) {
                alert('התמונה שנבחרה קטנה מדי.\nגודל מינימלי: 360×360 פיקסלים.\nהתמונה הנוכחית: ' + width + '×' + height + ' פיקסלים.');
                return;
            }
            if (filesize > maxBytes) {
                var kb = Math.round(filesize / 1024);
                alert('התמונה גדולה מדי.\nגודל מקסימלי: 200KB.\nגודל הקובץ: ' + kb + 'KB.');
                return;
            }

            $('#hsv2-image-id').val(attachment.id);
            $('#hsv2-image-preview').html('<img src="' + attachment.url + '" style="max-width:180px;height:auto;border-radius:4px;">');
            $('#hsv2-remove-image').show();
        });
        mediaFrame.open();
    });

    $('#hsv2-remove-image').on('click', function() {
        $('#hsv2-image-id').val('');
        $('#hsv2-image-preview').html('');
        $(this).hide();
    });

    // ── Socials repeater ──────────────────────────────────────────────────────
    function getNextIndex() {
        var max = -1;
        $('#hsv2-socials-repeater .hsv2-repeater-row').each(function() {
            var name = $(this).find('select').attr('name') || '';
            var m = name.match(/\[(\d+)\]/);
            if (m) max = Math.max(max, parseInt(m[1]));
        });
        return max + 1;
    }

    $('#hsv2-add-social').on('click', function() {
        var tmpl = $('#hsv2-social-row-template').html();
        var idx  = getNextIndex();
        var row  = tmpl.replace(/__IDX__/g, idx);
        $('#hsv2-socials-repeater .hsv2-no-rows').remove();
        $('#hsv2-socials-repeater').append(row);
    });

    $(document).on('click', '.hsv2-remove-row', function() {
        $(this).closest('.hsv2-repeater-row').remove();
        if ($('#hsv2-socials-repeater .hsv2-repeater-row').length === 0) {
            $('#hsv2-socials-repeater').html('<p class="hsv2-no-rows">אין רשומות. לחץ על "הוסף שורה".</p>');
        }
    });

    // ── CRIS required toggle ──────────────────────────────────────────────────
    // Makes CRIS field required only for senior academic staff.
    function toggleCrisRequired() {
        var isSenior = $('input[name="staff_academic_type"]:checked').val() === 'senior';
        var cris     = $('input[name="cris_website"]');
        if (isSenior) {
            cris.attr('required', true);
            cris.closest('.hsv2-row').find('label').addClass('hsv2-required');
        } else {
            cris.removeAttr('required');
            cris.closest('.hsv2-row').find('label').removeClass('hsv2-required');
        }
    }

    $('input[name="staff_academic_type"]').on('change', toggleCrisRequired);
    toggleCrisRequired();

    // ── Remove required fields when status is "dead" ──────────────────────────
    function toggleDeadFields() {
        var isDead = $('select[name="status"]').val() === 'dead';
        var emailField = $('input[name="email"]');
        var crisField  = $('input[name="cris_website"]');

        if (isDead) {
            emailField.removeAttr('required');
            emailField.closest('.hsv2-row').find('label .req').hide();
            crisField.removeAttr('required');
            crisField.closest('.hsv2-row').find('label').removeClass('hsv2-required');
        } else {
            emailField.attr('required', true);
            emailField.closest('.hsv2-row').find('label .req').show();
            toggleCrisRequired();
        }
    }

    $('select[name="status"]').on('change', toggleDeadFields);
    toggleDeadFields();

    // ── Save validation ───────────────────────────────────────────────────────
    // Intercepts publish/save clicks to validate required fields across all tabs.
    // Switches to the offending tab and alerts with a list of missing fields.
    $('#publish, #save-post').on('click', function(e) {
        if (typeof tinyMCE !== 'undefined') {
            tinyMCE.triggerSave();
        }

        var invalid = [];



        $('[required]').each(function() {
            // skip only if the entire tab is disabled (e.g. details2 when not academic)
            var tabPanel = $(this).closest('.hsv2-tab-panel');
            if (tabPanel.length) {
                var tabId = tabPanel.attr('id').replace('hsv2-tab-', '');
                if ($('button[data-tab="' + tabId + '"]').is(':hidden')) return;
            }

            var empty = false;

            if ($(this).is(':radio')) {
                var name = $(this).attr('name');
                if ($.grep(invalid, function(f) { return f.name === name; }).length > 0) return;
                empty = $('input[name="' + name + '"]:checked').length === 0;
            } else if ($(this).is(':checkbox')) {
                var name = $(this).attr('name');
                if ($.grep(invalid, function(f) { return f.name === name; }).length > 0) return;
                var escapedName = name.replace(/\[/g, '\\[').replace(/\]/g, '\\]');
                empty = $('input[name="' + escapedName + '"]:checked').length === 0;
            } else {
                empty = !$(this).val() || $(this).val().trim() === '';
            }

            if (empty) {
                var label = $(this).closest('.hsv2-row, .hsv2-repeater-row').find('label').first().text().trim();
                if (!label) label = $(this).attr('name') || 'שדה לא ידוע';
                var tabId2 = tabPanel.length ? tabPanel.attr('id').replace('hsv2-tab-', '') : '';
                invalid.push({ label: label, tabId: tabId2, name: $(this).attr('name') });
            }
        });


        if (invalid.length > 0) {
            e.preventDefault();

            // Switch to the tab of the first invalid field
            if (invalid[0].tabId) {
                $('button[data-tab="' + invalid[0].tabId + '"]').trigger('click');
            }

            // Alert all missing fields
            var msg = 'יש למלא את השדות הבאים:\n\n';
            $.each(invalid, function(i, f) {
                msg += '• ' + f.label;
                if (f.tab) msg += ' (טאב: ' + f.tab + ')';
                msg += '\n';
            });
            alert(msg);
        }
    });

    // ── Staff type checkbox validation ────────────────────────────────────────
    // Ensures at least one staff_type checkbox is checked before form submit.
    document.getElementById('post')?.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('input[name="staff_type[]"]:checked');
        if (checked.length === 0) {
            e.preventDefault();
            const first = document.querySelector('input[name="staff_type[]"]');
            if (first) {
                first.focus();
                first.closest('.hsv2-checkboxes').style.outline = '2px solid #d63638';
                alert('יש לבחור לפחות סוג תפקיד אחד.');
            }
        }
    });


    // ── Conditional field pairs ───────────────────────────────────────────────────
    var conditionalFields = [
        {
            controller: 'select[name="title_main"]',
            controlled: 'input[name="title_other"]',
            showWhen:   ['other'],
            required:   true
        },
        {
            controller: 'select[name="academic_degree_main"]',
            controlled: 'input[name="academic_degree_other"]',
            showWhen:   ['other'],
            required:   true
        },
        {
            controller: 'select[name="status"]',
            controlled: 'input[name="date_of_death"]',
            showWhen:   ['dead'],
            required:   false
        }
    ];

    function applyConditionalField(rule) {
        var currentVal = $(rule.controller).val() || '';
        var input      = $(rule.controlled);
        var row        = input.closest('.hsv2-row');
        var shouldShow = rule.showWhen.indexOf(currentVal) !== -1;

        if (shouldShow) {
            row.show();
            if (rule.required) {
                input.attr('required', true);
                var lbl = row.find('label');
                lbl.addClass('hsv2-required');
                row.find('label').addClass('hsv2-required');
            }
        } else {
            row.hide();
            input.removeAttr('required');
            row.find('label').removeClass('hsv2-required');
        }
    }

    function initConditionalFields() {
        $.each(conditionalFields, function(i, rule) {
            applyConditionalField(rule);
            $(document).on('change', rule.controller, function() {
                applyConditionalField(rule);
            });
        });
    }

    initConditionalFields();

    // ── Show/hide academic fields based on staff_type ─────────────────────────────
    function toggleAcademicFields() {
        var isAcademic = $('input[name="staff_type[]"][value="academic"]').is(':checked');
        var rows = [
            $('input[name="staff_academic_type"]').closest('.hsv2-row'),
            $('select[name="academic_degree_main"]').closest('.hsv2-row')
        ];

        if (isAcademic) {
            $('button[data-tab="details2"]').show();
            rows.forEach(function(row) { row.show(); });
            $('input[name="staff_academic_type"]').attr('required', true);
        } else {
            rows.forEach(function(row) { row.hide(); });
            $('input[name="staff_academic_type"]').removeAttr('required'); // ← remove required when hidden
            $('input[name="staff_academic_type"]').prop('checked', false);
            $('select[name="academic_degree_main"]').val('');
            $('input[name="academic_degree_other"]').val('');
            if ($('button[data-tab="details2"]').hasClass('active')) {
                $('button[data-tab="details"]').trigger('click');
            }
            $('button[data-tab="details2"]').hide();
            toggleCrisRequired();
        }

        applyConditionalField({
            controller: 'select[name="academic_degree_main"]',
            controlled: 'input[name="academic_degree_other"]',
            showWhen:   ['other'],
            required:   true
        });
    }

    $('input[name="staff_type[]"]').off('change').on('change', toggleAcademicFields);

})(jQuery);



// ── Custom title options ───────────────────────────────────────────────────────
(function($) {
    const titleSelect = $('#hsv2-title-main-select');
    if (!titleSelect.length) return;

    let prevTitleVal = titleSelect.val();

    titleSelect.on('change', function() {
        if ($(this).val() === '__add_new__') {
            $(this).val(prevTitleVal);
            openTitleOptionsModal();
        } else {
            prevTitleVal = $(this).val();
        }
    });

    function openTitleOptionsModal() {
        if ($('#hsv2-title-modal').length) {
            $('#hsv2-title-modal').show();
            refreshModalList();
            return;
        }
        $('body').append(`
            <div id="hsv2-title-modal">
                <div id="hsv2-title-modal-box">
                    <h3>ניהול תארים מותאמים</h3>
                    <div id="hsv2-title-modal-add">
                        <input type="text" id="hsv2-title-new-label" placeholder="תואר חדש...">
                        <input type="text" id="hsv2-title-new-label-en" placeholder="English title..." style="direction:ltr">
                        <button type="button" id="hsv2-title-add-btn" class="button button-primary">הוסף</button>
                    </div>
                    <div id="hsv2-title-modal-list"></div>
                    <div id="hsv2-title-modal-footer">
                        <button type="button" id="hsv2-title-modal-close" class="button">סגור</button>
                    </div>
                </div>
            </div>
        `);

        $('#hsv2-title-modal').on('click', function(e) {
            if ($(e.target).is('#hsv2-title-modal')) $(this).hide();
        });
        $('#hsv2-title-modal-close').on('click', function() {
            $('#hsv2-title-modal').hide();
        });
        $('#hsv2-title-add-btn').on('click', function() {
            const label    = $('#hsv2-title-new-label').val().trim();
            const label_en = $('#hsv2-title-new-label-en').val().trim();
            if (!label) return;
            $(this).prop('disabled', true);
            $.post(ajaxurl, {
                action: 'haifa_add_title_option',
                nonce:  haifaStaffTitleOptions.nonce,
                label, label_en
            }, (res) => {
                $('#hsv2-title-add-btn').prop('disabled', false);
                if (!res.success) { alert(res.data); return; }
                addOptionToSelect(res.data.value, res.data.label);
                haifaStaffTitleOptions.options[haifaStaffTitleOptions.options.length - 1].label_en = res.data.label_en;
                prevTitleVal = res.data.value;
                $('#hsv2-title-new-label').val('');
                $('#hsv2-title-new-label-en').val('');
                $('#hsv2-title-modal').hide();
            });
        });

        refreshModalList();
    }

    function refreshModalList() {
        const list = $('#hsv2-title-modal-list');
        list.empty();
        const opts = haifaStaffTitleOptions.options;
        if (!opts || !opts.length) {
            list.html('<p class="hsv2-no-custom">אין תארים מותאמים עדיין.</p>');
            return;
        }
        opts.forEach(function(opt) {
            list.append(
                $('<div class="hsv2-custom-opt-row">').attr('data-value', opt.value).append(
                    $('<input type="text" class="hsv2-custom-opt-label">').val(opt.label),
                    $('<input type="text" class="hsv2-custom-opt-label-en">').val(opt.label_en || '').attr('placeholder', 'English...').css('direction', 'ltr'),
                    $('<button type="button" class="button hsv2-custom-opt-save">').text('שמור'),
                    $('<button type="button" class="button-link-delete hsv2-custom-opt-delete">').text('מחק')
                )
            );
        });

        list.on('click', '.hsv2-custom-opt-save', function() {
            const row   = $(this).closest('.hsv2-custom-opt-row');
            const value = row.data('value');
            const label    = row.find('.hsv2-custom-opt-label').val().trim();
            const label_en = row.find('.hsv2-custom-opt-label-en').val().trim();
            if (!label) return;
            $(this).prop('disabled', true);
            const btn = $(this);
            $.post(ajaxurl, {
                action: 'haifa_update_title_option',
                nonce:  haifaStaffTitleOptions.nonce,
                value, label, label_en
            }, (res) => {
                btn.prop('disabled', false);
                if (!res.success) { alert(res.data); return; }
                haifaStaffTitleOptions.options = haifaStaffTitleOptions.options.map(
                    o => o.value === value ? { value, label, label_en } : o
                );
                titleSelect.find(`option[value="${value}"]`).text(label);
            });
        });

        list.on('click', '.hsv2-custom-opt-delete', function() {
            const row   = $(this).closest('.hsv2-custom-opt-row');
            const value = row.data('value');
            $.post(ajaxurl, {
                action: 'haifa_delete_title_option',
                nonce:  haifaStaffTitleOptions.nonce,
                value
            }, (res) => {
                if (!res.success) { alert(res.data); return; }
                haifaStaffTitleOptions.options = haifaStaffTitleOptions.options.filter(o => o.value !== value);
                titleSelect.find(`option[value="${value}"]`).remove();
                refreshModalList();
            });
        });
    }

    function addOptionToSelect(value, label) {
        $('<option>').val(value).text(label)
            .insertBefore(titleSelect.find('option[value="__add_new__"]'));
        haifaStaffTitleOptions.options.push({ value, label });
        titleSelect.val(value);
        prevTitleVal = value;
        titleSelect.trigger('change');
        // if title_other was required, clear and hide it
        const otherRow   = $('#hsv2-row-title-other');
        const otherInput = otherRow.find('input');
        otherRow.hide();
        otherInput.removeAttr('required').val('');
    }

})(jQuery);