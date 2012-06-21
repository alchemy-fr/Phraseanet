<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";

$registry = $Core->getRegistry();

require($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

phrasea::headers();
User_Adapter::updateClientInfos(4);

///////Construct dashboard
$user = $Core->getAuthenticatedUser();
$dashboard = new module_report_dashboard($user);
$dashboard->execute();

$var = array(
    'ajax_dash'   => true,
    'dashboard'   => $dashboard,
    'home_title'  => $registry->get('GV_homeTitle'),
    'module'      => "report",
    "module_name" => "Report",
    'anonymous'   => $registry->get('GV_anonymousReport'),
    'g_anal'      => $registry->get('GV_googleAnalytics'),
    'ajax'        => false,
    'ajax_chart'  => false
);

$twig = $Core->getTwig();

echo $twig->render('report/report_layout_child.twig', $var);
