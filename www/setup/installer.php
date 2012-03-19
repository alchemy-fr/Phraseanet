<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @todo write tests
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once __DIR__ . '/../../lib/classes/bootstrap.class.php';
bootstrap::register_autoloads();
bootstrap::set_php_configuration();

ini_set("display_errors", 1);
$app = require __DIR__ . '/../../lib/Alchemy/Phrasea/Application/Setup.php';

$app->run();
