# ClicReport Assessment & Clinical Narrative Engine — Frontend Integration Guide

This guide is designed for the frontend development team to quickly understand and integrate the **ClicReport Assessment Engine (`clicreport-assessment-engine`)** with WordPress theme templates or decoupled applications.

---

## 1. What is this Plugin?

The **ClicReport Assessment & Clinical Narrative Engine** is an enterprise-grade WordPress plugin (`v1.0.4`) that centralizes psychometric scoring, clinical rule evaluation, and narrative report generation for psychoeducational assessment templates (such as BASC-3, WISC-V, BRIEF-2, Conners-4, SCQ, and 200+ others).

Previously, each assessment template had hundreds of lines of duplicated JavaScript calculation logic, hardcoded narrative strings, and custom table DOM mutations. This plugin offloads all scoring math, multi-rater consistency evaluation, clinical classification, and text generation to a standardized, server-side REST API.

---

## 2. Tools & Technologies Used

- **Backend Runtime**: PHP 8.0+ (strict typing, PSR-4 autoloading).
- **Platform**: WordPress 6.2+ REST API (`/wp-json/clicreport/v1/...`).
- **Security & Authorization**: WordPress REST Nonce (`X-WP-Nonce`), WordPress Capabilities (`edit_posts`), rate limiting, input sanitization via `wp_kses()`.
- **Frontend Client SDK**: Vanilla JavaScript (`crae-api-client.js`) with Promise-based `window.ClicReportAPI`.
- **Database Engine**: Custom relational tables (`crae_templates`, `crae_sections`, `crae_scales`, `crae_narrative_rules`, `crae_audit_logs`).
- **Visuals & Assets Support**:
  - Bundled Copy & Action button assets under `assets/images/`.
  - Server-side Bell Curve SVG/PNG rendering service and client-side table/graph export helpers (`html2canvas`).

---

## 3. Purpose

1. **Accuracy & Consistency**: Standardize scoring algorithms, percentile conversions, confidence interval calculations, and diagnostic cutoffs across all templates.
2. **Grammar & Pronoun Precision**: Handle gender pronouns (`he/him`, `she/her`, `they/them`, etc.), possessive nouns, irregular verbs, and multi-rater agreement grammatically without client-side string concatenation bugs.
3. **Clean Separation of Concerns**: Frontend templates only manage form inputs and UI display. The plugin manages clinical psychometrics and outputs production-ready narrative HTML and score badges.
4. **Instant Multi-Rater Scaling**: Automatically evaluates up to 5 raters (Parent, Teacher, Clinician, etc.) and formats cross-setting comparative narratives.

---

## 4. Implementation Method (Architecture)

The plugin follows a **5-Archetype Psychometric Engine**:

```
           ┌────────────────────────────────────────┐
           │      Frontend Form / Template          │
           │  (Collects scores, raters & examinee)   │
           └──────────────────┬─────────────────────┘
                              │ POST JSON
                              ▼
           ┌────────────────────────────────────────┐
           │    WordPress REST API Controller       │
           │  (/wp-json/clicreport/v1/reports/gen)  │
           └──────────────────┬─────────────────────┘
                              │
               ┌──────────────┴──────────────┐
               ▼                             ▼
    [DatabaseReportEngine]          [Archetype Processors]
    - Authenticates Nonce           1. Normative T-Scores (BASC-3, BRIEF-2)
    - Validates Template Slug       2. Dual Standard/Scaled (WISC-V, WJ-IV)
    - Loads Rules & Scales          3. Clinical Cutoffs (SCQ, ADI-R)
                                    4. Static References / Charts
                                    5. Qualitative Matrices & Eligibility
                              │
                              ▼
           ┌────────────────────────────────────────┐
           │            JSON Response               │
           │  - narrative_html                      │
           │  - scores_evaluated (ranges, badges)   │
           │  - execution_time_ms                   │
           └────────────────────────────────────────┘
```

---

## 5. Frontend Connection Method

Connecting a page template to the engine requires only **two simple steps**:

### Step 1: Enqueue the Plugin SDK in PHP

At the top of your page template (e.g., `report-bas3-social-emotional.php` or `report-wisc-v.php`), add:

```php
// Enqueue jQuery and the ClicReport Frontend SDK
wp_enqueue_script('jquery');

if (function_exists('crae')) {
    crae()->container()
        ->get(\ClicReport\AssessmentEngine\Frontend\AssetLoader::class)
        ->enqueueFrontendAssets();
}
```

This automatically injects:

