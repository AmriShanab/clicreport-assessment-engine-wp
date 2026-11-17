# ClicReport Assessment & Clinical Narrative Engine (`v1.0.0`)

Enterprise WordPress plugin (`clicreport-assessment-engine`) for psychoeducational assessment scoring, multi-rater clinical narrative generation across **201 templates** and **5 psychometric archetypes**, and Psivey AI JWT authentication backed by WordPress MySQL (`$wpdb`) and the WordPress REST API.

---

## 1. How the Plugin Works

### 1.1 High-Level Architecture

```text
Browser / Avada Page / Shortcode
   │  (Cookie + X-WP-Nonce OR Authorization: Bearer <JWT> OR Application Password)
   ▼
WordPress REST API (/wp-json/clicreport/v1/* & /wp-json/psivey/v1/*)
   │  ├── AuthManager (determine_current_user for JWT Bearer tokens)
   │  ├── CorsManager (strict home_url() + configured origin allowlist)
   │  ├── CapabilityManager (built-in WP caps: 'read' & 'manage_options')
   │  ├── RateLimiter (WordPress Transients sliding window -> HTTP 429)
   │  └── InputSanitizer & GenerateReportRequest (recursive sanitization)
   ▼
DatabaseReportEngine
   │  ├── TemplateRepository / ScaleRepository / NarrativeRuleRepository ($wpdb->prepare)
   │  ├── ArchetypeFactory -> resolves Archetype 1–5 processor
   │  ├── AssessmentScorer (normative percentiles & 7 scale classifications)
   │  ├── ReportGrammar + InformantManager + NarrativeInterpolator (wp_kses())
   │  └── AuditLogRepository (non-PII HIPAA/FERPA metrics only)
   ▼
WordPress MySQL ({$wpdb->prefix}crae_*)
```

### 1.2 Plugin Lifecycle & Directory Map

| Component | Entry / Class | Responsibility |
| :--- | :--- | :--- |
| **Bootstrap** | `clicreport-assessment-engine.php` | Defines `CRAE_*` constants, checks PHP `>= 8.0`, registers PSR-4 `Autoloader`, hooks activation/deactivation, boots `Plugin::instance()`, exposes `crae()`. |
| **DI Container & Kernel** | `includes/Core/Plugin.php`, `Container.php` | Registers all repositories, engines, security services, REST controllers, Admin pages, and Shortcodes cleanly on WordPress hooks. |
| **Activation & Migration** | `includes/Core/Activator.php`, `includes/Database/Migrator.php` | Checks WP `>= 6.2`, supports Single Site & Multisite (`networkWide`), runs `dbDelta()` for 8 MySQL tables, seeds `config/seed-data/*.json`, and protects `wp-content/uploads/crae-logs/`. |
| **Capabilities** | `config/capabilities.php`, `includes/Authorization/CapabilityManager.php` | Maps actions directly to built-in WordPress capabilities (`read` for logged-in template viewing/report generation; `manage_options` for template admin/settings/audit logs). **Zero custom roles are created or modified.** |

### 1.3 MySQL Database Schema (`{$wpdb->prefix}crae_*`)

All tables use WordPress's dynamic `$wpdb->prefix` (Multisite-safe) and prepared SQL (`$wpdb->prepare()`):

1. `crae_templates` — Master catalog of all **201 clinical templates** (`template_slug`, `archetype`, `default_score_system`, `config_json`).
2. `crae_sections` — Ordered assessment report sections per template.
3. `crae_scales` — **114+ psychometric domain/composite scales** (`scale_key`, `scale_type`, `is_composite`, `cutoff_threshold`, `rater_restriction`).
4. `crae_score_ranges` — Normative score thresholds, range labels, badge colors, and elevation flags.
5. `crae_narrative_rules` — Elevated and average clinical prose templates keyed by scale & trigger condition.
6. `crae_validity_rules` — Validity index caution rules (e.g., F-Index, Consistency).
7. `crae_decision_options` — Qualitative eligibility/decision matrix prose fragments (Archetype 5).
8. `crae_audit_logs` — Execution telemetry (`request_id`, `user_id`, `template_slug`, `archetype`, `execution_time_ms`, `status`, salted HMAC `ip_hash`). **Zero student names, examinee DOBs, or raw scores are persisted.**

