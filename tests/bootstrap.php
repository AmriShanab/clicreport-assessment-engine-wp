<?php

/**
 * Standalone WordPress + Clinical Data Test Bootstrap for ClicReport Plugin
 *
 * Loads the plugin autoloader, WordPress function/class shims, assertion utilities,
 * and an in-memory $wpdb engine pre-populated from config/seed-data/*.json so all
 * 201 templates and security controls can run cleanly in CLI/CI without MySQL.
 *
 * @package ClicReport\AssessmentEngine\Tests
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}
if (! defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (! defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (! isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
}
if (! isset($_SERVER['HTTP_ORIGIN'])) {
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost';
}
if (! isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = 'http://localhost/assessment';
}
if (! isset($_SERVER['HTTP_HOST'])) {
    $_SERVER['HTTP_HOST'] = 'localhost';
}

global $wpdb, $table_prefix, $wp_version;
global $crae_test_options, $crae_test_user_meta, $crae_test_transients, $crae_test_cache;
global $crae_test_logged_in, $crae_test_user_id, $crae_test_user_caps;
global $crae_test_actions, $crae_test_filters, $crae_test_shortcodes, $crae_test_sent_mails;

$wp_version                    = '6.5';
$table_prefix                  = 'wp_';
$crae_test_logged_in           = true;
$crae_test_user_id             = 10;
$crae_test_user_caps           = ['read' => true, 'manage_options' => true];
$crae_test_user_meta           = [];
$crae_test_transients          = [];
$crae_test_cache               = [];
$crae_test_actions             = [];
$crae_test_filters             = [];
$crae_test_shortcodes          = [];
$crae_test_sent_mails          = [];
$crae_test_options             = [
    'crae_db_version' => '1.0.0',
    'admin_email'     => 'admin@clinic.test',
    'crae_settings'   => [
        'psivey_base_url'          => 'https://api.psivey.com',
        'psivey_jwt_secret'        => 'test-jwt-secret-key-2026',
        'psivey_jwt_ttl_seconds'   => 900,
        'rate_limit_per_minute'    => 60,
        'contact_recipient_email'  => 'clinic@clinic.test',
        'delete_data_on_uninstall' => false,
    ],
];

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(
            private string $code = '',
            private string $message = '',
            private mixed $data = ''
        ) {}
        public function get_error_code(): string
        {
            return $this->code;
        }
        public function get_error_message(): string
        {
            return $this->message;
        }
        public function get_error_data(): mixed
        {
            return $this->data;
        }
    }
}

if (! class_exists('WP_User')) {
    class WP_User
    {
        public function __construct(
            public int $ID = 10,
            public string $user_login = 'clinician',
            public string $user_email = 'clinician@clinic.test'
        ) {}
        public function exists(): bool
        {
            return $this->ID > 0;
        }
    }
}

if (! class_exists('WP_HTTP_Response')) {
    class WP_HTTP_Response
    {
        public function __construct(public mixed $data = null, public int $status = 200) {}
    }
}

if (! class_exists('WP_REST_Response')) {
    class WP_REST_Response extends WP_HTTP_Response {}
}

if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @param array<string, mixed> $params */
        public function __construct(private array $params = [], private string $route = '/clicreport/v1/reports/generate') {}
        public function get_params(): array
        {
            return $this->params;
        }
        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }
        public function has_param(string $key): bool
        {
            return array_key_exists($key, $this->params);
        }
        public function get_route(): string
        {
            return $this->route;
        }
    }
}

if (! class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const READABLE  = 'GET';
        public const CREATABLE = 'POST';
        public const EDITABLE  = 'POST, PUT, PATCH';
    }
}

