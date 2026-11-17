<?php

/**
 * PSR-4 Autoloader for ClicReport Assessment Engine
 * 
 * @package ClicReport\AssessmentEngine\Core
 */

namespace ClicReport\AssessmentEngine\Core;

defined('ABSPATH') || exit;

class Autoloader
{

    /**
     * Root namespace prefix for this plugin
     */

    private const NAMESPACE_PREFIX = 'ClicReport\\AssessmentEngine\\';

    /**
     * Register the autoloader with PHP's SPL autoload stack
     */

    public static function register(): void
    {
        spl_autoload_register([self::class, 'autoload']);
    }

    /**
     * Autoload a class by mapping its namespace to the includes/ directory.
     *
     * Example:
     *   ClicReport\AssessmentEngine\Core\Plugin
     *   -> {CRAE_PLUGIN_DIR}/includes/Core/Plugin.php
     *
     * @param string $class Fully-qualified class name.
     */

    public static function autoload(string $class): void
    {

        $prefixLength = strlen(self::NAMESPACE_PREFIX);
        if (strncmp(self::NAMESPACE_PREFIX, $class, $prefixLength) !== 0) {
            return;
        }

        $relativeClass = substr($class, $prefixLength);
        $file = CRAE_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_readable($file)) {
            require_once $file;
        }
    }
}