### 1.4 The 5 Clinical Archetypes & Scoring Engine

[`ArchetypeFactory`](includes/Services/Engine/ArchetypeFactory.php) routes each template's `archetype` column to one of five processors implementing `ArchetypeProcessorInterface`:

| Archetype Key | Processor Class | Seed Count | Clinical Scope |
| :--- | :--- | :---: | :--- |
| `t_score` | `NormativeTScoreProcessor` | 30 | BASC-3/4, BRIEF-2, Conners 3/4, SRS-2, EDDT, MASC-2, SPM-2 (`M=50, SD=10`, clinical & inverse adaptive) |
| `dual_standard_scaled` | `DualStandardScaledProcessor` | 96 | WISC-V, WAIS-IV/V, WIAT-3/4, WJ-IV, KTEA-3, Vineland-3 (`M=100, SD=15`; `M=10, SD=3`; `M=15, SD=3` V-scale) |
| `clinical_cutoff` | `ClinicalCutoffProcessor` | 6 | ADI-R, ADOS-2, SCQ (`>= 15`), Vanderbilt-2 symptom/impairment gatekeepers |
| `static_reference` | `StaticReferenceProcessor` | 46 | Normative classification charts, graph helpers, cover pages, timers |
| `qualitative_matrix` | `QualitativeMatrixProcessor` | 23 | Special Education Eligibility (SLD/ASD/OHI/Speech), EBR, FBA, Kennedy Axis V GAF, Dunn-Rankin |

### 1.5 Security & Privacy Safeguards

- **Authentication**: Cookie + `X-WP-Nonce` (`wp_rest`) for same-origin pages, WordPress Application Passwords, and HS256 JWT Bearer tokens (`AuthManager` + `PsiveyTokenService` with revocation via `crae_token_revoked_before` user meta).
- **XSS & Output Hardening**: Dynamic examinee/informant fields are escaped with `esc_html()` inside `NarrativeInterpolator`, and synthesized clinical HTML passes through `wp_kses()` allowlisting structural tags only.
- **Mass-Assignment & SQLi Prevention**: `TemplateMutationRequest` strictly whitelists writable fields (`template_name`, `publisher`, `category`, `intro_narrative`, `is_active`); all repositories use `$wpdb->prepare()` and column allowlists.
- **Rate Limiting**: WordPress Transient sliding-window counters enforce per-user / per-IP limits (`60 req/min` default for report generation, `15 req/min` for token issuance, `5 req/10 min` for contact submissions), returning HTTP `429` + `Retry-After`.
- **Upload Security**: `FileValidator` verifies HTTP upload state, 10 MB cap, WordPress extension checks (`.pdf`, `.docx`, `.txt`), and `finfo` real MIME verification.
- **Strict CORS**: `CorsManager` permits only `home_url()` and configured origins (`crae_settings['allowed_cors_origins']`), eliminating wildcard `*`.

---

## 2. How to Deploy

### 2.1 Requirements
- **PHP**: `8.0+` *(verified on PHP `8.4.26`)*
- **WordPress**: `6.2+` *(single-site or Multisite)*
- **Database**: MySQL `5.7+` / `8.0+` or MariaDB `10.4+`
- **Permalinks**: Pretty Permalinks enabled (**Settings → Permalinks → Post name**) so `/wp-json/` routes are active.

### 2.2 Packaging & Installation

#### Option A: Deploy via ZIP Upload in WP Admin *(Recommended for Staging/Production)*
1. Exclude local/dev test files if desired (or zip the entire `clicreport-assessment-engine/` folder cleanly so the root of the `.zip` contains `clicreport-assessment-engine/clicreport-assessment-engine.php`).
2. In PowerShell, build a clean deployment archive:
   ```powershell
   Compress-Archive -Path "clicreport-assessment-engine" -DestinationPath "clicreport-assessment-engine-1.0.0.zip" -Force
   ```
3. In WordPress Admin, go to **Plugins → Add New → Upload Plugin**, choose `clicreport-assessment-engine-1.0.0.zip`, click **Install Now**, and then **Activate Plugin**.

#### Option B: Direct Server / WP-CLI Deploy
1. Copy `clicreport-assessment-engine/` into `wp-content/plugins/clicreport-assessment-engine/`.
2. Activate via WP-CLI:
   ```bash
   wp plugin activate clicreport-assessment-engine
   # For Multisite network-wide activation:
   wp plugin activate clicreport-assessment-engine --network
   ```

