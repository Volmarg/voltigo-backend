<?php

namespace App\Attribute\Information;

/**
 * This attribute serves as an information to warn that given field should not be encrypted for example
 * due to symfony internal logic, like for example user identifier based fields cannot be encrypted
 * because symfony is unable to fetch the user in authentication process etc.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class NoEncryptionAllowedAttribute
{

}