1. `crae-api-client.js` exposing `window.CraeClient` (and `window.ClicReportAPI`).
2. The global `window.craeConfig` configuration object with image asset URLs and psychometric normative tables:
   ```js
   window.craeConfig = {
     restUrl: "https://your-site.com/wp-json/clicreport/v1/reports/generate",
     templatesUrl: "https://your-site.com/wp-json/clicreport/v1/templates",
     nonce: "1a2b3c4d5e", // WordPress REST nonce
     isLoggedIn: true,
     version: "1.0.4",
     imagesUrl:
       "https://your-site.com/wp-content/plugins/clicreport-assessment-engine/assets/images/",
     images: {
       copyTable: "https://your-site.com/.../assets/images/copy-table.png",
       copyChart: "https://your-site.com/.../assets/images/copy-chart.png",
       copyNarrative:
         "https://your-site.com/.../assets/images/copy-narrative.png",
       copyNarrativeSummary:
         "https://your-site.com/.../assets/images/copy-narrative-summary.jpg",
       copyPerformanceSummary:
         "https://your-site.com/.../assets/images/copy-performance-summary.png",
       copyPictureOfTable:
         "https://your-site.com/.../assets/images/copy-picture-of-table.jpg",
       copyPictureOfChart:
         "https://your-site.com/.../assets/images/copy-picture-of-chart.jpg",
       bellCurveEmpty:
         "https://your-site.com/.../assets/images/bell-curve-empty.jpg",
     },
     scoring: {
       standardPercentiles: { 40: "<0.1", ..., 100: "50", ..., 160: ">99.9" },
       scaledPercentiles:   { 1: "0.1", ..., 10: "50", ..., 19: "99.9" },
       tScorePercentiles:   { 1: "<1", ..., 50: "50", ..., 99: ">99" },
       standardRanges:      [ { max: 69, label: "Significantly Below Average", stars: "**", color: "#e06c6c" }, ... ],
       scaledRanges:        [ { max: 3, label: "Significantly Below Average", stars: "**" }, ... ],
       behavioralRanges:    [ { max: 39, label: "Low" }, { max: 59, label: "Average" }, { max: 64, label: "High Average" }, { max: 69, label: "Elevated", stars: "*" }, { max: 999, label: "Very Elevated", stars: "**" } ],
       vinelandStandardRanges:  [ { max: 70, label: "Low", stars: "**" }, { max: 85, label: "Moderately Low", stars: "*" }, { max: 114, label: "Adequate" }, { max: 129, label: "Moderately High" }, { max: 999, label: "High" } ],
       vinelandSubdomainRanges: [ { max: 9, label: "Low", stars: "**" }, { max: 12, label: "Moderately Low", stars: "*" }, { max: 17, label: "Adequate" }, { max: 20, label: "Moderately High" }, { max: 999, label: "High" } ]
     }
   };
   ```

### Step 2: Use the Global Client in JavaScript

When the user clicks the "Generate Narrative" button, call:

```js
// Both window.CraeClient and window.ClicReportAPI are available:
window.CraeClient.generateReport(payload);
// or
window.ClicReportAPI.generateReport(payload);
```

---

## 6. How to Use Bundled Copy & Visual Images

All action icons and visual assets are now bundled inside the plugin at:
`wp-content/plugins/clicreport-assessment-engine/assets/images/`

This prevents broken image links across subdirectories or staging domains.

### Image Keys & Filenames:

| Image Key                | Filename                       | Purpose                                        |
| :----------------------- | :----------------------------- | :--------------------------------------------- |
| `copyTable`              | `copy-table.png`               | Button icon to copy formatted score table HTML |
| `copyChart`              | `copy-chart.png`               | Button icon to copy graph/chart table          |
| `copyNarrative`          | `copy-narrative.png`           | Button icon to copy narrative text             |
| `copyNarrativeSummary`   | `copy-narrative-summary.jpg`   | Button icon to copy narrative summary          |
| `copyPerformanceSummary` | `copy-performance-summary.png` | Button icon to copy performance summary        |
| `copyPictureOfTable`     | `copy-picture-of-table.jpg`    | Button icon to copy table screenshot image     |
| `copyPictureOfChart`     | `copy-picture-of-chart.jpg`    | Button icon to copy chart screenshot image     |
| `bellCurveEmpty`         | `bell-curve-empty.jpg`         | Bell Curve background graphic                  |

### Usage Method 1: In PHP Theme Files (Recommended)

You can reference the constant `CRAE_PLUGIN_URL`:

