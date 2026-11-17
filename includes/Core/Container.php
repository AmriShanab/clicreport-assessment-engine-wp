<?php
/**
 * Lightweight Dependency Injection Container
 *
 * @package ClicReport\AssessmentEngine\Core
 */

namespace ClicReport\AssessmentEngine\Core;

defined( 'ABSPATH' ) || exit;

class Container {

    /**
     * Registered service factories.
     *
     * @var array<string, array{factory: callable, singleton: bool}>
     */
    private array $bindings = [];

    /**
     * Resolved singleton instances.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Register a service or factory in the container.
     *
     * @param string   $id        Unique service key (e.g. 'wpdb' or class name).
     * @param callable $factory   Closure receiving Container and returning the instance.
     * @param bool     $singleton Whether to cache and reuse the same instance.
     */
    public function set( string $id, callable $factory, bool $singleton = true ): void {
        $this->bindings[ $id ] = [
            'factory'   => $factory,
            'singleton' => $singleton,
        ];
        unset( $this->instances[ $id ] );
    }

    /**
     * Check whether a service key is registered.
     */
    public function has( string $id ): bool {
        return isset( $this->bindings[ $id ] );
    }

    /**
     * Resolve a registered service by key.
     *
     * @throws \InvalidArgumentException If the service key is not registered.
     */
    public function get( string $id ): mixed {
        if ( isset( $this->instances[ $id ] ) ) {
            return $this->instances[ $id ];
        }

        if ( ! $this->has( $id ) ) {
            throw new \InvalidArgumentException(
                sprintf( 'Service "%s" is not registered in the ClicReport container.', $id )
            );
        }

        $binding = $this->bindings[ $id ];
        $service = ( $binding['factory'] )( $this );

        if ( $binding['singleton'] ) {
            $this->instances[ $id ] = $service;
        }

        return $service;
    }
}