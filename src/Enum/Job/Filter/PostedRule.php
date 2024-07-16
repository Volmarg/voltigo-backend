<?php

namespace App\Enum\Job\Filter;

/**
 * Represents the posted date filter options of job offer filters
 */
enum PostedRule: string
{
    case NONE      = "NONE";
    case ONE_WEEK  = "ONE_WEEK";
    case TWO_WEEK  = "TWO_WEEK";
    case ONE_MONTH = "ONE_MONTH";
}