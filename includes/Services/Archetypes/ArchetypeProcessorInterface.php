<?php
/**
 * Strategy Interface for Assessment Archetype Processors (Archetypes 1-5)
 *
 * @package ClicReport\AssessmentEngine\Services\Archetypes
 */

namespace ClicReport\AssessmentEngine\Services\Archetypes;

use ClicReport\AssessmentEngine\Database\Repositories\NarrativeRuleRepository;

defined( 'ABSPATH' ) || exit;

interface ArchetypeProcessorInterface {

    /**
     * Execute clinical scoring and narrative synthesis for a template.
     *
     * @param array<string, mixed>       $templateRow
     * @param array<int, array>          $sections
     * @param array<int, array>          $scales
     * @param array<string, mixed>       $inputData
     * @param NarrativeRuleRepository    $narrativeRepo
     * @return array<string, mixed>
     */
    public function process(
        array $templateRow,
        array $sections,
        array $scales,
        array $inputData,
        NarrativeRuleRepository $narrativeRepo
    ): array;
}

