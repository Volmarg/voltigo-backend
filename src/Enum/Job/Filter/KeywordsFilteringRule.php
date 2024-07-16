<?php

namespace App\Enum\Job\Filter;

/**
 * Keywords filtering rules
 */
enum KeywordsFilteringRule: string
{
    case NONE = "NONE";
    case AND  = "AND";
    case OR   = "OR";
}