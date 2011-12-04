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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

//require_once dirname(__FILE__) . "/../../../lib/vendor/Silex/autoload.php";
//use Symfony\Component\HttpFoundation\Response;

$app = require __DIR__ . '/../../../lib/classes/module/api/V1.php';

$app->run();
