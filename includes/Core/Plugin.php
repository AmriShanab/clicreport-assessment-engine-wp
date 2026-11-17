<?php

/**
 * Main Plugin Orchestrator (Kernel)
 *
 * @package ClicReport\AssessmentEngine\Core
 */

namespace ClicReport\AssessmentEngine\Core;

use ClicReport\AssessmentEngine\Services\Visuals\BellCurveService;

defined('ABSPATH') || exit;

final class Plugin
{

    private static ?self $instance = null;
    private Container $container;
    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Prevent cloning or unserializing the plugin singleton.
     */
    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \RuntimeException('Cannot unserialize Plugin singleton.');
    }

    /**
     * Get the single Plugin instance.
     */
    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Boot services and register WordPress lifecycle hooks.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->registerCoreBindings();
        $this->registerHooks();

        $this->booted = true;

        /**
         * Fires once ClicReport Assessment Engine has finished booting.
         *
         * @param Plugin $plugin Main plugin instance.
         */
        do_action('crae_plugin_booted', $this);
    }

    /**
     * Access the Dependency Injection container.
     */
    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Load plugin translation files on WordPress 'init'.
     */
    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            CRAE_TEXT_DOMAIN,
            false,
            dirname(CRAE_PLUGIN_BASENAME) . '/resources/languages'
        );
    }

    /**
     * Register core infrastructure services inside the DI container.
     * Phase 2–5 repositories, scorers, and controllers will be bound here.
     */
    private function registerCoreBindings(): void
    {
        $this->container->set('wpdb', static function (): \wpdb {
            global $wpdb;
            return $wpdb;
        });

        $this->container->set('config.capabilities', static function (): array {
            return require CRAE_PLUGIN_DIR . 'config/capabilities.php';
        });

        $this->container->set(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository($c->get('wpdb'));
        });

        $this->container->set(\ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository($c->get('wpdb'));
        });

        $this->container->set(\ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository($c->get('wpdb'));
        });

        $this->container->set(\ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository($c->get('wpdb'));
        });

        $this->container->set(\ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine(
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository::class)
            );
        });

        $this->container->set(\ClicReport\AssessmentEngine\Security\ClientGuard::class, static function () {
            return new \ClicReport\AssessmentEngine\Security\ClientGuard();
        });

        $this->container->set(\ClicReport\AssessmentEngine\API\AjaxHandler::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\API\AjaxHandler(
                $c->get(\ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine::class),
                $c->get(\ClicReport\AssessmentEngine\Security\ClientGuard::class),
                $c->get(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class)
            );
        });

        $this->container->set(BellCurveService::class, static function () {
            return new BellCurveService();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Services\Mail\NotificationService::class, static function () {
            return new \ClicReport\AssessmentEngine\Services\Mail\NotificationService();
        });

        // Phase 4: Security, Auth, Authorization & REST API bindings.
        $this->container->set(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Authorization\CapabilityManager($c->get('config.capabilities'));
        });

        $this->container->set(\ClicReport\AssessmentEngine\Security\InputSanitizer::class, static function () {
            return new \ClicReport\AssessmentEngine\Security\InputSanitizer();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Security\RateLimiter::class, static function () {
            return new \ClicReport\AssessmentEngine\Security\RateLimiter();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Security\FileValidator::class, static function () {
            return new \ClicReport\AssessmentEngine\Security\FileValidator();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Security\CorsManager::class, static function () {
            return new \ClicReport\AssessmentEngine\Security\CorsManager();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class, static function () {
            return new \ClicReport\AssessmentEngine\Authentication\PsiveyTokenService();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Authentication\AuthManager::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Authentication\AuthManager(
                $c->get(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class)
            );
        });

        $this->container->set(\ClicReport\AssessmentEngine\Integrations\Psivey\PsiveyApiClient::class, static function () {
            return new \ClicReport\AssessmentEngine\Integrations\Psivey\PsiveyApiClient();
        });

        $this->container->set(\ClicReport\AssessmentEngine\API\RestRegistrar::class, static function (Container $c) {
            $caps      = $c->get(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class);
            $limiter   = $c->get(\ClicReport\AssessmentEngine\Security\RateLimiter::class);
            $sanitizer = $c->get(\ClicReport\AssessmentEngine\Security\InputSanitizer::class);
            $guard     = $c->get(\ClicReport\AssessmentEngine\Security\ClientGuard::class);

            return new \ClicReport\AssessmentEngine\API\RestRegistrar(
                [
                    new \ClicReport\AssessmentEngine\API\Controllers\ReportController(
                        $caps,
                        $limiter,
                        $c->get(\ClicReport\AssessmentEngine\Services\Engine\DatabaseReportEngine::class),
                        $c->get(\ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository::class),
                        new \ClicReport\AssessmentEngine\API\Requests\GenerateReportRequest($sanitizer),
                        $guard,
                        $c->get(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class)
                    ),
                    new \ClicReport\AssessmentEngine\API\Controllers\TemplateController(
                        $caps,
                        $limiter,
                        $c->get(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class),
                        $c->get(\ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository::class),
                        $c->get(\ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository::class),
                        new \ClicReport\AssessmentEngine\API\Requests\TemplateMutationRequest(),
                        $guard
                    ),
                    new \ClicReport\AssessmentEngine\API\Controllers\PsiveyProxyController(
                        $caps,
                        $limiter,
                        $c->get(\ClicReport\AssessmentEngine\Authentication\PsiveyTokenService::class)
                    ),
                    new \ClicReport\AssessmentEngine\API\Controllers\ContactController(
                        $caps,
                        $limiter,
                        $c->get(\ClicReport\AssessmentEngine\Services\Mail\NotificationService::class)
                    ),
                ]
            );
        });

        // Phase 5: Admin UI & Frontend Shortcode / Asset bindings.
        $this->container->set(\ClicReport\AssessmentEngine\Admin\SettingsPage::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Admin\SettingsPage(
                $c->get(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class)
            );
        });

        $this->container->set(\ClicReport\AssessmentEngine\Admin\TemplateManagerPage::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Admin\TemplateManagerPage(
                $c->get(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository::class)
            );
        });

        $this->container->set(\ClicReport\AssessmentEngine\Admin\AdminMenu::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Admin\AdminMenu(
                $c->get(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class),
                $c->get(\ClicReport\AssessmentEngine\Admin\SettingsPage::class),
                $c->get(\ClicReport\AssessmentEngine\Admin\TemplateManagerPage::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\AuditLogRepository::class)
            );
        });

        $this->container->set(\ClicReport\AssessmentEngine\Frontend\AssetLoader::class, static function () {
            return new \ClicReport\AssessmentEngine\Frontend\AssetLoader();
        });

        $this->container->set(\ClicReport\AssessmentEngine\Frontend\ShortcodeManager::class, static function (Container $c) {
            return new \ClicReport\AssessmentEngine\Frontend\ShortcodeManager(
                $c->get(\ClicReport\AssessmentEngine\Authorization\CapabilityManager::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\TemplateRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Database\Repositories\ScaleRepository::class),
                $c->get(\ClicReport\AssessmentEngine\Frontend\AssetLoader::class)
            );
        });
    }

    /**
     * Connect plugin modules to WordPress action & filter hooks.
     */
    private function registerHooks(): void
    {
        add_action('init', [$this, 'loadTextDomain']);

        $this->container->get(\ClicReport\AssessmentEngine\Authentication\AuthManager::class)->register();
        $this->container->get(\ClicReport\AssessmentEngine\Security\CorsManager::class)->register();
        $this->container->get(\ClicReport\AssessmentEngine\API\RestRegistrar::class)->register();

        if (is_admin()) {
            $this->container->get(\ClicReport\AssessmentEngine\Admin\AdminMenu::class)->register();
        }

        $this->container->get(\ClicReport\AssessmentEngine\Frontend\AssetLoader::class)->register();
        $this->container->get(\ClicReport\AssessmentEngine\Frontend\ShortcodeManager::class)->register();
        $ajaxHandler = $this->container->get(\ClicReport\AssessmentEngine\API\AjaxHandler::class);
        $ajaxHandler->registerHooks();
    }
}