```php
<?php
$img_dir = defined('CRAE_PLUGIN_URL') ? CRAE_PLUGIN_URL . 'assets/images/' : '/images/';
?>

<div class="copy-buttons">
    <a href="javascript:void(0);" class="copy-btn copy-table">
        <img src="<?php echo esc_url($img_dir . 'copy-table.png'); ?>" alt="Copy Table">
    </a>
    <a href="javascript:void(0);" class="copy-btn copy-graphs-table">
        <img src="<?php echo esc_url($img_dir . 'copy-chart.png'); ?>" alt="Copy Chart">
    </a>
    <a href="javascript:void(0);" class="copy-btn copy-narrative">
        <img src="<?php echo esc_url($img_dir . 'copy-narrative.png'); ?>" alt="Copy Narrative">
    </a>
    <a href="javascript:void(0);" class="copy-btn copy-picture-of-table">
        <img src="<?php echo esc_url($img_dir . 'copy-picture-of-table.jpg'); ?>" alt="Copy Table Picture">
    </a>
    <a href="javascript:void(0);" class="copy-btn copy-picture-of-graph">
        <img src="<?php echo esc_url($img_dir . 'copy-picture-of-chart.jpg'); ?>" alt="Copy Graph Picture">
    </a>
</div>
```

### Usage Method 2: In JavaScript Dynamically

You can query images via `window.craeConfig` or `window.ClicReportAPI.getImageUrl()`:

```javascript
// Access URL via config
var tableIconUrl = window.craeConfig.images.copyTable;

// Or via API helper
var chartIconUrl = window.ClicReportAPI.getImageUrl("copyChart");

// Copy narrative text using helper
window.ClicReportAPI.copyText("#narrative-container").then(function () {
  $(".copied-narrative").fadeIn().delay(2000).fadeOut();
});
```

---

## 7. Live Psychometric Scoring & Percentile Lookup SDK (Client-Side)

In cognitive, achievement, and psychological assessment templates (e.g. WISC-V, WAIS-IV, WIAT-4, WJ-IV, KTEA-3), evaluators expect live updates as they type scores into the table before generating the final report:
- Percentile rank updates immediately (e.g. `100` &rarr; `50`, `130` &rarr; `98`).
- Qualitative descriptor updates immediately (e.g. `Average`, `Well Above Average`).
- Bell curve bar width adjusts immediately to reflect the score position.

### The Problem in Legacy Templates
Previously, theme templates manually declared:
1. A **121-line hardcoded JavaScript array** (`percentile_lookup['40'] = '<1'; ... percentile_lookup['160'] = '<99.9';`).
2. An extensive `if...else` chain for subtest scaled scores 1–19.
3. An extensive `if...else` chain for composite standard scores.
4. Up to 15 manual `.change()` event listeners repeating the same calculation code for every field.

### The Solution: Centralized SDK Methods
All normative psychometric transformations are centralized inside `crae-api-client.js` and hydrated by `AssessmentScorer.php`. Frontend developers can access these methods directly on `window.CraeClient` (or `window.ClicReportAPI`):

| Method | Parameters | Return Value | Example Battery | Example Call & Output |
| :--- | :--- | :--- | :--- | :--- |
| `CraeClient.getStandardPercentile(score)` | `score` (40–160) | Percentile string (`"<1"`, `"50"`, `"98"`, `"<99.9"`) | WISC-V, WAIS-IV, WIAT-4, WJ-IV | `CraeClient.getStandardPercentile(100)` &rarr; `"50"` |
| `CraeClient.getScaledPercentile(score)` | `score` (1–19) | Percentile string (`"<1"`, `"50"`, `"91"`, `">99"`) | Subtests (WISC, WAIS, NEPSY) | `CraeClient.getScaledPercentile(14)` &rarr; `"91"` |
| `CraeClient.getTScorePercentile(score)` | `score` (1–99) | Percentile string (`"<1"`, `"50"`, `"84"`, `">99"`) | BOT-2, DAS-II subtests, RIAS-2, EDDT | `CraeClient.getTScorePercentile(60)` &rarr; `"84"` |
| `CraeClient.getCompositeDescriptor(score)` | `score` (Standard score) | Cognitive range label string | WISC-V, WAIS-IV, WJ-IV, KTEA-3 | `CraeClient.getCompositeDescriptor(130)` &rarr; `"Well Above Average"` |
| `CraeClient.getSubtestDescriptor(score)` | `score` (1–19) | Subtest range label string | Scaled subtests | `CraeClient.getSubtestDescriptor(4)` &rarr; `"Well Below Average"` |
| `CraeClient.getBehavioralDescriptor(score)` | `score` (T-Score) | Behavioral label (`Low`, `Average`, `Elevated`...) | Conners-3/4, CDI-2, MASC-2, BASC-3 | `CraeClient.getBehavioralDescriptor(66)` &rarr; `"Elevated"` |
| `CraeClient.getVinelandDescriptor(score, isSub)` | `score`, `isSubdomain` (bool) | Adaptive label (`Low`, `Adequate`, `High`...) | Vineland-3 Domain & Subdomains | `CraeClient.getVinelandDescriptor(11, true)` &rarr; `"Moderately Low"` |
| `CraeClient.getBellCurvePercentage(score)` | `score` (40–160) | Percentage float (0.0 to 100.0) | Bell curve bars | `CraeClient.getBellCurvePercentage(100)` &rarr; `50` |
| `CraeClient.updateBellCurveItem(scaleId, score)` | `scaleId` (string), `score` | Updates width on `.{scaleId}_bar` | Interactive bell curve charts | `CraeClient.updateBellCurveItem('fsiq', 115)` |
| `CraeClient.bindAssessmentTable(selector)` | `selector` (CSS selector) | Auto-attaches listeners to all table inputs | Any cognitive/academic table | `CraeClient.bindAssessmentTable('#table-cells')` |

