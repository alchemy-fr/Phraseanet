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

$app = new Application('dev');

$event = new GetResponseEvent($app, Request::createFromGlobals(), HttpKernelInterface::MASTER_REQUEST);

$app->addLocale($event);
$app->initSession($event);

require($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

phrasea::headers();
User_Adapter::updateClientInfos($app, 4);

///////Construct dashboard
$dashboard = new module_report_dashboard($app, $app['phraseanet.user']);
$dashboard->execute();


$var = array(
    'ajax_dash'   => true,
    'dashboard'   => $dashboard,
    'home_title'  => $app['phraseanet.registry']->get('GV_homeTitle'),
    'module'      => "report",
    "module_name" => "Report",
    'anonymous'   => $app['phraseanet.registry']->get('GV_anonymousReport'),
    'g_anal'      => $app['phraseanet.registry']->get('GV_googleAnalytics'),
    'ajax'        => false,
    'ajax_chart'  => false
);

$twig = $app['twig'];

echo $twig->render('report/report_layout_child.html.twig', $var);
