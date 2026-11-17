<?php
/**
 * Centralized Capability & Permission Guard
 *
 * Uses built-in WordPress capabilities ('read', 'manage_options') configured in
 * config/capabilities.php so zero custom WordPress user roles are modified.
 *
 * @package ClicReport\AssessmentEngine\Authorization
 */

namespace ClicReport\AssessmentEngine\Authorization;

defined( 'ABSPATH' ) || exit;

final class CapabilityManager {

    /**
     * @param array<string, string> $capabilityMap Map from config/capabilities.php.
     */
    public function __construct( private array $capabilityMap ) {}

    /**
     * Check if current user is authenticated and possesses the required capability.
     */
    public function can( string $action ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $requiredCap = $this->capabilityMap[ $action ] ?? 'manage_options';

        /**
         * Filter the WordPress capability required for a ClicReport action.
         *
         * @param string $requiredCap WordPress capability slug.
         * @param string $action      Logical plugin action key.
         */
        $filteredCap = (string) apply_filters( 'crae_required_capability', $requiredCap, $action );

        return current_user_can( $filteredCap );
    }

    public function canViewTemplates(): bool {
        return $this->can( 'view_templates' );
    }

    public function canGenerateReports(): bool {
        return $this->can( 'generate_reports' );
    }

    public function canManageTemplates(): bool {
        return $this->can( 'manage_templates' );
    }

    public function canViewAuditLogs(): bool {
        return $this->can( 'view_audit_logs' );
    }

    public function canManageSettings(): bool {
        return $this->can( 'manage_settings' );
    }
}

