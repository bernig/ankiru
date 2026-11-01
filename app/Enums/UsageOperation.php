<?php

namespace App\Enums;

/**
 * Kind of paid AI call recorded in api_usage_logs.operation.
 */
enum UsageOperation: string
{
    case Translation = 'translation';
    case StressCorrection = 'stress_correction';
    case RowGeneration = 'row_generation';
    case Tts = 'tts';
}
