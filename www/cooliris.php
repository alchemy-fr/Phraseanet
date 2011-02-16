<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap.php";
header('Content-Type: application/atom+xml');

$request = httpRequest::getInstance();
$parm = $request->get_parms('item_id');

$home_datas = new homelink();
$item_id = !is_null($parm['item_id']) ? $parm['item_id'] : false;
echo $home_datas->format_media_rss($item_id);