---

### Implementation Instructions for Frontend Templates

#### Step 1: Remove the Legacy Hardcoded Array
Open your assessment template file (`report-*.php`) and **delete the entire 121-line array**:
```javascript
// DELETE THIS ENTIRE BLOCK:
var percentile_lookup = [];
percentile_lookup['40'] = '<1';
...
percentile_lookup['160'] = '<99.9';
```

#### Step 2: Implement Live Updates by Battery Type

##### Battery Type 1: Standard Cognitive & Academic (WISC-V, WAIS-IV, WIAT-4, WJ-IV, KTEA-3)
```javascript
// 1. Subtest scaled scores (1 - 19)
function update_subtest_score_and_descriptor(clicked_el) {
    var val = $(clicked_el).val();
    var row = $(clicked_el).closest('tr');
    if (window.CraeClient) {
        row.find('.descriptor').text(window.CraeClient.getSubtestDescriptor(val));
        row.find('.percentile').text(window.CraeClient.getScaledPercentile(val));
    }
}

// 2. Composite standard score descriptors (e.g. Average, Well Above Average)
function update_composite_descriptor(clicked_el) {
    var val = $(clicked_el).val();
    var desc = window.CraeClient ? window.CraeClient.getCompositeDescriptor(val) : 'Average';
    $(clicked_el).closest('tr').find('.descriptor').text(desc);
}

// 3. Composite standard score percentiles (e.g. 50, 98, <1)
function update_composite_percentile(clicked_el) {
    var id = $(clicked_el).attr('id');
    var val = $(clicked_el).val();
    var pr = window.CraeClient ? window.CraeClient.getStandardPercentile(val) : '<1';
    $('#' + id + '_pr').text(pr);
}

// 4. Real-time Bell Curve bar width updates
function update_bell_curve_items(clicked_el) {
    var id = $(clicked_el).attr('id');
    var val = $(clicked_el).val();
    if (window.CraeClient) {
        window.CraeClient.updateBellCurveItem(id, val);
    }
}
```

##### Battery Type 2: Behavioral & Emotional T-Scores (BASC-3, Conners-3/4, CDI-2, MASC-2, EDDT)
```javascript
// Behavioral T-Score field update (Low, Average, High Average, Elevated, Very Elevated)
function update_behavioral_score_and_descriptor(clicked_el) {
    var val = $(clicked_el).val();
    var row = $(clicked_el).closest('tr');
    if (window.CraeClient) {
        row.find('.descriptor').text(window.CraeClient.getBehavioralDescriptor(val));
        row.find('.percentile').text(window.CraeClient.getTScorePercentile(val));
    }
}
```

##### Battery Type 3: Adaptive Behavior (Vineland-3 Domain & Subdomains)
```javascript
// Vineland-3 Composite / Domain Standard Scores (20 - 160)
function update_vineland_domain_descriptor(clicked_el) {
    var val = $(clicked_el).val();
    var row = $(clicked_el).closest('tr');
    if (window.CraeClient) {
        row.find('.descriptor').text(window.CraeClient.getVinelandDescriptor(val, false));
        row.find('.percentile').text(window.CraeClient.getStandardPercentile(val));
    }
}

// Vineland-3 Subdomain V-Scale Scores (1 - 24)
function update_vineland_subdomain_descriptor(clicked_el) {
    var val = $(clicked_el).val();
    var row = $(clicked_el).closest('tr');
    if (window.CraeClient) {
        row.find('.descriptor').text(window.CraeClient.getVinelandDescriptor(val, true));
    }
}
```

##### Battery Type 4: Zero-Boilerplate Auto-Binder (`bindAssessmentTable`)
For cognitive/academic tables, you can replace all individual event listeners with one line:
```javascript
$(document).ready(function () {
    if (window.CraeClient && window.CraeClient.bindAssessmentTable) {
        window.CraeClient.bindAssessmentTable('#table-cells');
    }
});
```
This automatically:
1. Detects whether each input in a row is a composite (`max > 20`) or a subtest (`max <= 20`).
2. Recalculates `.descriptor` and `.percentile` on both `input` and `change` events.
3. Automatically adjusts the associated bell curve `.{scaleId}_bar`.

---

