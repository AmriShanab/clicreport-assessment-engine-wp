<?php
/**
 * Base WordPress MySQL ($wpdb) Repository
 *
 * @package ClicReport\AssessmentEngine\Database\Repositories
 */

namespace ClicReport\AssessmentEngine\Database\Repositories;

use ClicReport\AssessmentEngine\Database\Schema;

defined( 'ABSPATH' ) || exit;

abstract class AbstractRepository {

    protected const CACHE_GROUP = 'crae_db';

    protected \wpdb $wpdb;

    public function __construct( \wpdb $wpdb ) {
        $this->wpdb = $wpdb;
    }

    /**
     * Resolve prefixed plugin table name dynamically.
     */
    protected function table( string $shortName ): string {
        return Schema::tableName( $this->wpdb, $shortName );
    }

    /**
     * Whitelist-validate dynamic ORDER BY columns and direction.
     *
     * @param string[] $allowedColumns
     */
    protected function sanitizeOrderBy( string $column, array $allowedColumns, string $direction = 'ASC' ): string {
        $safeCol = in_array( $column, $allowedColumns, true ) ? $column : $allowedColumns[0];
        $safeDir = strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC';

        return "{$safeCol} {$safeDir}";
    }

    /**
     * Start an InnoDB SQL transaction.
     */
    public function beginTransaction(): void {
        $this->wpdb->query( 'START TRANSACTION' );
    }

    /**
     * Commit the active SQL transaction.
     */
    public function commit(): void {
        $this->wpdb->query( 'COMMIT' );
    }

    /**
     * Roll back the active SQL transaction.
     */
    public function rollback(): void {
        $this->wpdb->query( 'ROLLBACK' );
    }

    protected function cacheGet( string $key ): mixed {
        $found = false;
        $value = wp_cache_get( $key, self::CACHE_GROUP, false, $found );
        return $found ? $value : null;
    }

    protected function cacheSet( string $key, mixed $value, int $ttl = 3600 ): void {
        wp_cache_set( $key, $value, self::CACHE_GROUP, $ttl );
    }

    protected function cacheDelete( string $key ): void {
        wp_cache_delete( $key, self::CACHE_GROUP );
    }
}