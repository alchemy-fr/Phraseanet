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

require_once __DIR__ . "/../lib/autoload.php";

ErrorHandler::register();

$environment = Application::ENV_DEV;
$app = require __DIR__ . '/../lib/Alchemy/Phrasea/Application/Root.php';

$app->run();