### Step 3: Integrate Server-Rendered Bell Curve Updates

1. In your HTML, wrap the bell curve section in an outer wrapper:
```html
<div id="bell-curve-wrapper">
    <div class="summary-title">
        ...
        <button class="copy-bell-curve" id="copy-bell-curve">Copy Bell Curve</button>
    </div>
    <div class="bell-curve" id="bell-curve">
        ...
    </div>
</div>
```

2. In your report generation AJAX success callback, inject the server-generated curve HTML:
```javascript
var bellCurveHtml = (result.bell_curve && result.bell_curve.html) || result.bell_curve_html || '';
if (bellCurveHtml) {
    $('#bell-curve-wrapper').html(bellCurveHtml).show();
}
```

3. Ensure the copy button uses delegated event binding so newly injected HTML responds to clicks:
```javascript
$(document).on('click', '#copy-bell-curve', function () {
    // html2canvas capture and clipboard copy
});
```

---

## 8. Example JavaScript Function for Frontend Files

Here is the clean, drop-in snippet for your template's button click event:

### Option A: Standard WordPress `admin-ajax.php` (Recommended for Theme Templates)

```javascript
$(document).ready(function () {
  $(".generate-narrative").on("click", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var originalText = $btn.text();
    $btn.prop("disabled", true).text("Generating...");

    // 1. Build Payload from form inputs
    var payloadData = {
      template_slug: "report-basc-3-social-emotional", // or 'report-wisc-v'
      first_name: $("#first_name").val() || "the examinee",
      gender: $("#gender").val() || "male",
      pre_school_h: $("#pre_school_h").is(":checked") ? "1" : "",
      informant_one: $("#informant_one").val() || "",
      informant_one_relationship:
        $("#informant_one_relationship").val() || "mother",
      informant_two: $("#informant_two").val() || "",
      informant_two_relationship:
        $("#informant_two_relationship").val() || "general education teacher",
      informant_three: $("#informant_three").val() || "",
      informant_three_relationship:
        $("#informant_three_relationship").val() || "math teacher",
      informant_four: $("#informant_four").val() || "",
      informant_four_relationship:
        $("#informant_four_relationship").val() || "",
      informant_five: $("#informant_five").val() || "",
      informant_five_relationship:
        $("#informant_five_relationship").val() || "",
      f_index: $("#f_index").val() || "acceptable",
      Response_Pattern: $("#Response_Pattern").val() || "acceptable",
      Consistency: $("#Consistency").val() || "acceptable",
      f_index1: $("#f_index1").val() || "acceptable",
      Response_Pattern1: $("#Response_Pattern1").val() || "acceptable",
      Consistency1: $("#Consistency1").val() || "acceptable",
      f_index2: $("#f_index2").val() || "acceptable",
      Response_Pattern2: $("#Response_Pattern2").val() || "acceptable",
      Consistency2: $("#Consistency2").val() || "acceptable",
      Social: $("#Social").is(":checked") ? "1" : "", // Exclusion flag for Social Emotional
      adaptive: $("#adaptive").is(":checked") ? "1" : "", // Exclusion flag for Adaptive Skills
      allow_partial: false, // Set to true only if intentionally allowing partial/unassessed subtests (omits validation error)
    };

    // 2. Dynamically gather all numeric scores (.percentile)
    $("input.percentile").each(function () {
      var id = $(this).attr("id");
      var val = $(this).val();
      if (id && val !== "") {
        payloadData[id] = $.isNumeric(val) ? Number(val) : val;
      }
    });

    // Safe config access with defaults (includes initial one-time Psivey-Token)
    var cfg = window.craeConfig || {};
    var ajaxUrl = cfg.ajaxUrl || "/wp-admin/admin-ajax.php";
    var ajaxAction = cfg.ajaxAction || "crae_generate_report";
    var ajaxNonce = cfg.ajaxNonce || "";
    var psiveyToken = cfg.psiveyToken || "";

    // 3. Post to WordPress admin-ajax.php with one-time Psivey-Token
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Psivey-Token": psiveyToken,
      },
      data: {
        action: ajaxAction,
        nonce: ajaxNonce,
        psivey_token: psiveyToken,
        payload: payloadData,
      },
      success: function (response) {
        if (response.success && response.data) {
          var result = response.data;

          // Automatically update one-time Psivey-Token for subsequent generations
          if (result.next_psivey_token) {
            window.craeConfig.psiveyToken = result.next_psivey_token;
          }

          // A. Inject Generated Narrative HTML
          $("#narrative-container").html(result.narrative_html).show();
          $(".narrative-container").show();
          $(".narrative-instructions-container").hide();

          // B. Sync Scores & Star Badges
          if (result.scores_evaluated) {
            Object.keys(result.scores_evaluated).forEach(function (scaleKey) {
              var scaleObj = result.scores_evaluated[scaleKey];
              var raters = scaleObj.raters || {};
              Object.keys(raters).forEach(function (rIdx) {
                var rData = raters[rIdx];
                var cellId = scaleKey + "_" + rIdx;
                var $label = $(
                  'label[la-attr="' +
                    cellId +
                    '"], lable[la-attr="' +
                    cellId +
                    '"]',
                );
                if (rData.stars) {
                  $label.text(rData.stars).show();
                } else {
                  $label.text("").hide();
                }
              });
            });
          }
        } else {
          alert(
            "Generation Error: " +
              (response.data && response.data.message
                ? response.data.message
                : "Unknown error"),
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error, xhr.responseText);
        var msg = "Failed to connect to server.";
        if (
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
        ) {
          msg = xhr.responseJSON.data.message;
        }
        alert("Error: " + msg);
      },
      complete: function () {
        $btn.prop("disabled", false).text(originalText);
      },
    });
  });
});
```

