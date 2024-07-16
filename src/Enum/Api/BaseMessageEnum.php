<?php

namespace App\Enum\Api;

/**
 * Some generic messages for presenting the overall state of the response
 */
enum BaseMessageEnum
{
    case OK;
    case BAD_REQUEST;
    case INTERNAL_SERVER_ERROR;
}