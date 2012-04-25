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

$em = $Core->getEntityManager();

$appbox = appbox::get_instance($Core);
$registry = $appbox->get_registry();
$user = $Core->getAuthenticatedUser();

$lng = Session_Handler::get_locale();

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action', 'env', 'pos', 'cont', 'roll', 'mode', 'color', 'options_serial', 'query');

switch ($parm['action']) {
    case 'LANGUAGE':
        $output = module_client::getLanguage($lng);
        break;
    case 'PREVIEW':

        $core = \bootstrap::getCore();
        $twig = $core->getTwig();

        $search_engine = null;
        if ($parm['env'] == 'RESULT' && ($options = unserialize($parm['options_serial'])) !== false) {
            $search_engine = new searchEngine_adapter($registry);
            $search_engine->set_options($options);
        }

        $record = new record_preview($parm['env'], $parm['pos'], $parm['cont'], $parm['roll'], $search_engine, $parm['query']);

        $train = '';

        if ($record->is_from_reg()) {
            $train = $twig->render('prod/preview/reg_train.html', array(
                'record' => $record
                )
            );
        }

        if ($record->is_from_basket() && $parm['roll']) {
            $train = $twig->render('prod/preview/basket_train.html', array(
                'record' => $record
                )
            );
        }


        if ($record->is_from_feed()) {
            $train = $twig->render('prod/preview/feed_train.html', array(
                'record' => $record
                )
            );
        }

        $output = p4string::jsonencode(
                array(
                    "desc" => $twig->render('prod/preview/caption.html', array(
                        'record'       => $record
                        , 'highlight'    => $parm['query']
                        , 'searchEngine' => $search_engine
                        )
                    )
                    , "html_preview" => $twig->render('common/preview.html', array('record' => $record)
                    )
                    , "others" => $twig->render('prod/preview/appears_in.html', array(
                        'parents' => $record->get_grouping_parents(),
                        'baskets' => $record->get_container_baskets()
                        )
                    )
                    , "current" => $train
                    , "history" => $twig->render('prod/preview/short_history.html', array('record'     => $record)
                    )
                    , "popularity" => $twig->render('prod/preview/popularity.html', array('record' => $record)
                    )
                    , "tools"  => $twig->render('prod/preview/tools.html', array('record' => $record)
                    )
                    , "pos"    => $record->get_number()
                    , "title"  => $record->get_title($parm['query'], $search_engine)
                )
        );

        break;
    case 'HOME':
        $output = phrasea::getHome('PUBLI', 'client');
        break;
    case 'CSS':
        $output = $user->setPrefs('css', $parm['color']);
        break;
    case 'BASK_STATUS':
        $output = $user->setPrefs('client_basket_status', $parm['mode']);
        break;
    case 'BASKUPDATE':
        $noview = 0;

        $repository = $em->getRepository('\Entities\Basket');

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

