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
include(__DIR__ . '/../../lib/bootstrap.php');
$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action', 'city');

$action = $parm['action'];

switch ($action)
{
  case 'FIND':
    $geoname = new geonames();
    $twig = new supertwig();
    $output = $twig->render('geonames/city_list.twig', array('geonames'=>$geoname->find_city($parm['city'])));
    break;
}


echo $output;

