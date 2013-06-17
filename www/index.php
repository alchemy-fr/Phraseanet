<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpKernel\Debug\ErrorHandler;

require_once __DIR__ . "/../lib/autoload.php";

ErrorHandler::register();

$environment = 'prod';
$app = require __DIR__ . '/../lib/Alchemy/Phrasea/Application/Root.php';

$app->run();
