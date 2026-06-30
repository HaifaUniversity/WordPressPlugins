(function () {
    'use strict';

    // ── Core plugin logic (editor-instance scoped) ────────────────────────────

    function initMentionOnEditor(editor) {

        var mentionActive = false;
        var searchTerm    = '';
        var dropdown      = null;
        var activeIndex   = 0;
        var results       = [];
        var debounceTimer = null;

        // ── Dropdown ──────────────────────────────────────────────────────────

        function buildDropdown(items) {
            destroyDropdown();
            if (!items.length) return;

            results     = items;
            activeIndex = 0;

            dropdown    = document.createElement('div');
            dropdown.id = 'haifa-mention-dd';
            dropdown.setAttribute('style', [
                'position:fixed', 'z-index:999999', 'right:auto', 'left:auto', 'background:#fff',
                'border:1px solid #d0d7de', 'border-radius:6px',
                'box-shadow:0 6px 18px rgba(0,0,0,.16)',
                'min-width:270px', 'max-height:290px', 'overflow-y:auto',
                'direction:rtl', 'font-family:-apple-system,sans-serif',
                'padding:4px 0'
            ].join(';'));

            items.forEach(function (item, i) {
                var row       = document.createElement('div');
                row.className = 'hm-row';
                row.dataset.i = i;
                row.setAttribute('style',
                    'padding:9px 16px;cursor:pointer;font-size:14px;color:#1f2328;' +
                    'border-bottom:1px solid #f3f4f6;white-space:nowrap;transition:background .12s;'
                );
                row.textContent = item.name;
                row.addEventListener('mouseenter', function () { highlight(i); });
                row.addEventListener('mousedown',  function (e) { e.preventDefault(); commit(i); });
                dropdown.appendChild(row);
            });

            document.body.appendChild(dropdown);
            reposition();
            highlight(0);
        }

        function highlight(i) {
            activeIndex = i;
            if (!dropdown) return;
            dropdown.querySelectorAll('.hm-row').forEach(function (el, idx) {
                el.style.background = idx === i ? '#dbeafe' : '#fff';
            });
        }

        function reposition() {
            if (!dropdown) return;
            var rect = caretRect();
            if (!rect) return;

            // Vertical
            var spaceBelow = window.innerHeight - rect.bottom;
            if (spaceBelow < 160 && rect.top > 160) {
                dropdown.style.top    = '';
                dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
            } else {
                dropdown.style.bottom = '';
                dropdown.style.top    = (rect.bottom + 4) + 'px';
            }

            // Horizontal — anchor to right edge of caret, flip if too close to left edge
            var fromRight = window.innerWidth - rect.left;
            if (fromRight < 280) fromRight = 280; // minimum margin from right edge
            dropdown.style.left  = '';
            dropdown.style.right = fromRight + 'px';
        }

        function destroyDropdown() {
            if (dropdown) { dropdown.remove(); dropdown = null; }
            results = []; activeIndex = 0;
        }

        // ── Caret position (iframe-aware) ─────────────────────────────────────

        function caretRect() {
            var iOffsetTop  = 0;
            var iOffsetLeft = 0;

            var editorContainer = editor.getContainer ? editor.getContainer() : null;
            var iframe = editorContainer ? editorContainer.querySelector('iframe') : null;
            if (!iframe && editor.iframeElement) iframe = editor.iframeElement;

            if (iframe) {
                var iRect    = iframe.getBoundingClientRect();
                iOffsetTop   = iRect.top;
                iOffsetLeft  = iRect.left;
            }

            var iDoc = editor.getDoc();
            var sel  = iDoc.getSelection && iDoc.getSelection();
            if (!sel || !sel.rangeCount) return null;
            var r = sel.getRangeAt(0).getBoundingClientRect();
            if (!r || (r.width === 0 && r.height === 0)) return null;
            return {
                top:    iOffsetTop  + r.top,
                left:   iOffsetLeft + r.left,
                bottom: iOffsetTop  + r.bottom
            };
        }

        // ── Insert link ───────────────────────────────────────────────────────

        function commit(i) {
            var item = results[i];
            if (!item) return;

            var sel    = editor.selection;
            var rng    = sel.getRng();
            var node   = rng.startContainer;
            var offset = rng.startOffset;

            if (node.nodeType === 3) {
                var before  = node.textContent.substring(0, offset);
                var atIndex = before.lastIndexOf('@');
                if (atIndex !== -1) {
                    var newRng = rng.cloneRange();
                    newRng.setStart(node, atIndex);
                    newRng.setEnd(node, offset);
                    sel.setRng(newRng);
                }
            }

            editor.insertContent(
                '[staff_link id="' + item.id + '"]' +
                tinymce.DOM.encode(item.name) +
                '[/staff_link]\u00a0'
            );

            mentionActive = false;
            searchTerm    = '';
            destroyDropdown();
        }

        // ── AJAX ──────────────────────────────────────────────────────────────

        function fetchResults(term) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                if (typeof haifaStaffMention === 'undefined') return;
                jQuery.ajax({
                    url:  haifaStaffMention.ajaxUrl,
                    type: 'GET',
                    data: {
                        action: 'haifa_staff_mention_search',
                        term:   term,
                        nonce:  haifaStaffMention.nonce
                    },
                    success: function (data) {
                        if (!mentionActive) return;
                        buildDropdown(data);
                    },
                    error: destroyDropdown
                });
            }, 180);
        }

        // ── Helpers ───────────────────────────────────────────────────────────

        function textBeforeCursor() {
            var rng  = editor.selection.getRng();
            var node = rng.startContainer;
            if (node.nodeType !== 3) return '';

            // Collect text from current node up to cursor
            var text = node.textContent.substring(0, rng.startOffset);

            // If @ already found here, no need to walk further
            if (text.indexOf('@') !== -1) return text;

            // Walk backwards through previous siblings to find @
            var sibling = node.previousSibling;
            while (sibling) {
                var sibText = sibling.textContent || '';
                text = sibText + text;
                if (text.indexOf('@') !== -1) return text;
                sibling = sibling.previousSibling;
            }

            return text;
        }

        var SKIP_KEYS = ['ArrowUp','ArrowDown','ArrowLeft','ArrowRight',
                         'Enter','Escape','Shift','Control','Alt','Meta','Tab',
                         'CapsLock','PageUp','PageDown','Home','End'];

        // ── Editor events ─────────────────────────────────────────────────────

        editor.on('keydown', function (e) {
            if (!mentionActive || !results.length) return;
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    highlight((activeIndex + 1) % results.length);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    highlight((activeIndex - 1 + results.length) % results.length);
                    break;
                case 'Enter':
                case 'Tab':
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    commit(activeIndex);
                    break;
                case 'Escape':
                    mentionActive = false; searchTerm = ''; destroyDropdown();
                    break;
            }
        }, true);

        editor.on('keyup', function (e) {
            if (SKIP_KEYS.indexOf(e.key) !== -1) return;

            var text    = textBeforeCursor();
            var atIndex = text.lastIndexOf('@');

            if (atIndex === -1) {
                if (mentionActive) { mentionActive = false; searchTerm = ''; destroyDropdown(); }
                return;
            }

            // @ must be at line start or preceded by whitespace (avoids email addresses)
            var charBefore = atIndex > 0 ? text.charAt(atIndex - 1) : ' ';
            if (charBefore !== ' ' && charBefore !== '\n' && charBefore !== '\t' && atIndex !== 0) {
                if (mentionActive) { mentionActive = false; searchTerm = ''; destroyDropdown(); }
                return;
            }

            var query = text.substring(atIndex + 1);
            mentionActive = true;
            searchTerm    = query;
            fetchResults(query);
        });

        editor.on('blur', function () {
            setTimeout(function () {
                if (!dropdown || !dropdown.matches(':hover')) {
                    destroyDropdown();
                    mentionActive = false; searchTerm = '';
                }
            }, 200);
        });

        editor.on('remove', function () {
            destroyDropdown();
        });

        document.addEventListener('click', function (e) {
            if (dropdown && !dropdown.contains(e.target)) {
                destroyDropdown(); mentionActive = false; searchTerm = '';
            }
        });
    }

    // ── Register as TinyMCE plugin (covers wp_editor / classic editor) ────────

    if (typeof tinymce !== 'undefined') {
        tinymce.PluginManager.add('haifa_staff_mention', function (editor) {
            initMentionOnEditor(editor);
        });

        // Hook into ALL dynamically created TinyMCE instances.
        // This covers Elementor's Text Editor widget and any other dynamic editor
        // that bypasses the mce_external_plugins WordPress filter.
        tinymce.on('AddEditor', function (e) {
            var ed = e.editor;
            // Skip if already initialised via mce_external_plugins
            if (ed.plugins && ed.plugins.haifa_staff_mention) return;
            ed.on('init', function () {
                initMentionOnEditor(ed);
            });
        });
    }

    // Attach to any editors already initialized before this script loaded
    if (typeof tinymce !== 'undefined') {
        tinymce.editors.forEach(function (ed) {
            if (ed.plugins && ed.plugins.haifa_staff_mention) return;
            if (ed.initialized) {
                initMentionOnEditor(ed);
            } else {
                ed.on('init', function () { initMentionOnEditor(ed); });
            }
        });
    }

    // Re-check when Elementor opens a widget panel (user clicks a widget)
    if (typeof elementor !== 'undefined') {
        elementor.channels.editor.on('section:activated', function () {
            setTimeout(function () {
                tinymce.editors.forEach(function (ed) {
                    if (ed.plugins && ed.plugins.haifa_staff_mention) return;
                    if (ed._haifaMentionAttached) return;
                    ed._haifaMentionAttached = true;
                    if (ed.initialized) {
                        initMentionOnEditor(ed);
                    } else {
                        ed.on('init', function () { initMentionOnEditor(ed); });
                    }
                });
            }, 300);
        });
    }

})();