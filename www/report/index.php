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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
require($registry->get('GV_RootPath') . 'lib/classes/deprecated/countries.php');

phrasea::headers();
User_Adapter::updateClientInfos(4);

///////Construct dashboard
$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
$dashboard = new module_report_dashboard($user);
$dashboard->execute();


$var = array(
    'ajax_dash' => true,
    'dashboard' => $dashboard,
    'home_title' => $registry->get('GV_homeTitle'),
    'module' => "report",
    "module_name" => "Report",
    'anonymous' => $registry->get('GV_anonymousReport'),
    'g_anal' => $registry->get('GV_googleAnalytics'),
    'ajax' => false,
    'ajax_chart' => false
);

$twig = new supertwig();

$twig->addFilter(array(
    'serialize' => 'serialize',
    'sbas_names' => 'phrasea::sbas_names',
    'unite' => 'p4string::format_octets',
    'stristr' => 'stristr',
    'key_exists' => 'array_key_exists')
);

$twig->display('report/report_layout_child.twig', $var);
