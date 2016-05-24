<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\Debug\ErrorHandler;

require_once __DIR__ . '/../lib/autoload.php';

error_reporting(0);

ErrorHandler::register();

$environment = getenv('APP_ENV') ?: Application::ENV_DEV;
$app = require __DIR__ . '/../lib/Alchemy/Phrasea/Application/Api.php';

$app->run();