function is_wp_error(mixed $thing): bool
{
    return $thing instanceof WP_Error;
}
function plugin_dir_path(string $f): string
{
    return rtrim(str_replace('\\', '/', dirname($f)), '/') . '/';
}
function plugin_dir_url(string $f): string
{
    return 'https://clinic.test/wp-content/plugins/clicreport-assessment-engine/';
}
function plugin_basename(string $f): string
{
    return 'clicreport-assessment-engine/clicreport-assessment-engine.php';
}
function register_activation_hook(string $f, callable $cb): void {}
function register_deactivation_hook(string $f, callable $cb): void {}
function add_action(string $tag, callable $cb, int $p = 10, int $args = 1): void
{
    global $crae_test_actions;
    $crae_test_actions[$tag][] = $cb;
}
function add_filter(string $tag, callable $cb, int $p = 10, int $args = 1): void
{
    global $crae_test_filters;
    $crae_test_filters[$tag][] = $cb;
}
function add_shortcode(string $tag, callable $cb): void
{
    global $crae_test_shortcodes;
    $crae_test_shortcodes[$tag] = $cb;
}
function apply_filters(string $tag, mixed $val, mixed ...$args): mixed
{
    return $val;
}
function do_action(string $tag, mixed ...$args): void {}
function load_plugin_textdomain(string $d, bool $dep, string $p): void {}
function is_admin(): bool
{
    return false;
}
function home_url(string $p = ''): string
{
    return 'https://clinic.test' . $p;
}
function rest_url(string $p = ''): string
{
    return 'https://clinic.test/wp-json/' . ltrim($p, '/');
}
function is_user_logged_in(): bool
{
    global $crae_test_logged_in;
    return (bool) $crae_test_logged_in;
}
function get_current_user_id(): int
{
    global $crae_test_logged_in, $crae_test_user_id;
    return $crae_test_logged_in ? (int) $crae_test_user_id : 0;
}
function wp_get_current_user(): WP_User
{
    global $crae_test_logged_in, $crae_test_user_id;
    return $crae_test_logged_in
        ? new WP_User((int) $crae_test_user_id)
        : new WP_User(0, '', '');
}
function current_user_can(string $cap): bool
{
    global $crae_test_logged_in, $crae_test_user_caps;
    return $crae_test_logged_in && ! empty($crae_test_user_caps[$cap]);
}
function get_option(string $k, mixed $default = false): mixed
{
    global $crae_test_options;
    return $crae_test_options[$k] ?? $default;
}
function update_option(string $k, mixed $val): bool
{
    global $crae_test_options;
    $crae_test_options[$k] = $val;
    return true;
}
function get_user_meta(int $uid, string $k, bool $single = false): mixed
{
    global $crae_test_user_meta;
    return $crae_test_user_meta[$uid][$k] ?? ($single ? '' : []);
}
function update_user_meta(int $uid, string $k, mixed $v): bool
{
    global $crae_test_user_meta;
    $crae_test_user_meta[$uid][$k] = $v;
    return true;
}
function get_transient(string $k): mixed
{
    global $crae_test_transients;
    return $crae_test_transients[$k] ?? false;
}
function set_transient(string $k, mixed $v, int $exp = 0): bool
{
    global $crae_test_transients;
    $crae_test_transients[$k] = $v;
    return true;
}
function delete_transient(string $k): bool
{
    global $crae_test_transients;
    unset($crae_test_transients[$k]);
    return true;
}
function wp_cache_get(string $k, string $g = '', bool $force = false, ?bool &$found = null): mixed
{
    global $crae_test_cache;
    $key    = $g . ':' . $k;
    $exists = array_key_exists($key, $crae_test_cache);
    if (null !== $found) {
        $found = $exists;
    }
    return $exists ? $crae_test_cache[$key] : false;
}
function wp_cache_set(string $k, mixed $v, string $g = '', int $exp = 0): bool
{
    global $crae_test_cache;
    $crae_test_cache[$g . ':' . $k] = $v;
    return true;
}
function wp_cache_delete(string $k, string $g = ''): bool
{
    global $crae_test_cache;
    unset($crae_test_cache[$g . ':' . $k]);
    return true;
}
function wp_salt(string $scheme = 'auth'): string
{
    return 'crae-test-salt-' . $scheme;
}
function wp_json_encode(mixed $data): string|false
{
    return json_encode($data);
}
function wp_json_file_decode(string $file, array $opts = []): mixed
{
    $raw = file_get_contents($file);
    return false !== $raw ? json_decode($raw, ! empty($opts['associative'])) : null;
}
function sanitize_key(string $key): string
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)) ?? '';
}
function sanitize_text_field(string $str): string
{
    $noScripts = preg_replace('@<(script|style)[^>]*?>.*?</\1>@si', '', $str) ?? $str;
    return trim(preg_replace('/[\r\n\t]+/', ' ', strip_tags($noScripts)) ?? '');
}
function sanitize_email(string $email): string
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL) ?: '';
}
function sanitize_file_name(string $name): string
{
    return preg_replace('/[^a-zA-Z0-9._\-]/', '-', basename($name)) ?? 'file';
}
function wp_unslash(mixed $val): mixed
{
    return $val;
}
function esc_html(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function esc_attr(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
function esc_url_raw(string $url): string
{
    return filter_var($url, FILTER_SANITIZE_URL) ?: '';
}
function wp_kses_post(string $html): string
{
    return strip_tags($html, '<div><p><span><strong><em><ul><ol><li><table><thead><tbody><tr><th><td><h3><h4><h5><br>');
}
function wp_kses(string $html, array $allowedHtml = []): string
{
    $tags = '';
    foreach (array_keys($allowedHtml) as $tag) {
        $tags .= '<' . $tag . '>';
    }
    $clean = strip_tags($html, $tags);
    return preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
}
function absint(mixed $n): int
{
    return abs((int) $n);
}
function wp_create_nonce(string $action = '-1'): string
{
    return hash_hmac('sha256', (string) $action, 'nonce-key');
}
function wp_verify_nonce(string $nonce, string|int $action = -1): false|int
{
    $expected = wp_create_nonce((string) $action);
    return hash_equals($expected, $nonce) ? 1 : false;
}
function check_ajax_referer(int|string $action = -1, false|string $query_arg = false, bool $die = true): false|int
{
    $nonce = '';
    if (false !== $query_arg && isset($_REQUEST[$query_arg])) {
        $nonce = (string) $_REQUEST[$query_arg];
    } elseif (isset($_REQUEST['_ajax_nonce'])) {
        $nonce = (string) $_REQUEST['_ajax_nonce'];
    } elseif (isset($_REQUEST['_wpnonce'])) {
        $nonce = (string) $_REQUEST['_wpnonce'];
    }
    $valid = wp_verify_nonce($nonce, $action);
    if (false === $valid && $die) {
        wp_die('-1', 403);
    }
    return $valid;
}
function wp_parse_args(mixed $args, array $defaults): array
{
    return array_merge($defaults, is_array($args) ? $args : []);
}
function wp_unique_filename(string $dir, string $name): string
{
    return 'uniq-' . $name;
}
function wp_check_filetype_and_ext(string $file, string $filename, array $mimes = []): array
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ('' !== $ext && isset($mimes[$ext])) {
        return ['ext' => $ext, 'type' => $mimes[$ext], 'proper_filename' => $filename];
    }
    return ['ext' => false, 'type' => false, 'proper_filename' => false];
}
function wp_mail(string|array $to, string $subject, string $message, string|array $headers = '', string|array $attachments = []): bool
{
    global $crae_test_sent_mails;
    $crae_test_sent_mails[] = compact('to', 'subject', 'message', 'headers', 'attachments');
    return true;
}
function admin_url(string $p = ''): string
{
    return 'https://clinic.test/wp-admin/' . ltrim($p, '/');
}
function site_url(string $path = '', ?string $scheme = null): string
{
    return 'https://clinic.test/' . ltrim($path, '/');
}
function wp_login_url(string $redirect = '', bool $force_reauth = false): string
{
    $url = 'https://clinic.test/wp-login.php';
    return '' !== $redirect ? $url . '?redirect_to=' . urlencode($redirect) : $url;
}

if (! class_exists('AjaxDieException')) {
    class AjaxDieException extends \RuntimeException {}
}
if (! class_exists('WPDieException')) {
    class WPDieException extends \RuntimeException {}
}

function wp_die(string|\WP_Error $message = '', string|int $title = '', string|array|int $args = []): void
{
    throw new WPDieException(is_wp_error($message) ? $message->get_error_message() : (string) $message);
}

function wp_send_json(mixed $response, ?int $status_code = null, int $options = 0): void
{
    if (null !== $status_code && ! headers_sent()) {
        http_response_code($status_code);
    }
    echo wp_json_encode($response);
    wp_die();
}

function wp_send_json_success(mixed $data = null, ?int $status_code = null, int $options = 0): void
{
    $response = ['success' => true];
    if (isset($data)) {
        $response['data'] = $data;
    }
    wp_send_json($response, $status_code, $options);
}

function wp_send_json_error(mixed $data = null, ?int $status_code = null, int $options = 0): void
{
    $response = ['success' => false];
    if (isset($data)) {
        $response['data'] = $data;
    }
    wp_send_json($response, $status_code, $options);
}

function wp_register_style(string $handle, string|false $src = false, array $deps = [], string|bool|null $ver = false, string $media = 'all'): bool
{
    return true;
}
function wp_register_script(string $handle, string|false $src = false, array $deps = [], string|bool|null $ver = false, bool|array $args = false): bool
{
    return true;
}
function wp_localize_script(string $handle, string $object_name, array $l10n): bool
{
    return true;
}
function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, string $media = 'all'): void {}
function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string|bool|null $ver = false, bool|array $args = false): void {}
function wp_script_is(string $handle, string $status = 'enqueued'): bool
{
    return true;
}

