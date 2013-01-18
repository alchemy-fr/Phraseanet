<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

require __DIR__ . "/../../vendor/autoload.php";

$app = new Application;
$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action', 'city');

$action = $parm['action'];

switch ($action) {
    case 'FIND':
        $output = $app['twig']->render('geonames/city_list.html.twig', array('geonames' => $app['geonames']->find_city($parm['city'])));
        break;
}


echo $output;

