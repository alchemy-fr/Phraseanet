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

$app = new Application();
$user = $app['phraseanet.user'];

$lng = $app['locale'];

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action', 'env', 'pos', 'cont', 'roll', 'mode', 'color', 'options_serial', 'query');

switch ($parm['action']) {
    case 'LANGUAGE':
        $output = module_client::getLanguage($app, $lng);
        break;
    case 'PREVIEW':

        $search_engine = null;
        if ($parm['env'] == 'RESULT' && ($options = unserialize($parm['options_serial'])) !== false) {
            $search_engine = new searchEngine_adapter($app);
            $search_engine->set_options($options);
        }

        $record = new record_preview($app, $parm['env'], $parm['pos'], $parm['cont'], $parm['roll'], $search_engine, $parm['query']);

        $train = '';

        if ($record->is_from_reg()) {
            $train = $app['twig']->render('prod/preview/reg_train.html.twig', array(
                'record' => $record
                )
            );
        }

        if ($record->is_from_basket() && $parm['roll']) {
            $train = $app['twig']->render('prod/preview/basket_train.html.twig', array(
                'record' => $record
                )
            );
        }


        if ($record->is_from_feed()) {
            $train = $app['twig']->render('prod/preview/feed_train.html.twig', array(
                'record' => $record
                )
            );
        }

        $output = p4string::jsonencode(
                array(
                    "desc" => $app['twig']->render('prod/preview/caption.html.twig', array(
                        'record'       => $record
                        , 'highlight'    => $parm['query']
                        , 'searchEngine' => $search_engine
                        )
                    )
                    , "html_preview" => $app['twig']->render('common/preview.html.twig', array('record' => $record)
                    )
                    , "others" => $app['twig']->render('prod/preview/appears_in.html.twig', array(
                        'parents' => $record->get_grouping_parents(),
                        'baskets' => $record->get_container_baskets($app['EM'], $app['phraseanet.user'])
                        )
                    )
                    , "current" => $train
                    , "history" => $app['twig']->render('prod/preview/short_history.html.twig', array('record'     => $record)
                    )
                    , "popularity" => $app['twig']->render('prod/preview/popularity.html.twig', array('record' => $record)
                    )
                    , "tools"  => $app['twig']->render('prod/preview/tools.html.twig', array('record' => $record)
                    )
                    , "pos"    => $record->get_number()
                    , "title"  => $record->get_title($parm['query'], $search_engine)
                )
        );

        break;
    case 'HOME':
        $output = phrasea::getHome($app, 'PUBLI', 'client');
        break;
    case 'CSS':
        $output = $user->setPrefs('css', $parm['color']);
        break;
    case 'BASK_STATUS':
        $output = $user->setPrefs('client_basket_status', $parm['mode']);
        break;
    case 'BASKUPDATE':
        $noview = 0;

        $repository = $app['EM']->getRepository('\Entities\Basket');

        /* @var $repository \Repositories\BasketRepository */
        $baskets = $repository->findActiveByUser($user);

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

