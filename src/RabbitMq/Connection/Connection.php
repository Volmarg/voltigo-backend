<?php

namespace App\RabbitMq\Connection;

use PhpAmqpLib\Connection\AbstractConnection;

/**
 * Needed because otherwise the bundle tries to initialize the {@see AbstractConnection} and will crash,
 * This class is basically used as replacement for {@see AbstractConnection} to fix the instantiation issue
 */
class Connection extends AbstractConnection
{

}