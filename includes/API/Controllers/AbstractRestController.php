<?php
/**
 * Base WordPress REST API Controller
 *
 * @package ClicReport\AssessmentEngine\API\Controllers
 */

namespace ClicReport\AssessmentEngine\API\Controllers;

use ClicReport\AssessmentEngine\Authorization\CapabilityManager;
use ClicReport\AssessmentEngine\Security\RateLimiter;

defined( 'ABSPATH' ) || exit;

abstract class AbstractRestController {

    public function __construct(
        protected CapabilityManager $capabilities,
        protected RateLimiter $rateLimiter
    ) {}

    abstract public function registerRoutes(): void;

    /**
     * Enforce sliding-window rate limits per authenticated user or hashed client IP.
     *
     * @return true|\WP_Error
     */
    protected function enforceRateLimit( string $bucket, int $limit, int $windowSeconds = 60 ): true|\WP_Error {
        $userId     = get_current_user_id();
        $identifier = $userId > 0 ? 'user_' . $userId : 'ip_' . $this->rateLimiter->getClientIp();

        return $this->rateLimiter->check( $bucket, $identifier, $limit, $windowSeconds );
    }
}

