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

$registry = registry::get_instance();
phrasea::headers();

$request = http_request::getInstance();
$parm = $request->get_parms(
                "lst"
                , "ACT"
                , "operation"
                , "ForceThumbSubstit"
);

$lst = explode(";", $parm["lst"]);
$newlist = '';
if ($parm['ForceThumbSubstit'] == '1')
{
  foreach ($lst as $basrec)
  {
    $basrec = explode('_', $basrec);

    try
    {
      $record = new record_adapter($basrec[0], $basrec[1]);
      $record->rebuild_subdefs();
      unset($record);
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }
  }
}
