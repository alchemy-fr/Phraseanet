<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";

$Request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$app = new Application();

$gatekeeper = gatekeeper::getInstance($app);
$gatekeeper->require_session();



$request = http_request::getInstance();
$parm = $request->get_parms("lst", "obj", "ssttid", "type", "businessfields");

$download = new set_export($app, $parm['lst'], $parm['ssttid']);

if ($parm["type"] == "title")
    $titre = true;
else
    $titre = false;

$list = $download->prepare_export($app['phraseanet.user'], $app['filesystem'], $parm['obj'], $titre, $parm['businessfields']);

$exportname = "Export_" . date("Y-n-d") . '_' . mt_rand(100, 999);

if ($parm["ssttid"] != "") {
    $repository = $app['EM']->getRepository('\Entities\Basket');

    /* @var $repository \Repositories\BasketRepository */

    $basket = $repository->findUserBasket($app, $Request->get('ssttid'), $app['phraseanet.user'], false);
    $exportname = str_replace(' ', '_', $basket->getName()) . "_" . date("Y-n-d");
}

$list['export_name'] = $exportname . '.zip';

$endDate = new DateTime('+3 hours');

$url = random::getUrlToken($app, \random::TYPE_DOWNLOAD, $app['phraseanet.user']->get_id(), $endDate, serialize($list));

if ($url) {

    $params = array(
        'lst'         => $parm['lst'],
        'downloader'  => $app['phraseanet.user']->get_id(),
        'subdefs'     => $parm['obj'],
        'from_basket' => $parm["ssttid"],
        'export_file' => $exportname
    );

    $app['events-manager']->trigger('__DOWNLOAD__', $params);

    return phrasea::redirect('/download/' . $url);
}
phrasea::headers(500);


