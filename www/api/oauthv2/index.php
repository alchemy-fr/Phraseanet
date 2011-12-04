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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Response;

try
{
  $app = require dirname(__FILE__).'/../../../lib/classes/module/api/OAuthv2.php';
  $app->run();
}
catch (Exception $e)
{
  return new Response('Internal Server Error', 500);
}
