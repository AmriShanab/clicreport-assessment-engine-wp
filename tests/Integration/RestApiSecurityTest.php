<?php
/**
 * Integration Security Test Suite: Capabilities, CSRF, Sanitization,
 * Mass-Assignment, JWT Revocation, Rate Limiting, File Uploads & CORS
 *
 * @package ClicReport\AssessmentEngine\Tests\Integration
 */

declare(strict_types=1);

use ClicReport\AssessmentEngine\API\Requests\GenerateReportRequest;
use ClicReport\AssessmentEngine\API\Requests\TemplateMutationRequest;
use ClicReport\AssessmentEngine\Authentication\PsiveyTokenService;
use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Security\CorsManager;
use ClicReport\AssessmentEngine\Security\FileValidator;
use ClicReport\AssessmentEngine\Security\InputSanitizer;
use ClicReport\AssessmentEngine\Security\RateLimiter;

final class RestApiSecurityTest {

    public function run(): void {
        $container = crae()->container();

        // 1. Authorization / Built-in WP Capability Guards (no custom roles)
        /** @var CapabilityManager $caps */
        $caps = $container->get( CapabilityManager::class );

        $GLOBALS['crae_test_logged_in'] = false;
        CraeAssert::false( $caps->canViewTemplates(), 'Unauthenticated blocked from viewing templates' );
        CraeAssert::false( $caps->canGenerateReports(), 'Unauthenticated blocked from generating reports' );

        $GLOBALS['crae_test_logged_in'] = true;
        $GLOBALS['crae_test_user_caps'] = [ 'read' => true, 'manage_options' => false ];
        CraeAssert::true( $caps->canViewTemplates(), 'Subscriber can view templates' );
        CraeAssert::true( $caps->canGenerateReports(), 'Subscriber can generate reports' );
        CraeAssert::false( $caps->canManageTemplates(), 'Subscriber blocked from managing templates' );
        CraeAssert::false( $caps->canManageSettings(), 'Subscriber blocked from managing settings' );
        CraeAssert::false( $caps->canViewAuditLogs(), 'Subscriber blocked from viewing audit logs' );

        $GLOBALS['crae_test_user_caps'] = [ 'read' => true, 'manage_options' => true ];
        CraeAssert::true( $caps->canManageTemplates(), 'Admin can manage templates' );
        CraeAssert::true( $caps->canViewAuditLogs(), 'Admin can view audit logs' );

        // 2. Request Sanitization & SQLi / XSS Slug Protection
        /** @var InputSanitizer $sanitizer */
        $sanitizer  = $container->get( InputSanitizer::class );
        $reqHelper  = new GenerateReportRequest( $sanitizer );
        $dirtyReq   = new WP_REST_Request(
            [
                'template_name' => "report-scq' OR 1=1;--",
                'first_name'    => '<img src=x onerror=alert(1)>Jordan',
                'gender'        => '<script>bad</script>',
                'total_score_0' => '18',
            ]
        );
        $cleanPayload = $reqHelper->sanitizePayload( $dirtyReq );
        CraeAssert::notContains( "'", $cleanPayload['template_name'], 'SQLi single quote stripped from template_name' );
        CraeAssert::notContains( ';', $cleanPayload['template_name'], 'SQLi semicolon stripped from template_name' );
        CraeAssert::notContains( '=', $cleanPayload['template_name'], 'SQLi equals sign stripped from template_name' );
        CraeAssert::same( 'Jordan', $cleanPayload['first_name'], 'HTML/img tag stripped from examinee name' );
        CraeAssert::same( 'male', $cleanPayload['gender'], 'Invalid pronoun falls back to whitelist default' );
        CraeAssert::same( 18, $cleanPayload['total_score_0'], 'Numeric string typed cleanly' );

        // 3. Mass-Assignment Protection on TemplateMutationRequest
        $mutationValidator = new TemplateMutationRequest();
        $mutReq            = new WP_REST_Request(
            [
                'template_name' => 'Updated Name',
                'id'            => 9999,
                'template_slug' => 'hijacked-slug',
                'archetype'     => 'admin_override',
            ]
        );
        $existing = [
            'id'            => 42,
            'template_slug' => 'report-scq',
            'template_name' => 'Original Name',
            'archetype'     => 'cutoff_gatekeeper',
        ];
        $merged = $mutationValidator->extractAllowedUpdates( $mutReq, $existing );
        CraeAssert::same( 42, $merged['id'], 'Immutable id preserved against mass-assignment' );
        CraeAssert::same( 'report-scq', $merged['template_slug'], 'Immutable template_slug preserved' );
        CraeAssert::same( 'cutoff_gatekeeper', $merged['archetype'], 'Immutable archetype preserved' );
        CraeAssert::same( 'Updated Name', $merged['template_name'], 'Allowed template_name updated' );

        // 4. JWT Signing, Tampering Detection & Token Revocation
        /** @var PsiveyTokenService $tokens */
        $tokens = $container->get( PsiveyTokenService::class );
        $user   = new WP_User( 10, 'clinician', 'clinician@clinic.test' );
        $issued = $tokens->issueForUser( $user );
        $claims = $tokens->verifyToken( $issued['token'] );
        CraeAssert::true( is_array( $claims ) && 10 === (int) $claims['sub'], 'Valid JWT verified' );

        $tampered = $issued['token'] . 'x';
        $badSig   = $tokens->verifyToken( $tampered );
        CraeAssert::true( is_wp_error( $badSig ) && 'crae_invalid_signature' === $badSig->get_error_code(), 'Tampered JWT rejected' );

        $tokens->revokeUserTokens( 10 );
        $revoked = $tokens->verifyToken( $issued['token'] );
        CraeAssert::true( is_wp_error( $revoked ) && 'crae_token_revoked' === $revoked->get_error_code(), 'Revoked JWT rejected' );

        // 5. Rate Limiter Enforcement (429)
        /** @var RateLimiter $limiter */
        $limiter = $container->get( RateLimiter::class );
        $GLOBALS['crae_test_transients'] = [];
        CraeAssert::true( true === $limiter->check( 'sec_test', 'actor_1', 2, 60 ), 'Rate limit 1/2 allowed' );
        CraeAssert::true( true === $limiter->check( 'sec_test', 'actor_1', 2, 60 ), 'Rate limit 2/2 allowed' );
        $blocked = $limiter->check( 'sec_test', 'actor_1', 2, 60 );
        CraeAssert::true( is_wp_error( $blocked ) && 'crae_rate_limit_exceeded' === $blocked->get_error_code(), 'Rate limit 3/2 triggers 429' );

        // 6. File Upload Validator blocks non-uploaded / disallowed extensions
        /** @var FileValidator $files */
        $files   = $container->get( FileValidator::class );
        $badFile = $files->validate(
            [
                'name'     => 'shell.php',
                'tmp_name' => __FILE__, // Not uploaded via HTTP POST -> is_uploaded_file() false.
                'error'    => UPLOAD_ERR_OK,
                'size'     => 128,
            ]
        );
        CraeAssert::true( is_wp_error( $badFile ), 'Non-HTTP-uploaded / dangerous file rejected' );

        // 7. Strict Origin Allowlist in CorsManager
        /** @var CorsManager $cors */
        $cors   = $container->get( CorsManager::class );
        $ref    = new ReflectionMethod( $cors, 'getAllowedOrigins' );
        $ref->setAccessible( true );
        $origins = $ref->invoke( $cors );
        CraeAssert::true( in_array( 'https://clinic.test', $origins, true ), 'Site origin present in CORS allowlist' );
        CraeAssert::false( in_array( '*', $origins, true ), 'Wildcard * absent from CORS allowlist' );
    }
}