### Option B: JavaScript SDK (`window.ClicReportAPI`)

```javascript
// Using window.ClicReportAPI.generateReportViaAjax(payload)
try {
  var result = await window.ClicReportAPI.generateReportViaAjax(payloadData);
  $("#narrative-container").html(result.narrative_html).show();
} catch (err) {
  alert("Error: " + err.message);
}
```

---

## 9. API Request Methods

You can invoke the engine via either WordPress `admin-ajax.php` or the WordPress REST API:

### Method 1: WordPress AJAX (`admin-ajax.php`)

| Property            | Value                                                                              |
| :------------------ | :--------------------------------------------------------------------------------- |
| **Endpoint**        | `/wp-admin/admin-ajax.php`                                                         |
| **HTTP Method**     | `POST`                                                                             |
| **Content-Type**    | `application/x-www-form-urlencoded` or `multipart/form-data`                       |
| **Action**          | `crae_generate_report`                                                             |
| **Security Check**  | `nonce` parameter matching `wp_create_nonce('crae_ajax_nonce')`                    |
| **Response Format** | Standard WP JSON (`{ success: true, data: { narrative_html, scores_evaluated } }`) |

### Method 2: WordPress REST API

| Property            | Value                                                                              |
| :------------------ | :--------------------------------------------------------------------------------- |
| **Endpoint**        | `/wp-json/clicreport/v1/reports/generate`                                          |
| **HTTP Method**     | `POST`                                                                             |
| **Content-Type**    | `application/json`                                                                 |
| **Authentication**  | `X-WP-Nonce: <wp_rest nonce>` _(automatically attached by `window.ClicReportAPI`)_ |
| **Response Format** | `application/json`                                                                 |

---

## 10. Payload Structures

### A. Multi-Rater Assessments (e.g., BASC-3, BRIEF-2)

```json
{
  "template_slug": "report-basc-3-social-emotional",
  "first_name": "AmriShanab",
  "gender": "male",
  "pre_school_h": "1",
  "informant_one": "TestRater1",
  "informant_one_relationship": "mother",
  "informant_two": "TestRater2",
  "informant_two_relationship": "general education teacher",
  "informant_three": "TestRater3",
  "informant_three_relationship": "math teacher",
  "externalizing_problems_1": 50,
  "externalizing_problems_2": 50,
  "externalizing_problems_3": 50,
  "hyperactivity_1": 50,
  "hyperactivity_2": 50,
  "aggression_1": 65,
  "anxiety_1": 48
}
```

### B. Cognitive / Dual Scaled Assessments (e.g., WISC-V)

```json
{
  "template_slug": "report-wisc-v",
  "first_name": "Maya",
  "gender": "female",
  "include_confidence_intervals": true,
  "ci_level": 95,
  "scores": {
    "fsiq": 108,
    "vci": 114,
    "similarities": 13,
    "vocabulary": 12
  }
}
```

---

## 11. Required & Optional Parameters

### Common Parameters

| Parameter       |  Type  | Required? |     Default      | Description                                                                                           |
| :-------------- | :----: | :-------: | :--------------: | :---------------------------------------------------------------------------------------------------- |
| `template_slug` | string |  **Yes**  |        —         | Unique slug matching database template (e.g., `"report-basc-3-social-emotional"`, `"report-wisc-v"`). |
| `first_name`    | string |    No     | `"the examinee"` | First name of student / examinee.                                                                     |
| `gender`        | string |    No     |     `"male"`     | `"male"`, `"female"`, `"neutral"`, `"he/him"`, `"she/her"`, `"they/them"`.                            |

### Multi-Rater Specific Parameters (BASC-3, BRIEF-2, etc.)

