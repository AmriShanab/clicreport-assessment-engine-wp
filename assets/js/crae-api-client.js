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

    var DEFAULT_SCORING = {
        standardRanges: [
            { max: 69, label: 'Significantly Below Average', stars: '**', color: '#e06c6c' },
            { max: 79, label: 'Well Below Average', stars: '*', color: '#ea9999' },
            { max: 84, label: 'Below Average', stars: '*', color: '#ffe699' },
            { max: 89, label: 'Low Average', stars: '', color: '#ffe699' },
            { max: 110, label: 'Average', stars: '', color: '' },
            { max: 115, label: 'High Average', stars: '', color: '#d9ead3' },
            { max: 120, label: 'Above Average', stars: '', color: '#d9ead3' },
            { max: 130, label: 'Well Above Average', stars: '', color: '#b6d7a8' },
            { max: 999, label: 'Significantly Above Average', stars: '', color: '#93c47d' }
        ],
        scaledRanges: [
            { max: 3, label: 'Significantly Below Average', stars: '**' },
            { max: 5, label: 'Well Below Average', stars: '*' },
            { max: 6, label: 'Below Average', stars: '*' },
            { max: 7, label: 'Low Average', stars: '' },
            { max: 12, label: 'Average', stars: '' },
            { max: 13, label: 'High Average', stars: '' },
            { max: 14, label: 'Above Average', stars: '' },
            { max: 16, label: 'Well Above Average', stars: '' },
            { max: 999, label: 'Significantly Above Average', stars: '' }
        ],
        standardPercentiles: {
            40: '<0.1', 41: '<0.1', 42: '<0.1', 43: '<0.1', 44: '<0.1', 45: '<0.1',
            46: '<0.1', 47: '<0.1', 48: '<0.1', 49: '<0.1', 50: '<0.1', 51: '0.1',
            52: '0.1', 53: '0.1', 54: '0.1', 55: '0.1', 56: '0.2', 57: '0.2',
            58: '0.3', 59: '0.3', 60: '0.4', 61: '0.5', 62: '1', 63: '1',
            64: '1', 65: '1', 66: '1', 67: '1', 68: '2', 69: '2',
            70: '2', 71: '3', 72: '3', 73: '4', 74: '4', 75: '5',
            76: '5', 77: '6', 78: '7', 79: '8', 80: '9', 81: '10',
            82: '12', 83: '13', 84: '14', 85: '16', 86: '18', 87: '19',
            88: '21', 89: '23', 90: '25', 91: '27', 92: '30', 93: '32',
            94: '34', 95: '37', 96: '39', 97: '42', 98: '45', 99: '47',
            100: '50', 101: '53', 102: '55', 103: '58', 104: '61', 105: '63',
            106: '66', 107: '68', 108: '70', 109: '73', 110: '75', 111: '77',
            112: '79', 113: '81', 114: '82', 115: '84', 116: '86', 117: '87',
            118: '88', 119: '90', 120: '91', 121: '92', 122: '93', 123: '94',
            124: '95', 125: '95', 126: '96', 127: '96', 128: '97', 129: '97',
            130: '98', 131: '98', 132: '98', 133: '99', 134: '99', 135: '99',
            136: '99', 137: '99', 138: '99', 139: '99.5', 140: '99.6', 141: '99.7',
            142: '99.7', 143: '99.8', 144: '99.8', 145: '99.9', 146: '99.9', 147: '99.9',
            148: '99.9', 149: '99.9', 150: '>99.9', 151: '>99.9', 152: '>99.9', 153: '>99.9',
            154: '>99.9', 155: '>99.9', 156: '>99.9', 157: '>99.9', 158: '>99.9', 159: '>99.9',
            160: '>99.9'
        },
        scaledPercentiles: {
            1: '0.1', 2: '0.4', 3: '1', 4: '2', 5: '5', 6: '9', 7: '16', 8: '25', 9: '37',
            10: '50', 11: '63', 12: '75', 13: '84', 14: '91', 15: '95', 16: '98', 17: '99',
            18: '99.6', 19: '99.9'
        }
    };

    function getScoringConfig() {
        var cfg = getConfig();
        return (cfg && cfg.scoring) || DEFAULT_SCORING;
    }

    var Scoring = {
        getStandardPercentile: function (score) {
            if (score === null || score === undefined || score === '' || isNaN(score)) {
                return '';
            }
            var num = Math.round(Number(score));
            if (num < 40) return '<1';
            if (num > 160) return '<99.9';
            var sc = getScoringConfig();
            var map = (sc && sc.standardPercentiles) || DEFAULT_SCORING.standardPercentiles;
            var pr = map[num] !== undefined ? String(map[num]) : '';
            if (pr === '<0.1') return '<1';
            if (pr === '>99.9') return '<99.9';
            return pr;
        },

        getScaledPercentile: function (score) {
            if (score === null || score === undefined || score === '' || isNaN(score)) {
                return '';
            }
            var num = Math.round(Number(score));
            if (num <= 2) return '<1';
            if (num >= 19) return '>99';
            var sc = getScoringConfig();
            var map = (sc && sc.scaledPercentiles) || DEFAULT_SCORING.scaledPercentiles;
            return map[num] !== undefined ? String(map[num]) : '';
        },

        getCompositeDescriptor: function (score) {
            if (score === null || score === undefined || score === '' || isNaN(score)) {
                return '';
            }
            var num = Number(score);
            var sc = getScoringConfig();
            var ranges = (sc && sc.standardRanges) || DEFAULT_SCORING.standardRanges;
            for (var i = 0; i < ranges.length; i++) {
                if (num <= ranges[i].max) {
                    return ranges[i].label;
                }
            }
            return 'Significantly Above Average';
        },

        getSubtestDescriptor: function (score) {
            if (score === null || score === undefined || score === '' || isNaN(score)) {
                return '';
            }
            var num = Number(score);
            var sc = getScoringConfig();
            var ranges = (sc && sc.scaledRanges) || DEFAULT_SCORING.scaledRanges;
            for (var i = 0; i < ranges.length; i++) {
                if (num <= ranges[i].max) {
                    return ranges[i].label;
                }
            }
            return 'Significantly Above Average';
        },

        getBellCurvePercentage: function (score) {
            if (score === null || score === undefined || score === '' || isNaN(score)) {
                return 0;
            }
            var num = Number(score);
            var pct = ((num - 40) / 120) * 100;
            return Math.max(0, Math.min(100, pct));
        },

        updateBellCurveItem: function (scaleId, score) {
            if (!scaleId) return;
            var pct = Scoring.getBellCurvePercentage(score);
            var barSelector = '.' + scaleId + '_bar';
            var calcWidth = 'calc(' + pct + '% - ' + (pct / 10) + 'px)';
            if (window.jQuery) {
                window.jQuery(barSelector).css('width', calcWidth);
            } else {
                var el = document.querySelector(barSelector);
                if (el) el.style.width = calcWidth;
            }
        },

        bindAssessmentTable: function (tableOrContainerSelector) {
            var sel = tableOrContainerSelector || '#table-cells';
            var container = typeof sel === 'string' ? document.querySelector(sel) : sel;
            if (!container) return;

            function handleInput(input) {
                var val = input.value;
                var id = input.id || input.name;
                var row = input.closest('tr');
                if (!row) return;

                var maxAttr = input.getAttribute('max');
                var maxVal = maxAttr ? Number(maxAttr) : null;
                var isComposite = (maxVal !== null && maxVal > 20) || (!maxVal && Number(val) > 20);

                var descriptorText = '';
                var percentileText = '';

                if (isComposite) {
                    descriptorText = Scoring.getCompositeDescriptor(val);
                    percentileText = Scoring.getStandardPercentile(val);
                    Scoring.updateBellCurveItem(id, val);
                } else {
                    descriptorText = Scoring.getSubtestDescriptor(val);
                    percentileText = Scoring.getScaledPercentile(val);
                }

                var descEl = row.querySelector('.descriptor');
                if (descEl) descEl.textContent = descriptorText;

                var prEl = row.querySelector('.percentile') || (id ? document.getElementById(id + '_pr') : null);
                if (prEl) prEl.textContent = percentileText;

                if (id) {
                    var textValEl = row.querySelector('.' + id + '.text-values');
                    if (textValEl) textValEl.textContent = val;
                }
            }

            container.addEventListener('input', function (e) {
                if (e.target && e.target.matches('input[type="number"], input.score-input')) {
                    handleInput(e.target);
                }
            });

            container.addEventListener('change', function (e) {
                if (e.target && e.target.matches('input[type="number"], input.score-input')) {
                    handleInput(e.target);
                }
            });

            var inputs = container.querySelectorAll('input[type="number"], input.score-input');
            inputs.forEach(function (inp) {
                if (inp.value !== '') {
                    handleInput(inp);
                }
            });
        }
    };

    ClicReportAPI.scoring = Scoring;
    ClicReportAPI.getStandardPercentile = Scoring.getStandardPercentile;
    ClicReportAPI.getScaledPercentile = Scoring.getScaledPercentile;
    ClicReportAPI.getCompositeDescriptor = Scoring.getCompositeDescriptor;
    ClicReportAPI.getSubtestDescriptor = Scoring.getSubtestDescriptor;
    ClicReportAPI.getBellCurvePercentage = Scoring.getBellCurvePercentage;
    ClicReportAPI.updateBellCurveItem = Scoring.updateBellCurveItem;
    ClicReportAPI.bindAssessmentTable = Scoring.bindAssessmentTable;

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

