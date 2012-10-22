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
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../lib/bootstrap.php";

$app = new Application();

$lng = $app['locale'];

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action', 'env', 'pos', 'cont', 'roll', 'mode', 'color', 'options_serial', 'query');

switch ($parm['action']) {
    case 'LANGUAGE':
        $output = module_client::getLanguage($app, $lng);
        break;
    case 'HOME':
        $output = phrasea::getHome($app, 'PUBLI', 'client');
        break;
    case 'CSS':
        $output = $app['phraseanet.user']->setPrefs('css', $parm['color']);
        break;
    case 'BASK_STATUS':
        $output = $app['phraseanet.user']->setPrefs('client_basket_status', $parm['mode']);
        break;
    case 'BASKUPDATE':
        $noview = 0;

        $repository = $app['EM']->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */
        $baskets = $repository->findActiveByUser($app['phraseanet.user']);

        foreach ($baskets as $basket) {
            if ( ! $basket->getIsRead())
                $noview ++;
            if ( ! $basket->getIsRead())
                $noview ++;
        }
        $output = $noview;
        break;
}
echo $output;