| Parameter                      |     Type      | Required? | Description                                                                                       |
| :----------------------------- | :-----------: | :-------: | :------------------------------------------------------------------------------------------------ |
| `informant_one`                |    string     |    No     | Name of Rater 1 (e.g., `"Sarah Smith"`).                                                          |
| `informant_one_relationship`   |    string     |    No     | Relationship (e.g., `"mother"`, `"father"`, `"general education teacher"`).                       |
| `informant_two`                |    string     |    No     | Name of Rater 2.                                                                                  |
| `informant_two_relationship`   |    string     |    No     | Relationship of Rater 2.                                                                          |
| `informant_three`              |    string     |    No     | Name of Rater 3.                                                                                  |
| `informant_three_relationship` |    string     |    No     | Relationship of Rater 3.                                                                          |
| `pre_school_h`                 | string / bool |    No     | Set to `"1"` or `true` when preschool battery is used. Adjusts composites and scales accordingly. |
| `Social`                       |    string     |    No     | Set to `"1"` to suppress the Social-Emotional composite.                                          |
| `adaptive`                     |    string     |    No     | Set to `"1"` to suppress the Adaptive Skills composite.                                           |
| `{scale_key}_{rater_number}`   |    number     |    No     | T-score for scale and rater (e.g., `hyperactivity_1: 65`).                                        |

---

## 12. API Response Format

```json
{
  "success": true,
  "status": "success",
  "data": {
    "status": "success",
    "request_id": "req_67384a9f",
    "engine": "WordPress DatabaseReportEngine",
    "template_slug": "report-basc-3-social-emotional",
    "examinee": "AmriShanab",
    "pronouns": {
      "subject": "he",
      "object": "him",
      "possessive": "his"
    },
    "scores_evaluated": {
      "hyperactivity": {
        "title": "Hyperactivity",
        "scale_type": "clinical",
        "raters": {
          "1": {
            "score": 50,
            "range": "Average",
            "stars": "",
            "color": "#ffffff",
            "is_elevated": false
          }
        }
      }
    },
    "narrative_html": "<p class='Social-body_n'>Social/Emotional Functioning</p><p>...</p>",
    "execution_time_ms": 5.42
  }
}
```

---

## 13. Error Handling & HTTP Statuses

|         Status Code         | Reason                                                                                                                                         | What Frontend Should Do                                               |
| :-------------------------: | :--------------------------------------------------------------------------------------------------------------------------------------------- | :-------------------------------------------------------------------- |
|        **`200 OK`**         | Report generated successfully.                                                                                                                 | Render `data.narrative_html` and update badges.                       |
|    **`400 Bad Request`**    | Missing `template_slug`, invalid score values, or **incomplete scores (`N/A` detected in narrative)**.                                         | Display returned `error.message` asking user to enter all scores.     |
|   **`401 Unauthorized`**    | User is not logged in (`is_user_logged_in() === false`). Strict access control blocks all guest users.                                         | Redirect or prompt user to log in (`wp_login_url()`).                 |
|     **`403 Forbidden`**     | **Anti-Postman / Tool Block** (`crae_client_blocked_tool` or `crae_client_missing_origin`). Direct tools (Postman, curl, bots) are prohibited. | Request must originate from an authenticated web browser session.     |
|     **`403 Forbidden`**     | **One-Time Psivey-Token Already Consumed** (`crae_token_already_consumed`). Token cannot be reused (blocks Postman replay attacks).            | A fresh one-time Psivey-Token is required for each report generation. |
|   **`401 Unauthorized`**    | **Missing or Invalid Psivey-Token** (`crae_missing_psivey_token`). User session must supply an active single-use token.                        | Obtain fresh token via `action: 'crae_get_psivey_token'`.             |
|     **`403 Forbidden`**     | Invalid or expired security nonce.                                                                                                             | Refresh page to renew nonce token.                                    |
|     **`404 Not Found`**     | Unknown `template_slug`.                                                                                                                       | Check spelling of template slug in payload.                           |
| **`429 Too Many Requests`** | Rate limit exceeded (>60 req/min).                                                                                                             | Show friendly cooldown notice to the user.                            |
|   **`500 Server Error`**    | Unhandled PHP exception.                                                                                                                       | Log error in console and notify technical team.                       |

---

## 14. Incomplete Score Validation (`N/A` Check & `allow_partial`)

To maintain the highest clinical standards and prevent accidental generation of incomplete reports, the backend engine validates generated narratives against unassessed or missing score placeholders (`N/A`).

### Validation Rule:
- By default, if any required score was missed or left blank such that the narrative would output an **`N/A`** range (e.g. `"rated to be in the N/A range"`), the API rejects the request and returns a **`400 Bad Request`**:
  ```json
  {
    "success": false,
    "data": {
      "message": "Incomplete assessment data: One or more required scores are missing. Please enter all scores before generating the narrative."
    }
  }
  ```

