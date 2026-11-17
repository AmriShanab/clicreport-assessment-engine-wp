<?php
/**
 * Assessment Templates & Sections Repository
 *
 * @package ClicReport\AssessmentEngine\Database\Repositories
 */

namespace ClicReport\AssessmentEngine\Database\Repositories;

defined( 'ABSPATH' ) || exit;

final class TemplateRepository extends AbstractRepository {

    /**
     * Fetch a single active template by its unique slug.
     */
    public function findBySlug( string $slug ): ?array {
        $cacheKey = 'tpl_' . $slug;
        $cached   = $this->cacheGet( $cacheKey );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $table = $this->table( 'templates' );
        $row   = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_slug = %s LIMIT 1",
                $slug
            ),
            ARRAY_A
        );

        if ( ! is_array( $row ) ) {
            return null;
        }

        $this->cacheSet( $cacheKey, $row );
        return $row;
    }

    /**
     * Return paginated assessment catalog with optional filters.
     */
    public function listCatalog( array $filters = [], int $page = 1, int $perPage = 50 ): array {
        $table   = $this->table( 'templates' );
        $page    = max( 1, $page );
        $perPage = max( 1, min( 100, $perPage ) );
        $offset  = ( $page - 1 ) * $perPage;

        $where  = [ 'is_active = 1' ];
        $params = [];

        if ( ! empty( $filters['archetype'] ) ) {
            $where[]  = 'archetype = %s';
            $params[] = (string) $filters['archetype'];
        }

        if ( ! empty( $filters['category'] ) ) {
            $where[]  = 'category = %s';
            $params[] = (string) $filters['category'];
        }

        if ( ! empty( $filters['search'] ) ) {
            $like     = '%' . $this->wpdb->esc_like( (string) $filters['search'] ) . '%';
            $where[]  = '(template_name LIKE %s OR template_slug LIKE %s OR publisher LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode( ' AND ', $where );
        $orderBy  = $this->sanitizeOrderBy(
            (string) ( $filters['orderby'] ?? 'template_name' ),
            [ 'template_name', 'template_slug', 'archetype', 'category', 'id' ],
            (string) ( $filters['order'] ?? 'ASC' )
        );

        $countSql = "SELECT COUNT(*) FROM {$table} WHERE {$whereSql}";
        $total    = (int) $this->wpdb->get_var(
            empty( $params ) ? $countSql : $this->wpdb->prepare( $countSql, $params )
        );

        $queryParams   = array_merge( $params, [ $perPage, $offset ] );
        $itemsSql      = "SELECT id, template_slug, template_name, publisher, category, archetype, default_score_system, config_json
                          FROM {$table} WHERE {$whereSql} ORDER BY {$orderBy} LIMIT %d OFFSET %d";
        $items         = $this->wpdb->get_results( $this->wpdb->prepare( $itemsSql, $queryParams ), ARRAY_A );

        return [
            'items'       => is_array( $items ) ? $items : [],
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil( $total / $perPage ),
        ];
    }

    /**
     * Fetch ordered sections belonging to a template.
     */
    public function getSections( int $templateId ): array {
        $table = $this->table( 'sections' );
        $rows  = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$table} WHERE template_id = %d ORDER BY sort_order ASC, id ASC",
                $templateId
            ),
            ARRAY_A
        );

        return is_array( $rows ) ? $rows : [];
    }

    /**
     * Insert or update a template record and return its primary ID.
     */
    public function upsert( array $data ): int {
        $table = $this->table( 'templates' );
        $sql   = $this->wpdb->prepare(
            "INSERT INTO {$table}
                (template_slug, template_name, publisher, category, archetype, default_score_system, intro_narrative, config_json, is_active)
             VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %d)
             ON DUPLICATE KEY UPDATE
                template_name = VALUES(template_name),
                publisher = VALUES(publisher),
                category = VALUES(category),
                archetype = VALUES(archetype),
                default_score_system = VALUES(default_score_system),
                intro_narrative = COALESCE(VALUES(intro_narrative), intro_narrative),
                config_json = COALESCE(VALUES(config_json), config_json),
                is_active = VALUES(is_active)",
            $data['template_slug'],
            $data['template_name'],
            $data['publisher'] ?? null,
            $data['category'] ?? 'Social-Emotional',
            $data['archetype'] ?? 't_score',
            $data['default_score_system'] ?? 't_score',
            $data['intro_narrative'] ?? null,
            isset( $data['config_json'] ) ? ( is_string( $data['config_json'] ) ? $data['config_json'] : wp_json_encode( $data['config_json'] ) ) : null,
            (int) ( $data['is_active'] ?? 1 )
        );

        $this->wpdb->query( $sql );
        $this->cacheDelete( 'tpl_' . $data['template_slug'] );

        $existing = $this->findBySlug( (string) $data['template_slug'] );
        return (int) ( $existing['id'] ?? $this->wpdb->insert_id );
    }
}