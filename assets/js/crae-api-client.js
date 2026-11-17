/**
 * ClicReport Universal Frontend API Client (v1.0)
 *
 * Automatically targets WordPress REST (/wp-json/clicreport/v1/* with X-WP-Nonce)
 * when localized via `craeConfig`, and falls back to standalone local PHP
 * (`/backend/api/generate_report.php`) for preview environments.
 */
(function (window, document) {
    'use strict';

    function getConfig() {
        return window.craeConfig || {};
    }

    function normalizeResponse(json) {
        if (json && json.success === true && json.data && typeof json.data === 'object') {
            return Object.assign({}, json.data, {
                status: json.status || 'success',
                success: true,
                meta: json.meta || {}
            });
        }
        return json;
    }

    async function requestJson(url, options) {
        var cfg = getConfig();
        var headers = Object.assign(
            {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            (options && options.headers) || {}
        );

        if (cfg.nonce && !headers['X-WP-Nonce']) {
            headers['X-WP-Nonce'] = cfg.nonce;
        }

        if (cfg.psiveyToken && !headers['Psivey-Token']) {
            headers['Psivey-Token'] = cfg.psiveyToken;
            headers['X-Psivey-Token'] = cfg.psiveyToken;
        }

        var response = await window.fetch(url, Object.assign({}, options, {
            headers: headers,
            credentials: cfg.restUrl ? 'same-origin' : 'omit'
        }));

        var json = await response.json();
        if (!response.ok) {
            var errMsg = (json && (json.message || (json.data && json.data.message))) || ('HTTP ' + response.status);
            var err = new Error(errMsg);
            err.status = response.status;
            err.payload = json;
            throw err;
        }

        var normalized = normalizeResponse(json);
        if (normalized && normalized.next_psivey_token) {
            cfg.psiveyToken = normalized.next_psivey_token;
        }
        return normalized;
    }

    var ClicReportAPI = {
        generateReport: function (payload, fallbackEndpoint) {
            var cfg = getConfig();
            var targetUrl = cfg.restUrl || fallbackEndpoint || 'backend/api/generate_report.php';
            return requestJson(targetUrl, {
                method: 'POST',
                body: JSON.stringify(payload || {})
            });
        },

        generateReportViaAjax: function (payload) {
            var cfg = getConfig();
            var ajaxUrl = cfg.ajaxUrl || '/wp-admin/admin-ajax.php';
            var formData = new URLSearchParams();
            formData.append('action', cfg.ajaxAction || 'crae_generate_report');
            formData.append('nonce', cfg.ajaxNonce || '');
            formData.append('psivey_token', cfg.psiveyToken || '');
            formData.append('payload', JSON.stringify(payload || {}));

            return window.fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Psivey-Token': cfg.psiveyToken || ''
                },
                body: formData.toString()
            })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json.success) {
                    var msg = (json.data && json.data.message) ? json.data.message : 'Report generation failed.';
                    throw new Error(msg);
                }
                if (json.data && json.data.next_psivey_token) {
                    cfg.psiveyToken = json.data.next_psivey_token;
                }
                return json.data;
            });
        },

        getTemplates: function (filters) {
            var cfg = getConfig();
            if (!cfg.templatesUrl) {
                return Promise.reject(new Error('WordPress templatesUrl is not configured.'));
            }
            var params = new URLSearchParams(filters || {});
            var qs = params.toString();
            return requestJson(cfg.templatesUrl + (qs ? '?' + qs : ''), { method: 'GET' });
        },

        getTemplate: function (slug) {
            var cfg = getConfig();
            if (!cfg.templatesUrl) {
                return Promise.reject(new Error('WordPress templatesUrl is not configured.'));
            }
            return requestJson(cfg.templatesUrl + '/' + encodeURIComponent(slug), { method: 'GET' });
        },

        getPsiveyToken: function () {
            var cfg = getConfig();
            if (!cfg.psiveyTokenUrl) {
                return Promise.reject(new Error('WordPress psiveyTokenUrl is not configured.'));
            }
            return requestJson(cfg.psiveyTokenUrl, { method: 'POST', body: '{}' });
        },

        submitContact: function (payload) {
            var cfg = getConfig();
            if (!cfg.contactUrl) {
                return Promise.reject(new Error('WordPress contactUrl is not configured.'));
            }
            return requestJson(cfg.contactUrl, {
                method: 'POST',
                body: JSON.stringify(payload || {})
            });
        },

        syncBellCurve: function (bellCurveData, containerSelector) {
            if (!bellCurveData || !bellCurveData.bars || !Array.isArray(bellCurveData.bars)) {
                return;
            }
            var container = containerSelector
                ? document.querySelector(containerSelector)
                : document.querySelector('#bell-curve, .bell-curve');

            var aliasMap = {
                'fsiq': ['full_scale_iq', 'fsiq'],
                'vci': ['verbal_comprehension', 'vci'],
                'vsi': ['visual_spatial', 'vsi'],
                'fri': ['fluid_reasoning', 'fri'],
                'wmi': ['working_memory', 'wmi'],
                'psi': ['processing_speed', 'psi'],
                'full_scale_iq': ['fsiq', 'full_scale_iq'],
                'verbal_comprehension': ['vci', 'verbal_comprehension'],
                'visual_spatial': ['vsi', 'visual_spatial'],
                'fluid_reasoning': ['fri', 'fluid_reasoning'],
                'working_memory': ['wmi', 'working_memory'],
                'processing_speed': ['psi', 'processing_speed']
            };

            bellCurveData.bars.forEach(function (bar) {
                var candidates = aliasMap[bar.scale_key] || [bar.scale_key];
                candidates.forEach(function (cKey) {
                    var el = document.querySelector('.' + cKey + '_bar');
                    if (el) {
                        el.style.width = bar.width_css;
                        if (bar.color) {
                            el.style.background = bar.color;
                        }
                    }
                });
            });

            if (container && bellCurveData.svg) {
                var svgWrap = document.querySelector('.crae-bell-svg-wrap');
                if (!svgWrap && container.parentNode) {
                    svgWrap = document.createElement('div');
                    svgWrap.className = 'crae-bell-svg-wrap';
                    svgWrap.style.marginTop = '20px';
                    container.parentNode.insertBefore(svgWrap, container.nextSibling);
                }
                if (svgWrap) {
                    svgWrap.innerHTML = bellCurveData.svg;
                }
            }
        },

        getImageUrl: function (imageKey) {
            var cfg = getConfig();
            if (cfg.images && cfg.images[imageKey]) {
                return cfg.images[imageKey];
            }
            if (cfg.imagesUrl) {
                var map = {
                    copyTable: 'copy-table.png',
                    copyChart: 'copy-chart.png',
                    copyNarrative: 'copy-narrative.png',
                    copyNarrativeSummary: 'copy-narrative-summary.jpg',
                    copyPerformanceSummary: 'copy-performance-summary.png',
                    copyPictureOfTable: 'copy-picture-of-table.jpg',
                    copyPictureOfChart: 'copy-picture-of-chart.jpg',
                    bellCurveEmpty: 'bell-curve-empty.jpg',
                    bellCurveEmptyPng: 'bell-curve-empty.png'
                };
                var filename = map[imageKey] || imageKey;
                return cfg.imagesUrl + filename;
            }
            return '/images/' + imageKey;
        },

        copyText: function (elementOrSelector) {
            var target = typeof elementOrSelector === 'string'
                ? document.querySelector(elementOrSelector)
                : elementOrSelector;
            if (!target) {
                return Promise.reject(new Error('Target element not found'));
            }
            if (navigator.clipboard && window.getSelection) {
                var range = document.createRange();
                range.selectNode(target);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                try {
                    document.execCommand('copy');
                    sel.removeAllRanges();
                    return Promise.resolve(true);
                } catch (e) {
                    sel.removeAllRanges();
                    return navigator.clipboard.writeText(target.innerText || target.textContent);
                }
            }
            return Promise.reject(new Error('Clipboard API not supported in this browser'));
        }
    };

    window.ClicReportAPI = ClicReportAPI;

    // Auto-bind shortcode shells rendered by [clicreport_template] & [clicreport_sandbox]
    document.addEventListener('DOMContentLoaded', function () {
        var shells = document.querySelectorAll('.crae-shortcode-shell');
        shells.forEach(function (shell) {
            var form = shell.querySelector('.crae-assessment-form');
            var outputEl = shell.querySelector('.crae-report-output');
            var statusEl = shell.querySelector('.crae-status-text');
            var copyBtn = shell.querySelector('.crae-copy-btn');
            var tplSelect = shell.querySelector('.crae-sandbox-select');

            if (tplSelect) {
                tplSelect.addEventListener('change', function () {
                    shell.setAttribute('data-template-slug', tplSelect.value);
                });
            }

            if (!form) {
                return;
            }

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                var formData = new FormData(form);
                var payload = {
                    template_name: shell.getAttribute('data-template-slug') || 'report-basc-3-social-emotional',
                    '0': 'Two-row'
                };

                formData.forEach(function (value, key) {
                    payload[key] = value;
                });

                if (statusEl) {
                    statusEl.textContent = 'Generating clinical narrative...';
                }

                try {
                    var result = await ClicReportAPI.generateReport(payload);
                    if (outputEl) {
                        outputEl.innerHTML = result.narrative_html || '';
                        outputEl.style.display = 'block';
                    }
                    if (copyBtn) {
                        copyBtn.style.display = 'inline-block';
                    }
                    if (statusEl) {
                        statusEl.textContent = 'Generated in ' + (result.execution_time_ms || 0) + ' ms';
                    }
                } catch (err) {
                    if (statusEl) {
                        statusEl.textContent = 'Error: ' + err.message;
                    }
                }
            });

            if (copyBtn && outputEl) {
                copyBtn.addEventListener('click', function () {
                    if (navigator.clipboard && outputEl.innerText) {
                        navigator.clipboard.writeText(outputEl.innerText).then(function () {
                            if (statusEl) {
                                statusEl.textContent = 'Copied narrative text.';
                            }
                        });
                    }
                });
            }
        });
    });
})(window, document);

