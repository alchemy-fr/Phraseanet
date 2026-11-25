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
use Symfony\Component\ErrorHandler\ErrorHandler;

require_once __DIR__ . '/../lib/autoload.php';

// Defense in depth: Symfony < 2.8.44 / 3.4.14 let these IIS headers override the request path.
unset($_SERVER['HTTP_X_ORIGINAL_URL'], $_SERVER['HTTP_X_REWRITE_URL']);

error_reporting(0);

ErrorHandler::register();

$environment = getenv('APP_ENV') ?: Application::ENV_DEV;
$app = require __DIR__ . '/../lib/Alchemy/Phrasea/Application/Api.php';

$app->run();
