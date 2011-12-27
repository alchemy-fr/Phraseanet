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
require_once __DIR__ . "/../../lib/bootstrap.php";

$request = http_request::getInstance();
$parm = $request->get_parms('name', 'csv');

function trimUltime($str)
{
  $str = preg_replace('/[ \t\r\f]+/', '', $str);

  return $str;
}

$parm['name'] ? $name = '_' . $parm['name'] : $name = "";
$name = preg_replace('/\s+/', '_', $name);
$filename = mb_strtolower('report' . $name . '_' . date('dmY') . '.csv');

$content = "";


if ($parm['csv'])
{
  $content = trimUltime($parm['csv']);
  set_export::stream_data($content, $filename, "text/csv");
}
?>
