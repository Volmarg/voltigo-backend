<?php

namespace App\Enum\Job\SearchResult;

use App\Entity\Job\JobSearchResult;

/**
 * Enums for {@see JobSearchResult}, representing state of the search
 */
enum SearchResultStatusEnum
{
    case PENDING;
    case DONE;
    case PARTIALY_DONE;
    case ERROR;
    case WIP;

}