/**
 * In-Memory $wpdb Shim seeded from config/seed-data/*.json for full 201-template regression tests.
 */
if (! class_exists('wpdb')) {
    class wpdb
    {
        public string $prefix    = 'wp_';
        public int $insert_id    = 0;
        public array $templates  = [];
        public array $sections   = [];
        public array $scales     = [];
        public array $ranges     = [];
        public array $rules      = [];
        public array $validity   = [];
        public array $decisions  = [];
        public array $auditLogs  = [];

        public function __construct(string $dbuser = '', string $dbpassword = '', string $dbname = '', string $dbhost = '') {}

        public function loadSeedData(string $seedDir): void
        {
            $tplRows   = json_decode((string) file_get_contents($seedDir . 'templates.json'), true) ?: [];
            $ruleData  = json_decode((string) file_get_contents($seedDir . 'clinical-rules.json'), true) ?: [];
            $slugToId  = [];
            $nextTplId = 1;

            foreach ($tplRows as $row) {
                $id                    = $nextTplId++;
                $slug                  = (string) $row['template_slug'];
                $slugToId[$slug]     = $id;
                $this->templates[$id] = array_merge(
                    $row,
                    [
                        'id'          => $id,
                        'is_active'   => $row['is_active'] ?? 1,
                        'config_json' => isset($row['config_json']) && ! is_string($row['config_json']) ? json_encode($row['config_json']) : ($row['config_json'] ?? null),
                    ]
                );
            }

            foreach (($ruleData['score_ranges'] ?? []) as $idx => $r) {
                $tplSlug         = $r['template_slug'] ?? null;
                $r['id']         = $idx + 1;
                $r['template_id'] = null !== $tplSlug ? ($slugToId[$tplSlug] ?? null) : null;
                $this->ranges[]  = $r;
            }

            $secKeyToId = [];
            $nextSecId  = 1;
            foreach (($ruleData['sections'] ?? []) as $sec) {
                $tid = $slugToId[$sec['template_slug'] ?? ''] ?? null;
                if (null === $tid) {
                    continue;
                }
                $sid              = $nextSecId++;
                $sec['id']        = $sid;
                $sec['template_id'] = $tid;
                $this->sections[] = $sec;
                $secKeyToId[$sec['template_slug'] . ':' . $sec['section_key']] = $sid;
            }

            $scaleKeyToId = [];
            $nextScaleId  = 1;
            foreach (($ruleData['scales'] ?? []) as $sc) {
                $tid = $slugToId[$sc['template_slug'] ?? ''] ?? null;
                if (null === $tid) {
                    continue;
                }
                $sid                = $nextScaleId++;
                $sc['id']           = $sid;
                $sc['template_id']  = $tid;
                $sc['section_id']   = $secKeyToId[($sc['template_slug'] ?? '') . ':' . ($sc['section_key'] ?? '')] ?? null;
                $this->scales[]     = $sc;
                $scaleKeyToId[($sc['template_slug'] ?? '') . ':' . $sc['scale_key']] = $sid;
            }

            foreach (($ruleData['narrative_rules'] ?? []) as $idx => $nr) {
                $scaleId = $scaleKeyToId[($nr['template_slug'] ?? '') . ':' . ($nr['scale_key'] ?? '')] ?? null;
                if (null === $scaleId) {
                    continue;
                }
                $nr['id']       = $idx + 1;
                $nr['scale_id'] = $scaleId;
                $this->rules[]  = $nr;
            }

            foreach (($ruleData['validity_rules'] ?? []) as $idx => $vr) {
                $tid = $slugToId[$vr['template_slug'] ?? ''] ?? null;
                if (null !== $tid) {
                    $vr['id']          = $idx + 1;
                    $vr['template_id'] = $tid;
                    $this->validity[]  = $vr;
                }
            }

            foreach (($ruleData['decision_options'] ?? []) as $idx => $do) {
                $tid = $slugToId[$do['template_slug'] ?? ''] ?? null;
                if (null !== $tid) {
                    $do['id']           = $idx + 1;
                    $do['template_id']  = $tid;
                    $this->decisions[]  = $do;
                }
            }
        }

        public function esc_like(string $text): string
        {
            return addslashes($text);
        }
        public function query(string $sql): int
        {
            return 1;
        }

        public function prepare(string $query, mixed ...$args): array
        {
            if (1 === count($args) && is_array($args[0])) {
                $args = $args[0];
            }
            return ['sql' => $query, 'args' => $args];
        }

        public function get_row(string|array $prepared, string $output = ARRAY_A): ?array
        {
            $sql  = is_array($prepared) ? $prepared['sql'] : $prepared;
            $args = is_array($prepared) ? $prepared['args'] : [];
            if (str_contains($sql, 'crae_templates') && ! empty($args[0])) {
                $slug = (string) $args[0];
                foreach ($this->templates as $row) {
                    if ($row['template_slug'] === $slug) {
                        return $row;
                    }
                }
            }
            return null;
        }

        public function get_results(string|array $prepared, string $output = ARRAY_A): array
        {
            $sql  = is_array($prepared) ? $prepared['sql'] : $prepared;
            $args = is_array($prepared) ? $prepared['args'] : [];

            if (str_contains($sql, 'crae_sections')) {
                $tid = (int) ($args[0] ?? 0);
                $out = [];
                foreach ($this->sections as $s) {
                    if ((int) $s['template_id'] === $tid) {
                        $out[] = $s;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_scales')) {
                $tid = (int) ($args[0] ?? 0);
                $out = [];
                foreach ($this->scales as $s) {
                    if ((int) $s['template_id'] === $tid) {
                        $out[] = $s;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_narrative_rules')) {
                $ids = array_map('intval', $args);
                $out = [];
                foreach ($this->rules as $r) {
                    if (in_array((int) $r['scale_id'], $ids, true)) {
                        $out[] = $r;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_score_ranges')) {
                if (str_contains($sql, 'template_id IS NULL')) {
                    $type = (string) ($args[0] ?? '');
                    $out  = [];
                    foreach ($this->ranges as $r) {
                        if (null === $r['template_id'] && $r['scale_type'] === $type) {
                            $out[] = $r;
                        }
                    }
                    return $out;
                }
                $tid  = (int) ($args[0] ?? 0);
                $type = (string) ($args[1] ?? '');
                $out  = [];
                foreach ($this->ranges as $r) {
                    if ((int) $r['template_id'] === $tid && $r['scale_type'] === $type) {
                        $out[] = $r;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_validity_rules')) {
                $tid = (int) ($args[0] ?? 0);
                $out = [];
                foreach ($this->validity as $r) {
                    if ((int) $r['template_id'] === $tid) {
                        $out[] = $r;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_decision_options')) {
                $tid = (int) ($args[0] ?? 0);
                $out = [];
                foreach ($this->decisions as $r) {
                    if ((int) $r['template_id'] === $tid) {
                        $out[] = $r;
                    }
                }
                return $out;
            }
            if (str_contains($sql, 'crae_templates')) {
                return array_values($this->templates);
            }
            return [];
        }

        public function get_var(string|array $prepared): mixed
        {
            $sql = is_array($prepared) ? $prepared['sql'] : $prepared;
            if (str_contains($sql, 'crae_templates')) {
                return count($this->templates);
            }
            return 0;
        }

        public function insert(string $table, array $data, array $format = []): int
        {
            if (str_contains($table, 'crae_audit_logs')) {
                $this->insert_id     = count($this->auditLogs) + 1;
                $data['id']          = $this->insert_id;
                $this->auditLogs[]   = $data;
                return 1;
            }
            return 0;
        }
    }
}

/**
 * Minimal assertion helper so tests run directly with PHP CLI without extra packages.
 */
final class CraeAssert
{
    public static int $assertions = 0;

    public static function same(mixed $expected, mixed $actual, string $label = ''): void
    {
        self::$assertions++;
        if ($expected !== $actual) {
            throw new RuntimeException(sprintf('Assertion failed (%s): expected %s, got %s', $label, var_export($expected, true), var_export($actual, true)));
        }
    }

    public static function true(bool $cond, string $label = ''): void
    {
        self::same(true, $cond, $label);
    }

    public static function false(bool $cond, string $label = ''): void
    {
        self::same(false, $cond, $label);
    }

    public static function contains(string $needle, string $haystack, string $label = ''): void
    {
        self::$assertions++;
        if (! str_contains($haystack, $needle)) {
            throw new RuntimeException(sprintf('Assertion failed (%s): expected string containing "%s"', $label, $needle));
        }
    }

    public static function notContains(string $needle, string $haystack, string $label = ''): void
    {
        self::$assertions++;
        if (str_contains($haystack, $needle)) {
            throw new RuntimeException(sprintf('Assertion failed (%s): unexpected string "%s" found', $label, $needle));
        }
    }

    public static function notEmpty(mixed $value, string $label = ''): void
    {
        self::$assertions++;
        if (empty($value)) {
            throw new RuntimeException(sprintf('Assertion failed (%s): value should not be empty', $label));
        }
    }
}

global $wpdb;
$wpdb = new wpdb('test', 'test', 'test', 'localhost');
$wpdb->loadSeedData(dirname(__DIR__) . '/config/seed-data/');

require_once dirname(__DIR__) . '/clicreport-assessment-engine.php';
crae()->boot();