### 2.3 Post-Activation Checklist
1. Open **WP Admin → ClicReport** and confirm:
   - **Active Clinical Templates**: `201 / 201`
   - **Database Schema Version**: `1.0.0`
   - All 5 archetype row counts match (`30 / 96 / 6 / 46 / 23`).
2. *(Optional)* In `wp-config.php`, define locked production overrides (takes precedence over WP Admin UI):
   ```php
   define( 'PSIVEY_BASE_URL', 'https://api.psivey.com' );
   define( 'PSIVEY_JWT_SECRET', 'your-long-random-production-hmac-secret' );
   ```
3. Go to **WP Admin → ClicReport → Settings** to review rate limits (`60 req/min`), JWT TTL (`900s`), recipient email for `/contact`, and uninstall policy.
4. Keep **Delete All Plugin Tables on Uninstall** unchecked in production so accidental plugin deletion preserves your database tables.

### 2.4 Upgrade, Rollback & Uninstall Behavior
- **Deactivating** the plugin clears scheduled hooks/transients via `Deactivator::deactivate()` while preserving all 8 `{$wpdb->prefix}crae_*` tables and `crae_settings`.
- **Re-seeding** after updates can be run non-destructively anytime from **WP Admin → ClicReport → Verify & Re-Seed 201 Clinical Templates**.
- **Uninstalling** only drops `{$wpdb->prefix}crae_*` tables and options if **Delete All Plugin Tables on Uninstall** (`crae_settings['delete_data_on_uninstall']`) is explicitly enabled.

---

## 3. How to Connect With Your Frontend

The plugin supports **four integration paths** depending on whether you are using Gutenberg/Avada pages, migrating existing `original_files/*.php` templates, keeping the legacy bridge, or connecting an external SPA/mobile client.

### 3.1 Shortcodes (Fastest — Gutenberg, Classic Editor & Avada Builder)
Embed any assessment directly into a page or post while logged in (`read` capability required):

- **Specific clinical template**:
  ```text
  [clicreport_template slug="report-basc-3-social-emotional"]
  ```
  *(Optional custom heading: `[clicreport_template slug="report-wisc-v" title="WISC-V Cognitive Assessment"]`)*
- **Interactive multi-template sandbox** *(selector across active templates)*:
  ```text
  [clicreport_sandbox]
  ```
- **Live plugin + developer code demo** *(interactive 5-archetype preset switcher, live REST payload & response JSON inspector, and copyable JS/PHP code)*:
  ```text
  [clicreport_demo]
  ```

`ShortcodeManager` automatically checks `CapabilityManager::canViewTemplates()` and loads `crae-clinical-report.css`, `crae-api-client.js`, and localized `window.craeConfig` on pages using the shortcode.

---

### 3.2 Connecting Existing WordPress / Avada Page Templates (`original_files/*.php`)

For custom theme templates (`report-*.php`), enqueue the plugin's JS SDK in PHP instead of inline hardcoded scoring scripts or raw `fetch('/backend/api/generate_report.php')`:

1. **Enqueue plugin assets cleanly inside your template before `get_header()`** *(or via `wp_enqueue_scripts`)*:
   ```php
   if ( function_exists( 'crae' ) ) {
       crae()->container()
           ->get( \ClicReport\AssessmentEngine\Frontend\AssetLoader::class )
           ->enqueueFrontendAssets();
   }
   ```
   This injects `window.craeConfig` containing:
   - `craeConfig.restUrl` -> `/wp-json/clicreport/v1/reports/generate`
   - `craeConfig.templatesUrl` -> `/wp-json/clicreport/v1/templates`
   - `craeConfig.psiveyTokenUrl` -> `/wp-json/psivey/v1/generate-token`
   - `craeConfig.contactUrl` -> `/wp-json/clicreport/v1/contact`
   - `craeConfig.nonce` -> fresh `wp_create_nonce( 'wp_rest' )`