### How the Frontend Handles This:
1. **Standard Flow (Default)**: Ensure all administered subtest scores are passed in `payloadData`. If an evaluator misses a score, display the returned `message` (e.g. via alert or toast) prompting them to fill in the missing fields.
2. **Partial Assessment Mode (`allow_partial: true`)**: If your UI supports an intentional partial assessment where only a subset of subtests was administered, pass:
   ```javascript
   payloadData.allow_partial = true;
   ```
   This instructs the engine to bypass the strict missing score check and render whatever subtests were provided.

---

## 15. Plugin Deployment Steps on Live Site

Follow these steps to deploy and activate the plugin on the live WordPress environment:

### Step 1: Pre-Deployment Check

Ensure the live server meets the requirements:

- **PHP**: `8.0` or higher
- **WordPress**: `6.2` or higher
- **MySQL / MariaDB**: MySQL 5.7+ or MariaDB 10.3+

### Step 2: Upload Plugin Files

1. Zip or upload the directory:
   `wp-content/plugins/clicreport-assessment-engine/`
   to the live site's `wp-content/plugins/` directory.
2. Verify folder structure:
   ```
   wp-content/plugins/clicreport-assessment-engine/
   ├── assets/
   │   ├── css/
   │   ├── images/  (all copy icons & bell curves)
   │   └── js/      (crae-api-client.js)
   ├── config/      (constants, capabilities, seed-data)
   ├── includes/    (all PSR-4 PHP classes)
   └── clicreport-assessment-engine.php
   ```

### Step 3: Activate Plugin in WP Admin

1. Go to **WP Admin > Plugins > Installed Plugins**.
2. Locate **ClicReport Assessment & Clinical Narrative Engine**.
3. Click **Activate**.
   - _Note: On activation, the plugin automatically creates the required tables (`crae_templates`, `crae_scales`, `crae_narrative_rules`, etc.) and seeds all 201 templates and psychometric rules._

### Step 4: Verify Live Endpoints

#### A. Verify REST API

Test the REST API endpoint from browser or curl:

```bash
curl -X GET https://your-live-site.com/wp-json/clicreport/v1/templates
```

You should receive a `200 OK` JSON response containing the template catalog.

#### B. Verify AJAX Endpoint

In browser DevTools Console on an assessment page:

```javascript
$.post(
  window.craeConfig.ajaxUrl,
  {
    action: window.craeConfig.ajaxAction,
    nonce: window.craeConfig.ajaxNonce,
    payload: {
      template_slug: "report-basc-3-social-emotional",
      first_name: "Test",
    },
  },
  function (res) {
    console.log("AJAX Test Success:", res);
  },
);
```

### Step 5: Update Frontend Templates

Ensure each active assessment page template in the theme (e.g. `report-bas3-social-emotional.php`, `report-wisc-v.php`) has the SDK enqueue snippet:

```php
if (function_exists('crae')) {
    crae()->container()
        ->get(\ClicReport\AssessmentEngine\Frontend\AssetLoader::class)
        ->enqueueFrontendAssets();
}
```

### Step 6: Flush Caches

- Flush any Object Cache (Redis/Memcached).
- Flush CDN or page cache (Cloudflare, WP Rocket, LiteSpeed, etc.) so new JavaScript and nonce tokens are served freshly.

---

## 16. Key Takeaways for Frontend Developers

1. **Do not format narrative text in JavaScript**: The backend outputs valid, sanitized, grammatically matched HTML in `narrative_html`. Simply inject this into `#narrative-container`.
2. **Use Centralized Scoring Methods**: Never hardcode 121-line percentile arrays or descriptor lookup `if/else` ladders in template files. Call `CraeClient` (or `ClicReportAPI`):
   - `getStandardPercentile(score)` & `getCompositeDescriptor(score)` for cognitive/academic standard scores (40–160).
   - `getScaledPercentile(score)` & `getSubtestDescriptor(score)` for scaled subtests (1–19).
   - `getTScorePercentile(score)` & `getBehavioralDescriptor(score)` for behavioral T-scores (1–99).
   - `getVinelandDescriptor(score, isSubdomain)` for Vineland-3 standard scores and v-scale subdomains.
3. **Automate with `bindAssessmentTable()`**: For standard assessment tables, one call to `CraeClient.bindAssessmentTable('#table-cells')` auto-binds input listeners, descriptors, percentiles, and bell curve bars.
4. **Use Bundled Plugin Images**: Use `CRAE_PLUGIN_URL . 'assets/images/...'` in PHP or `window.craeConfig.images` in JS instead of hardcoded `/images/` paths.
5. **Always use `window.CraeClient.generateReport(payload)`**: Avoid writing raw `fetch()` or `$.ajax()` calls so nonces, credentials, and error normalization are handled uniformly.
