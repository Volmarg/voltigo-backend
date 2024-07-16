<?php

namespace App\Exception\Security;

use Exception;

/**
 * Indicates that user tried to get some other user resources (example: entities),
 * for example "calling search results of other user"
 */
class OtherUserResourceAccessException extends Exception
{

}