2. **Generate reports from your template JavaScript via `window.ClicReportAPI`**:
   ```javascript
   async function generateReport(formPayload) {
     const result = await window.ClicReportAPI.generateReport({
       template_name: 'report-basc-3-social-emotional',
       first_name: formPayload.first_name,
       gender: formPayload.gender, // 'he/him', 'she/her', 'they/them', 'ze/hir', 'ze/zir', 'xey/xem'
       '0': 'Two-row',             // rater count selector ('0'=2, '1'=3, '2'=4, '3'=5)
       informant_one: formPayload.informant_one,
       informant_one_relationship: formPayload.informant_one_relationship,
       hyperactivity_0: 74,
       depression_0: 65
     });

     // Normalized response works identically to legacy format:
     document.getElementById('narrative-container').innerHTML = result.narrative_html;
     console.log(result.scores_evaluated, result.execution_time_ms, result.request_id);
   }
   ```
   `ClicReportAPI.generateReport()` automatically attaches `X-WP-Nonce` and `credentials: 'same-origin'` when running inside WordPress, unwraps the `{ success: true, data: ... }` REST envelope, and falls back to `backend/api/generate_report.php` when previewing standalone outside WordPress.

3. **Connect Psivey AI / Billing / Chat templates (`psivey-*.php`)**:
   - Call `const tokenRes = await window.ClicReportAPI.getPsiveyToken();` (hits `POST /wp-json/psivey/v1/generate-token` with `X-WP-Nonce`) to obtain `{ token, expires_in, expires_at }`.
   - Use `tokenRes.token` as `Authorization: Bearer <token>` against Psivey cloud endpoints.

---

### 3.3 Legacy Endpoint Bridge (`backend/api/generate_report.php`)

Templates that still POST to `backend/api/generate_report.php` automatically operate in dual mode:
- **Inside WordPress (`wp-load.php` detected)**: Validates logged-in capability + `X-WP-Nonce` (or Bearer token), enforces `RateLimiter`, sanitizes input, and delegates directly to `DatabaseReportEngine`.
- **Emergency Rollback**: Defining `define( 'CRAE_USE_SQLITE_FALLBACK', true );` in `wp-config.php` switches the bridge back to the local SQLite `ReportFactory`.

---

### 3.4 Complete REST API Reference

Base URL: `https://your-site.com/wp-json/`

| Method | Route | Auth / Capability | Purpose |
| :--- | :--- | :--- | :--- |
| `POST` | `/clicreport/v1/reports/generate` | Logged-in (`read`) + Nonce or Bearer/App Pass | Generate clinical scores & sanitized HTML narrative. |
| `GET` | `/clicreport/v1/templates` | Logged-in (`read`) | List paginated/filtered templates (`page`, `per_page`, `archetype`, `category`, `search`). |
| `GET` | `/clicreport/v1/templates/{slug}` | Logged-in (`read`) | Get single template metadata, sections, scales, and decision options. |
| `PUT` | `/clicreport/v1/templates/{slug}` | Admin (`manage_options`) | Update whitelisted template metadata (`template_name`, `publisher`, `category`, `intro_narrative`, `is_active`). |
| `GET` | `/clicreport/v1/audit-logs` | Admin (`manage_options`) | Paginated non-PII execution audit log (`page`, `per_page`). |
| `POST` | `/psivey/v1/generate-token` | Logged-in (`read`) + Nonce | Legacy-compatible Psivey JWT endpoint returning top-level `{ token, expires_in, expires_at }`. |
| `POST` | `/clicreport/v1/auth/token` | Logged-in (`read`) | Wrapped `ApiResponse` JWT endpoint for external clients. |
| `POST` | `/clicreport/v1/auth/revoke` | Logged-in (`read`) | Immediately revokes all prior JWT tokens for current user. |
| `POST` | `/clicreport/v1/contact` | Public (rate-limited: `5 / 10 min`) | Secure `wp_mail()` contact notification endpoint (`name`, `email`, `subject`, `message`). |

---

## 4. Automated Test Suite Verification

Run the built-in CLI test harness anytime before packaging or after clinical rule edits:

```powershell
php clicreport-assessment-engine/tests/run.php
```

This runs 5 suites (**1,099 assertions**) covering normative scoring math, pronoun/grammar formatting, all 5 Archetype processors, all **201 templates** with XSS/latency checks, and security controls (`CapabilityManager`, SQLi/XSS input scrubbing, mass-assignment protection, JWT tampering/revocation, rate limiting, file upload validation, and CORS allowlisting).

