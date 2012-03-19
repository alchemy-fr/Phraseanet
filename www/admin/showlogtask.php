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
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms('fil', 'act');

$registry = $appbox->get_registry();
$logdir = p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');
$logfile = $logdir . $parm['fil'];

if (file_exists($logfile))
{
  if ($parm['act'] == 'CLR')
  {
    file_put_contents($logfile, '');

    return phrasea::redirect("/admin/showlogtask.php?fil=" . urlencode($parm['fil']));
  }
  else
  {
    printf("<html lang=\"" . $session->get_I18n() . "\"><body><h4>%s&nbsp;  <a href=\"showlogtask.php?fil=%s&act=CLR\">effacer</a></h4>\n", $logfile, urlencode($parm['fil']));
    print("<pre>\n");
    print(htmlentities(file_get_contents($logfile)));
    print("</pre>\n</body></html>");
  }
}
else
{
  printf("file <b>%s</b> does not exists\n", $logfile);
}

