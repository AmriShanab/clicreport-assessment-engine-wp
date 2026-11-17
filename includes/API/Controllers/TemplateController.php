<?php
/**
 * REST Controller for Assessment Template Catalog & Schema Management
 *
 * Endpoints:
 *   GET /wp-json/clicreport/v1/templates
 *   GET /wp-json/clicreport/v1/templates/{slug}
 *   PUT /wp-json/clicreport/v1/templates/{slug}
 *
 * @package ClicReport\AssessmentEngine\API\Controllers
 */

namespace ClicReport\AssessmentEngine\API\Controllers;

use ClicReport\AssessmentEngine\API\Requests\TemplateMutationRequest;
use ClicReport\AssessmentEngine\API\Responses\ApiResponse;
use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository;
use ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository;
use ClicReport\AssessmentEngine\Security\RateLimiter;

defined( 'ABSPATH' ) || exit;

final class TemplateController extends AbstractRestController {

    public function __construct(
        CapabilityManager $capabilities,
        RateLimiter $rateLimiter,
        private TemplateRepository $templateRepo,
        private ScaleRepository $scaleRepo,
        private NarrativeRuleRepository $narrativeRepo,
        private TemplateMutationRequest $mutationValidator,
        private ?\ClicReport\AssessmentEngine\Security\ClientGuard $clientGuard = null
    ) {
        parent::__construct( $capabilities, $rateLimiter );
    }

    public function registerRoutes(): void {
        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/templates',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'index' ],
                'permission_callback' => [ $this->capabilities, 'canViewTemplates' ],
                'args'                => [
                    'page'      => [ 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'sanitize_callback' => 'absint' ],
                    'per_page'  => [ 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100, 'sanitize_callback' => 'absint' ],
                    'archetype' => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
                    'category'  => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                    'search'    => [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                ],
            ]
        );

        register_rest_route(
            CRAE_REST_NAMESPACE,
            '/templates/(?P<slug>[a-z0-9\-_]+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'show' ],
                    'permission_callback' => [ $this->capabilities, 'canViewTemplates' ],
                ],
                [
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'update' ],
                    'permission_callback' => [ $this->capabilities, 'canManageTemplates' ],
                    'args'                => TemplateMutationRequest::args(),
                ],
            ]
        );
    }

    public function index( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        if ( null !== $this->clientGuard ) {
            $clientCheck = $this->clientGuard->verifyBrowserRequest();
            if ( is_wp_error( $clientCheck ) ) {
                return $clientCheck;
            }
        }

        $filters = [
            'archetype' => (string) $request->get_param( 'archetype' ),
            'category'  => (string) $request->get_param( 'category' ),
            'search'    => (string) $request->get_param( 'search' ),
        ];

        $page    = max( 1, (int) $request->get_param( 'page' ) );
        $perPage = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );

        return ApiResponse::success( $this->templateRepo->listCatalog( $filters, $page, $perPage ) );
    }

    public function show( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        if ( null !== $this->clientGuard ) {
            $clientCheck = $this->clientGuard->verifyBrowserRequest();
            if ( is_wp_error( $clientCheck ) ) {
                return $clientCheck;
            }
        }

        $slug     = sanitize_key( (string) $request->get_param( 'slug' ) );
        $template = $this->templateRepo->findBySlug( $slug );

        if ( null === $template ) {
            return ApiResponse::error( 'crae_template_not_found', "Template '{$slug}' not found.", 404 );
        }

        $tplId = (int) $template['id'];

        return ApiResponse::success(
            [
                'template'         => $template,
                'sections'         => $this->templateRepo->getSections( $tplId ),
                'scales'           => $this->scaleRepo->getByTemplate( $tplId ),
                'validity_rules'   => $this->narrativeRepo->getValidityRules( $tplId ),
                'decision_options' => $this->narrativeRepo->getDecisionOptions( $tplId ),
            ]
        );
    }

    public function update( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $slug     = sanitize_key( (string) $request->get_param( 'slug' ) );
        $existing = $this->templateRepo->findBySlug( $slug );

        if ( null === $existing ) {
            return ApiResponse::error( 'crae_template_not_found', "Template '{$slug}' not found.", 404 );
        }

        $updated = $this->mutationValidator->extractAllowedUpdates( $request, $existing );
        $this->templateRepo->upsert( $updated );

        return ApiResponse::success(
            [
                'template' => $this->templateRepo->findBySlug( $slug ),
            ]
        );
    }
}

