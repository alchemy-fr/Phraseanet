<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\Basket;
use Alchemy\Phrasea\Controller\Prod\Bridge;
use Alchemy\Phrasea\Controller\Prod\Edit;
use Alchemy\Phrasea\Controller\Prod\Feed;
use Alchemy\Phrasea\Controller\Prod\Language;
use Alchemy\Phrasea\Controller\Prod\Lazaret;
use Alchemy\Phrasea\Controller\Prod\MoveCollection;
use Alchemy\Phrasea\Controller\Prod\MustacheLoader;
use Alchemy\Phrasea\Controller\Prod\Order;
use Alchemy\Phrasea\Controller\Prod\Printer;
use Alchemy\Phrasea\Controller\Prod\Push;
use Alchemy\Phrasea\Controller\Prod\Query;
use Alchemy\Phrasea\Controller\Prod\Root;
use Alchemy\Phrasea\Controller\Prod\Story;
use Alchemy\Phrasea\Controller\Prod\Tools;
use Alchemy\Phrasea\Controller\Prod\Tooltip;
use Alchemy\Phrasea\Controller\Prod\TOU;
use Alchemy\Phrasea\Controller\Prod\Upload;
use Alchemy\Phrasea\Controller\Prod\UserPreferences;
use Alchemy\Phrasea\Controller\Prod\UsrLists;
use Alchemy\Phrasea\Controller\Prod\WorkZone;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {

            $app = new PhraseaApplication();

            $app->mount('/UserPreferences/', new UserPreferences());
            $app->mount('/query/', new Query());
            $app->mount('/order/', new Order());
            $app->mount('/baskets', new Basket());
            $app->mount('/story', new Story());
            $app->mount('/WorkZone', new WorkZone());
            $app->mount('/lists', new UsrLists());
            $app->mount('/MustacheLoader', new MustacheLoader());
            $app->mount('/records/edit', new Edit());
            $app->mount('/records/movecollection', new MoveCollection());
            $app->mount('/bridge/', new Bridge());
            $app->mount('/push/', new Push());
            $app->mount('/printer/', new Printer());
            $app->mount('/TOU/', new TOU());
            $app->mount('/feeds', new Feed());
            $app->mount('/tooltip', new Tooltip());
            $app->mount('/language', new Language());
            $app->mount('/tools/', new Tools());
            $app->mount('/lazaret/', new Lazaret());
            $app->mount('/upload/', new Upload());
            $app->mount('/', new Root());

            $app->error(function (\Exception $e, $code) use ($app) {
                    /* @var $request \Symfony\Component\HttpFoundation\Request */
                    $request = $app['request'];

                    if ($request->getRequestFormat() == 'json') {
                        $datas = array(
                            'success' => false
                            , 'message' => $e->getMessage()
                        );

                        return $app->json($datas, 200, array('X-Status-Code' => 200));
                    }
                    if ($e instanceof \Exception_BadRequest) {
                        return new Response('Bad Request', 400, array('X-Status-Code' => 400));
                    }
                    if ($e instanceof \Exception_NotFound) {
                        return new Response('Not Found', 404, array('X-Status-Code' => 404));
                    }
                    if ($e instanceof \Exception_Forbidden) {
                        return new Response('Not Found', 403, array('X-Status-Code' => 403));
                    }
                });

            return $app;
        }
);
