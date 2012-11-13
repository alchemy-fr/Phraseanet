<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../vendor/autoload.php";

$app = new Application();

$event = new GetResponseEvent($app, Request::createFromGlobals(), HttpKernelInterface::MASTER_REQUEST);


$app->initPhrasea($event);
$app->addLocale($event);
$app->initSession($event);

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

