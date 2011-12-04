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

if (isset($session->usr_id) && isset($session->ses_id))
{
  $usr_id = $session->get_usr_id();
  $user = User_Adapter::getInstance($usr_id, $appbox);

  if (!$user->ACL()->has_right('report'))
    phrasea::headers(403);
}
else
{
  header("Location: /login/?redirect=/report");
  exit();
}


$sbasid = isset($_POST['sbasid']) ? $_POST['sbasid'] : null;
$dmin = isset($_POST['dmin']) ? $_POST['dmin'] : false;
$dmax = isset($_POST['dmax']) ? $_POST['dmax'] : false;
///////Construct dashboard
try
{
  $dashboard = new module_report_dashboard($user, $sbasid);

  if ($dmin && $dmax)
  {
    $dashboard->setDate($dmin, $dmax);
  }

  $dashboard->execute();
}
catch (Exception $e)
{
  echo 'Exception reçue : ', $e->getMessage(), "\n";
}

$twig = new supertwig();
$twig->addFilter(
        array(
            'serialize' => 'serialize',
            'sbas_names' => 'phrasea::sbas_names',
            'unite' => 'p4string::format_octets',
            'stristr' => 'stristr',
            'key_exists' => 'array_key_exists'
        )
);
$html = $twig->render(
                "report/ajax_dashboard_content_child.twig",
                array(
                    'dashboard' => $dashboard
                )
);

$t = array('html' => $html);
echo p4string::jsonencode($t);
