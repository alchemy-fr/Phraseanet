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
$request = http_request::getInstance();
$parm = $request->get_parms('id');

$id = $parm['id'];

$dashboard = new module_report_dashboard($session->get_usr_id());

$var = array(
    'rs' => $dashboard->dashboard['activity_day'][$id],
    'legendDay' => $dashboard->legendDay,
    "sbas_id" => $id,
    'ajax_chart' => true
);

$twig = new supertwig();

$twig->addFilter(
        array(
            'serialize' => 'serialize'
            , 'sbas_names' => 'phrasea::sbas_names'
            , 'unite' => 'p4string::format_octets'
            , 'stristr' => 'stristr'
        )
);
$html = $twig->render('report/chart.twig', $var);
$t = array("rs" => $html);
echo json_encode($t);

