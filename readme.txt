=== ClicReport Assessment & Clinical Narrative Engine ===
Contributors: clicreport
Tags: assessment, psychology, clinical reports, scoring, shortcode
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: Proprietary
License URI: https://clicreport.com

Enterprise psychoeducational assessment scoring, multi-rater clinical narrative generation across 201 templates (5 psychometric archetypes), and Psivey JWT integration for WordPress.

== Description ==

ClicReport Assessment & Clinical Narrative Engine moves psychometric scoring, multi-rater comparisons, and clinical narrative synthesis from client-side scripts into a secure WordPress MySQL + REST API architecture:

* 201 clinical templates & 114+ psychometric scales seeded transactionally into `{$wpdb->prefix}crae_*` tables.
* 5 Psychometric Archetypes (`t_score`, `dual_standard_scaled`, `clinical_cutoff`, `static_reference`, `qualitative_matrix`).
* Non-PII HIPAA/FERPA audit logging (no examinee names, DOBs, scores, or raw IPs stored in audit logs).
* Strict security layer: WordPress capability mapping (`read` / `manage_options`), `X-WP-Nonce` CSRF verification, `wp_kses()` output sanitization, sliding-window rate limiting, strict CORS, and HS256 JWT issuance/revocation for Psivey APIs.
* Drop-in shortcodes (`[clicreport_template]` and `[clicreport_sandbox]`) and universal JS client (`window.ClicReportAPI`).

== Installation ==

1. Upload the `clicreport-assessment-engine` folder to `/wp-content/plugins/clicreport-assessment-engine/` or upload the plugin `.zip` via **Plugins -> Add New -> Upload Plugin**.
2. Activate the plugin via **Plugins -> Installed Plugins**.
3. Ensure **Settings -> Permalinks** uses a non-plain permalink structure (e.g., **Post name**) so `/wp-json/clicreport/v1/*` routes resolve.
4. Visit **ClicReport -> Dashboard** in WordPress Admin to confirm `201 / 201` templates are active, and configure **ClicReport -> Settings**.

== Shortcodes ==

* `[clicreport_template slug="report-basc-3-social-emotional"]` — Embeds a specific clinical assessment form and report generator.
* `[clicreport_sandbox]` — Embeds an interactive template picker across active clinical assessments.
* `[clicreport_demo]` — Interactive live plugin + code demo showing all 5 archetypes, live JS snippets, and request/response JSON.

== Changelog ==

= 1.0.0 =
* Full WordPress MySQL `$wpdb` schema (`8` tables) and idempotent 201-template catalog seeding.
* Modular 5-Archetype scoring and `wp_kses()`-hardened narrative engine.
* REST API (`clicreport/v1` and `psivey/v1`), WordPress Admin UI, Shortcodes, and legacy bridge.

