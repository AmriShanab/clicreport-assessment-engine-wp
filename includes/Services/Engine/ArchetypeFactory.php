<?php
/**
 * Factory resolving Archetype Strategy Processors (Archetypes 1-5)
 *
 * @package ClicReport\AssessmentEngine\Services\Engine
 */

namespace ClicReport\AssessmentEngine\Services\Engine;

use ClicReport\AssessmentEngine\Services\Archetypes\ArchetypeProcessorInterface;
use ClicReport\AssessmentEngine\Services\Archetypes\ClinicalCutoffProcessor;
use ClicReport\AssessmentEngine\Services\Archetypes\DualStandardScaledProcessor;
use ClicReport\AssessmentEngine\Services\Archetypes\NormativeTScoreProcessor;
use ClicReport\AssessmentEngine\Services\Archetypes\QualitativeMatrixProcessor;
use ClicReport\AssessmentEngine\Services\Archetypes\StaticReferenceProcessor;

defined( 'ABSPATH' ) || exit;

final class ArchetypeFactory {

    public static function create( string $archetype ): ArchetypeProcessorInterface {
        return match ( $archetype ) {
            't_score', 'archetype_1'              => new NormativeTScoreProcessor(),
            'dual_standard_scaled', 'archetype_2' => new DualStandardScaledProcessor(),
            'clinical_cutoff', 'archetype_3'      => new ClinicalCutoffProcessor(),
            'static_reference', 'archetype_4'     => new StaticReferenceProcessor(),
            'qualitative_matrix', 'archetype_5'   => new QualitativeMatrixProcessor(),
            default                               => new NormativeTScoreProcessor(),
        };
    }

    public static function inferFromScoreSystem( string $scoreSystem ): string {
        return match ( $scoreSystem ) {
            'standard_score', 'scaled_score', 'v_scale' => 'dual_standard_scaled',
            'cutoff'                                    => 'clinical_cutoff',
            'static'                                    => 'static_reference',
            'qualitative', 'matrix'                     => 'qualitative_matrix',
            default                                     => 't_score',
        };
    }
}

