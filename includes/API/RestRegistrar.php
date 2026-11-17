<?php
/**
 * WordPress REST API Route Registrar
 *
 * @package ClicReport\AssessmentEngine\API
 */

namespace ClicReport\AssessmentEngine\API;

use ClicReport\AssessmentEngine\API\Controllers\AbstractRestController;

defined( 'ABSPATH' ) || exit;

final class RestRegistrar {

    /**
     * @param AbstractRestController[] $controllers
     */
    public function __construct( private array $controllers ) {}

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'registerAllRoutes' ] );
    }

    public function registerAllRoutes(): void {
        foreach ( $this->controllers as $controller ) {
            $controller->registerRoutes();
        }
    }
}

