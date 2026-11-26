<?php

/**
 * AJAX Handler for ClicReport Assessment Engine 
 * 
 * Handles admin-ajax.php requests fro report generation
 * 
 * @package ClicReport\AssessmentEngine\API
 */

namespace ClicReport\AssessmentEngine\API;

use ClicReport\AssessmentEngine\Authentication\PsiveyTokenService;
use ClicReport\AssessmentEngine\Security\ClientGuard;
use ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine;

defined('ABSPATH') || exit;

class AjaxHandler
{
    /**
     * @param DatabaseReportEngine    $engine       The scoring & narrative report engine
     * @param ClientGuard|null        $clientGuard  Anti-Postman & request verification service
     * @param PsiveyTokenService|null $tokenService One-time Psivey-Token issuance & verification
     */

    public function __construct(
        private DatabaseReportEngine $engine,
        private ?ClientGuard $clientGuard = null,
        private ?PsiveyTokenService $tokenService = null
    ) {}

    /**
     * Register WordPress AJAX action hooks.
     */

    public function registerHooks(): void
    {
        // Logged-in users report generation
        add_action(
            'wp_ajax_crae_generate_report',
            [$this, 'handleGenerateReport']
        );

        // One-time Psivey-Token refresh endpoint
        add_action(
            'wp_ajax_crae_get_psivey_token',
            [$this, 'handleGetPsiveyToken']
        );

        // Non-logged-in (guest) users
        add_action(
            'wp_ajax_nopriv_crae_generate_report',
            [$this, 'handleGenerateReport']
        );
    }
    /**
     * Handle report generation request
     * 
     */

    public function handleGenerateReport(): void
    {
        // Strictly block non-logged in users from generating reports or accessing data
        if (! is_user_logged_in()) {
            wp_send_json_error([
                'message' => 'Authentication required. You must be logged in to generate reports or access assessment data.',
            ], 401);
            return;
        }

        // Anti-Postman & Direct Client Tool Verification (blocks Postman, curl, scrapers, invalid origins)
        if (null !== $this->clientGuard) {
            $clientCheck = $this->clientGuard->verifyBrowserRequest();
            if (is_wp_error($clientCheck)) {
                wp_send_json_error([
                    'message' => $clientCheck->get_error_message(),
                ], 403);
                return;
            }

            // User Subscriber Verification
            $subCheck = $this->clientGuard->verifySubscription((int) get_current_user_id());
            if (is_wp_error($subCheck)) {
                wp_send_json_error([
                    'message' => $subCheck->get_error_message(),
                ], 401);
                return;
            }
        }

        if (! check_ajax_referer('crae_ajax_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => 'Security check failed. Please refresh this page and try again.',
            ], 403);
            return;
        }

        // Enforce one-time Psivey-Token consumption
        if (null !== $this->tokenService) {
            $psiveyToken = $this->extractPsiveyToken();
            $tokenResult = $this->tokenService->verifyAndConsumeToken($psiveyToken, (int) get_current_user_id());
            if (is_wp_error($tokenResult)) {
                wp_send_json_error([
                    'message' => $tokenResult->get_error_message(),
                ], $tokenResult->get_error_data()['status'] ?? 403);
                return;
            }
        }

        $payload = [];
        if (isset($_POST['payload'])) {
            if (is_array($_POST['payload'])) {
                $payload = $_POST['payload'];
            } elseif (is_string($_POST['payload'])) {
                $payload = json_decode(wp_unslash($_POST['payload']), true) ?: [];
            }
        }

        // If no nested payload array was supplied, fall back to flat $_POST fields
        if (empty($payload) && !empty($_POST)) {
            $payload = $_POST;
            unset($payload['action'], $payload['nonce'], $payload['_ajax_nonce']);
        }

        $templateSlug = sanitize_text_field($payload['template_slug'] ?? ($payload['template_name'] ?? ''));
        if (empty($templateSlug)) {
            wp_send_json_error([
                'message' => 'Missing required parameter: template_slug',
            ], 400);
            return;
        }

        try {
            $userId = get_current_user_id();
            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

            $result = $this->engine->generate($payload, $userId, $clientIp);

            // Automatically issue the next one-time Psivey-Token for subsequent requests
            if (null !== $this->tokenService && $userId > 0) {
                $currentUser = wp_get_current_user();
                if ($currentUser->exists()) {
                    $nextIssued = $this->tokenService->issueForUser($currentUser);
                    $result['next_psivey_token'] = $nextIssued['token'] ?? '';
                }
            }

            wp_send_json_success($result);
        } catch (\Throwable $e) {
            if ($e instanceof \WPDieException || get_class($e) === 'AjaxDieException') {
                throw $e;
            }
            $errorData = [
                'message' => $e->getMessage(),
            ];
            // If the token was consumed before this error occurred, issue a replacement token so subsequent retries succeed without page refresh
            if (null !== $this->tokenService && $userId > 0) {
                $currentUser = wp_get_current_user();
                if ($currentUser->exists()) {
                    $nextIssued = $this->tokenService->issueForUser($currentUser);
                    $errorData['next_psivey_token'] = $nextIssued['token'] ?? '';
                }
            }
            wp_send_json_error($errorData, 500);
        }
    }

    /**
     * AJAX endpoint to generate a fresh one-time Psivey-Token on demand.
     */
    public function handleGetPsiveyToken(): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(['message' => 'Authentication required.'], 401);
            return;
        }

        if (null !== $this->clientGuard) {
            $clientCheck = $this->clientGuard->verifyBrowserRequest();
            if (is_wp_error($clientCheck)) {
                wp_send_json_error([
                    'message' => $clientCheck->get_error_message(),
                ], 403);
                return;
            }
        }

        if (! check_ajax_referer('crae_ajax_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => 'Security check failed.',
            ], 403);
            return;
        }

        if (null !== $this->tokenService) {
            $user = wp_get_current_user();
            $issued = $this->tokenService->issueForUser($user);
            wp_send_json_success($issued);
            return;
        }

        wp_send_json_error(['message' => 'Token service unavailable.'], 500);
    }

    /**
     * Extract Psivey-Token from headers, POST data, or payload.
     */
    private function extractPsiveyToken(): string
    {
        // 1. Check HTTP headers (standard casing and X- prefixed)
        if (! empty($_SERVER['HTTP_PSIVEY_TOKEN'])) {
            return trim((string) $_SERVER['HTTP_PSIVEY_TOKEN']);
        }
        if (! empty($_SERVER['HTTP_X_PSIVEY_TOKEN'])) {
            return trim((string) $_SERVER['HTTP_X_PSIVEY_TOKEN']);
        }

        // 2. Check direct POST field
        if (! empty($_POST['psivey_token'])) {
            return trim((string) $_POST['psivey_token']);
        }

        // 3. Check within nested payload
        if (isset($_POST['payload'])) {
            if (is_array($_POST['payload']) && ! empty($_POST['payload']['psivey_token'])) {
                return trim((string) $_POST['payload']['psivey_token']);
            }
            if (is_string($_POST['payload'])) {
                $decoded = json_decode(wp_unslash($_POST['payload']), true);
                if (is_array($decoded) && ! empty($decoded['psivey_token'])) {
                    return trim((string) $decoded['psivey_token']);
                }
            }
        }

        return '';
    }
}
