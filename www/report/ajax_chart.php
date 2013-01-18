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

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../vendor/autoload.php";

$app = new Application();
$request = http_request::getInstance();
$parm = $request->get_parms('id');

$id = $parm['id'];

$dashboard = new module_report_dashboard($app, $app['phraseanet.user']->get_id());

$var = array(
    'rs'         => $dashboard->dashboard['activity_day'][$id],
    'legendDay'  => $dashboard->legendDay,
    "sbas_id"    => $id,
    'ajax_chart' => true
);

$twig = $app['twig'];

$html = $twig->render('report/chart.html.twig', $var);
$t = array("rs" => $html);
echo json_encode($t);

