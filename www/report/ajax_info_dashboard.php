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
/**
 * @todo Remove this in next refactor
 */
$event = new GetResponseEvent($app, Request::createFromGlobals(), HttpKernelInterface::MASTER_REQUEST);

$app->addLocale($event);
$app->initSession($event);

if ( ! $app['phraseanet.user']->ACL()->has_right('report'))
    phrasea::headers(403);


$sbasid = isset($_POST['sbasid']) ? $_POST['sbasid'] : null;
$dmin = isset($_POST['dmin']) ? $_POST['dmin'] : false;
$dmax = isset($_POST['dmax']) ? $_POST['dmax'] : false;
///////Construct dashboard
try {
    $dashboard = new module_report_dashboard($app, $app['phraseanet.user'], $sbasid);

    if ($dmin && $dmax) {
        $dashboard->setDate($dmin, $dmax);
    }

    $dashboard->execute();
} catch (Exception $e) {
    echo 'Exception reçue : ', $e->getMessage(), "\n";
}

$twig = $app['twig'];

$html = $twig->render(
    "report/ajax_dashboard_content_child.html.twig", array(
    'dashboard' => $dashboard
    )
);

$t = array('html' => $html);
echo p4string::jsonencode($t);
