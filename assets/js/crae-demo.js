/**
 * Interactive [clicreport_demo] Live UI + Developer Code Showcase
 * Connects directly to window.ClicReportAPI (/wp-json/clicreport/v1/reports/generate).
 */
(function (window, document) {
    'use strict';

    var PRESETS = {
        t_score: {
            label: 'A1: BASC-3 (T-Score)',
            template_name: 'report-basc-3-social-emotional',
            first_name: 'Alex',
            gender: 'she/her',
            informant_one: 'Ms. Rivera',
            informant_one_relationship: 'Teacher',
            informant_two: 'Jordan',
            informant_two_relationship: 'Mother',
            active_raters: 2,
            fields: [
                { key: 'hyperactivity_0', label: 'Hyperactivity (Rater 1 T-Score)', value: 74 },
                { key: 'hyperactivity_1', label: 'Hyperactivity (Rater 2 T-Score)', value: 66 },
                { key: 'depression_0', label: 'Depression (Rater 1 T-Score)', value: 52 }
            ]
        },
        dual_standard_scaled: {
            label: 'A2: WISC-V (Standard/Scaled)',
            template_name: 'report-wisc-v',
            first_name: 'Maya',
            gender: 'she/her',
            informant_one: 'Dr. Patel',
            informant_one_relationship: 'School Psychologist',
            informant_two: '',
            informant_two_relationship: '',
            active_raters: 1,
            fields: [
                { key: 'fsiq', label: 'Full Scale IQ (Standard Score, M=100)', value: 108 },
                { key: 'vci', label: 'Verbal Comprehension Index (Standard)', value: 114 },
                { key: 'similarities', label: 'Similarities (Scaled, M=10)', value: 13 }
            ]
        },
        clinical_cutoff: {
            label: 'A3: SCQ (Clinical Cutoff)',
            template_name: 'report-scq',
            first_name: 'Liam',
            gender: 'he/him',
            informant_one: 'Taylor',
            informant_one_relationship: 'Mother',
            informant_two: '',
            informant_two_relationship: '',
            active_raters: 1,
            fields: [
                { key: 'total_score', label: 'SCQ Total Score (Cutoff >= 15)', value: 19 }
            ]
        },
        static_reference: {
            label: 'A4: ABAS-3 Chart (Static)',
            template_name: 'abasdescriptions',
            first_name: 'Elena',
            gender: 'she/her',
            informant_one: 'Clinician',
            informant_one_relationship: 'Evaluator',
            informant_two: '',
            informant_two_relationship: '',
            active_raters: 1,
            fields: []
        },
        qualitative_matrix: {
            label: 'A5: SLD Eligibility (Matrix)',
            template_name: 'eligibilitysld',
            first_name: 'Noah',
            gender: 'they/them',
            informant_one: 'IEP Team',
            informant_one_relationship: 'Team',
            informant_two: '',
            informant_two_relationship: '',
            active_raters: 1,
            fields: [
                { key: 'exclusionary_factors', label: 'Exclusionary Factors (ruled_out / present)', value: 'ruled_out', type: 'text' }
            ]
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.crae-demo-wrap').forEach(function (root) {
            var presetBtns = root.querySelectorAll('.crae-preset-btn');
            var tplSelect = root.querySelector('.crae-demo-template-select');
            var dynamicFieldsBox = root.querySelector('.crae-demo-dynamic-fields');
            var form = root.querySelector('.crae-demo-form');
            var scoreCardsBox = root.querySelector('.crae-score-cards');
            var narrativeBox = root.querySelector('.crae-demo-narrative');
            var statusEl = root.querySelector('.crae-demo-status');
            var tabBtns = root.querySelectorAll('.crae-code-tab');
            var codeTitleEl = root.querySelector('.crae-code-title');
            var codePreEl = root.querySelector('.crae-code-pre');
            var copyCodeBtn = root.querySelector('.crae-copy-code-btn');

            var activeTab = 'js';
            var lastPayload = {};
            var lastResponse = null;

            function renderDynamicFields(fields) {
                if (!dynamicFieldsBox) return;
                dynamicFieldsBox.innerHTML = '';
                (fields || []).forEach(function (f) {
                    var row = document.createElement('div');
                    row.className = 'crae-field';
                    var label = document.createElement('label');
                    label.textContent = f.label;
                    var input = document.createElement('input');
                    input.type = f.type || 'number';
                    input.name = 'dyn_' + f.key;
                    input.setAttribute('data-scale-key', f.key);
                    input.value = f.value;
                    row.appendChild(label);
                    row.appendChild(input);
                    dynamicFieldsBox.appendChild(row);
                });
            }

            function collectPayload() {
                var tpl = tplSelect ? tplSelect.value : 'report-basc-3-social-emotional';
                var payload = {
                    template_name: tpl,
                    first_name: (form.querySelector('[name="first_name"]') || {}).value || 'Alex',
                    gender: (form.querySelector('[name="gender"]') || {}).value || 'she/her',
                    informant_one: (form.querySelector('[name="informant_one"]') || {}).value || 'Ms. Rivera',
                    informant_one_relationship: (form.querySelector('[name="informant_one_relationship"]') || {}).value || 'Teacher'
                };

                var r2 = (form.querySelector('[name="informant_two"]') || {}).value || '';
                if (r2.trim() !== '') {
                    payload.active_raters = 2;
                    payload.informant_two = r2;
                    payload.informant_two_relationship = (form.querySelector('[name="informant_two_relationship"]') || {}).value || 'Mother';
                }

                root.querySelectorAll('.crae-demo-dynamic-fields input').forEach(function (inp) {
                    var key = inp.getAttribute('data-scale-key');
                    if (key && inp.value !== '') {
                        payload[key] = inp.type === 'number' ? Number(inp.value) : inp.value;
                    }
                });

                lastPayload = payload;
                return payload;
            }

            function buildJsSnippet(payload) {
                return [
                    '// Option 1: Using the plugin SDK (window.ClicReportAPI)',
                    'const result = await window.ClicReportAPI.generateReport(' + JSON.stringify(payload, null, 2) + ');',
                    '',
                    '// Render sanitized narrative & evaluated scores:',
                    'document.querySelector("#narrative-container").innerHTML = result.narrative_html;',
                    'console.log(result.scores_evaluated, result.execution_time_ms);',
                    '',
                    '// Option 2: Direct fetch() with WordPress X-WP-Nonce',
                    'const res = await fetch(window.craeConfig.restUrl, {',
                    '  method: "POST",',
                    '  headers: {',
                    '    "Content-Type": "application/json",',
                    '    "X-WP-Nonce": window.craeConfig.nonce',
                    '  },',
                    '  credentials: "same-origin",',
                    '  body: JSON.stringify(payload)',
                    '});'
                ].join('\n');
            }

            function buildEmbedSnippet(payload) {
                return [
                    '<!-- 1. WordPress Shortcode (Gutenberg / Avada Builder) -->',
                    '[clicreport_template slug="' + payload.template_name + '"]',
                    '',
                    '<?php',
                    '// 2. Custom WordPress PHP Page Template (enqueue SDK + craeConfig)',
                    "if ( function_exists( 'crae' ) ) {",
                    '    crae()->container()',
                    '        ->get( \\ClicReport\\AssessmentEngine\\Frontend\\AssetLoader::class )',
                    '        ->enqueueFrontendAssets();',
                    '}',
                    '?>'
                ].join('\n');
            }

            function refreshCodeInspector() {
                var payload = collectPayload();
                if (activeTab === 'js') {
                    codeTitleEl.textContent = 'Frontend JS -> POST /wp-json/clicreport/v1/reports/generate';
                    codePreEl.textContent = buildJsSnippet(payload);
                } else if (activeTab === 'request') {
                    codeTitleEl.textContent = 'Live JSON Request Payload';
                    codePreEl.textContent = JSON.stringify(payload, null, 2);
                } else if (activeTab === 'response') {
                    codeTitleEl.textContent = 'Live Plugin REST Response JSON';
                    codePreEl.textContent = lastResponse
                        ? JSON.stringify(lastResponse, null, 2)
                        : '// Click "Run Live Plugin Engine" to inspect live REST JSON output...';
                } else {
                    codeTitleEl.textContent = 'Shortcode & Custom Theme Template Snippet';
                    codePreEl.textContent = buildEmbedSnippet(payload);
                }
            }

            function renderScoreCards(scoresEvaluated) {
                if (!scoreCardsBox) return;
                scoreCardsBox.innerHTML = '';
                Object.keys(scoresEvaluated || {}).forEach(function (k) {
                    var item = scoresEvaluated[k];
                    if (!item || typeof item !== 'object') return;
                    var metric = item.raters && item.raters['1'] ? item.raters['1'] : item;
                    if (metric.score === undefined || metric.score === null) return;

                    var card = document.createElement('div');
                    card.className = 'crae-score-card';

                    var title = document.createElement('strong');
                    title.textContent = item.title || k;
                    var valLine = document.createElement('div');
                    valLine.textContent = 'Score: ' + metric.score + (metric.stars ? ' ' + metric.stars : '') + (metric.percentile_ordinal ? ' (' + metric.percentile_ordinal + ')' : '');

                    var pill = document.createElement('span');
                    pill.className = 'crae-score-pill';
                    pill.textContent = metric.range || metric.indicative_text || 'Evaluated';
                    pill.style.backgroundColor = metric.color || '#e2e8f0';
                    pill.style.color = '#0f172a';

                    card.appendChild(title);
                    card.appendChild(valLine);
                    card.appendChild(pill);
                    scoreCardsBox.appendChild(card);
                });
            }

            async function executeDemo() {
                var payload = collectPayload();
                refreshCodeInspector();
                if (statusEl) {
                    statusEl.textContent = 'Calling WordPress REST API...';
                }

                try {
                    var result = await window.ClicReportAPI.generateReport(payload);
                    lastResponse = result;
                    renderScoreCards(result.scores_evaluated || {});
                    if (narrativeBox) {
                        narrativeBox.innerHTML = result.narrative_html || '<p>No narrative output returned.</p>';
                    }
                    if (statusEl) {
                        statusEl.textContent = 'Success in ' + (result.execution_time_ms || 0) + ' ms (Request #' + (result.request_id || '') + ')';
                    }
                    refreshCodeInspector();
                } catch (err) {
                    lastResponse = { error: err.message, status: err.status || 500, payload: err.payload || null };
                    if (statusEl) {
                        statusEl.textContent = 'Error: ' + err.message;
                    }
                    refreshCodeInspector();
                }
            }

            function applyPreset(key) {
                var p = PRESETS[key];
                if (!p) return;
                presetBtns.forEach(function (b) {
                    b.classList.toggle('is-active', b.getAttribute('data-preset') === key);
                });
                if (tplSelect) tplSelect.value = p.template_name;
                form.querySelector('[name="first_name"]').value = p.first_name;
                form.querySelector('[name="gender"]').value = p.gender;
                form.querySelector('[name="informant_one"]').value = p.informant_one;
                form.querySelector('[name="informant_one_relationship"]').value = p.informant_one_relationship;
                form.querySelector('[name="informant_two"]').value = p.informant_two;
                form.querySelector('[name="informant_two_relationship"]').value = p.informant_two_relationship;
                renderDynamicFields(p.fields);
                executeDemo();
            }

            presetBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    applyPreset(btn.getAttribute('data-preset'));
                });
            });

            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeTab = btn.getAttribute('data-tab');
                    tabBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    refreshCodeInspector();
                });
            });

            if (copyCodeBtn) {
                copyCodeBtn.addEventListener('click', function () {
                    if (navigator.clipboard && codePreEl) {
                        navigator.clipboard.writeText(codePreEl.textContent).then(function () {
                            copyCodeBtn.textContent = 'Copied!';
                            setTimeout(function () { copyCodeBtn.textContent = 'Copy'; }, 1500);
                        });
                    }
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                executeDemo();
            });

            if (tplSelect) {
                tplSelect.addEventListener('change', executeDemo);
            }

            form.addEventListener('input', refreshCodeInspector);

            applyPreset('t_score');
        });
    });
})(window, document);
