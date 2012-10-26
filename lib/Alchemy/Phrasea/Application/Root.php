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
use Alchemy\Phrasea\Controller\Datafiles;
use Alchemy\Phrasea\Controller\Permalink;
use Alchemy\Phrasea\Controller\Admin\Collection;
use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;
use Alchemy\Phrasea\Controller\Admin\Dashboard;
use Alchemy\Phrasea\Controller\Admin\Databox;
use Alchemy\Phrasea\Controller\Admin\Databoxes;
use Alchemy\Phrasea\Controller\Admin\Description;
use Alchemy\Phrasea\Controller\Admin\Fields;
use Alchemy\Phrasea\Controller\Admin\Publications;
use Alchemy\Phrasea\Controller\Admin\Root;
use Alchemy\Phrasea\Controller\Admin\Setup;
use Alchemy\Phrasea\Controller\Admin\SearchEngine;
use Alchemy\Phrasea\Controller\Admin\Subdefs;
use Alchemy\Phrasea\Controller\Admin\TaskManager;
use Alchemy\Phrasea\Controller\Admin\Users;
use Alchemy\Phrasea\Controller\Prod\Basket;
use Alchemy\Phrasea\Controller\Prod\Bridge;
use Alchemy\Phrasea\Controller\Prod\Download;
use Alchemy\Phrasea\Controller\Prod\DoDownload;
use Alchemy\Phrasea\Controller\Prod\Edit;
use Alchemy\Phrasea\Controller\Prod\Export;
use Alchemy\Phrasea\Controller\Prod\Feed;
use Alchemy\Phrasea\Controller\Prod\Language;
use Alchemy\Phrasea\Controller\Prod\Lazaret;
use Alchemy\Phrasea\Controller\Prod\MoveCollection;
use Alchemy\Phrasea\Controller\Prod\MustacheLoader;
use Alchemy\Phrasea\Controller\Prod\Order;
use Alchemy\Phrasea\Controller\Prod\Printer;
use Alchemy\Phrasea\Controller\Prod\Push;
use Alchemy\Phrasea\Controller\Prod\Query;
use Alchemy\Phrasea\Controller\Prod\Property;
use Alchemy\Phrasea\Controller\Prod\Records;
use Alchemy\Phrasea\Controller\Prod\Root as Prod;
use Alchemy\Phrasea\Controller\Prod\Share;
use Alchemy\Phrasea\Controller\Prod\Story;
use Alchemy\Phrasea\Controller\Prod\Tools;
use Alchemy\Phrasea\Controller\Prod\Tooltip;
use Alchemy\Phrasea\Controller\Prod\TOU;
use Alchemy\Phrasea\Controller\Prod\Upload;
use Alchemy\Phrasea\Controller\Prod\UsrLists;
use Alchemy\Phrasea\Controller\Prod\WorkZone;
use Alchemy\Phrasea\Controller\Root\Account;
use Alchemy\Phrasea\Controller\Root\Developers;
use Alchemy\Phrasea\Controller\Root\Login;
use Alchemy\Phrasea\Controller\Root\RSSFeeds;
use Alchemy\Phrasea\Controller\Root\Session;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;
use Alchemy\Phrasea\Controller\User\Notifications;
use Alchemy\Phrasea\Controller\User\Preferences;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return call_user_func(function($environment = null) {

    $app = new PhraseaApplication($environment);

    $app->before(function () use ($app) {
        $app['firewall']->requireSetup();
    });

    $app->before(function(Request $request) use ($app) {
        if ($request->cookies->has('persistent') && !$app->isAuthenticated()) {
            try {
                $auth = new \Session_Authentication_PersistentCookie($app, $request->cookies->get('persistent'));
                $app->openAccount($auth, $auth->getSessionId());
            } catch (\Exception $e) {

            }
        }
    });

    $app->get('/', function(PhraseaApplication $app) {
        if ($app['browser']->isMobile()) {
            return $app->redirect("/login/?redirect=lightbox");
        } elseif ($app['browser']->isNewGeneration()) {
            return $app->redirect("/login/?redirect=prod");
        } else {
            return $app->redirect("/login/?redirect=client");
        }
    });

    $app->get('/robots.txt', function(PhraseaApplication $app) {

        if ($app['phraseanet.registry']->get('GV_allow_search_engine') === true) {
            $buffer = "User-Agent: *\n" . "Allow: /\n";
        } else {
            $buffer = "User-Agent: *\n" . "Disallow: /\n";
        }

        return new Response($buffer, 200, array('Content-Type' => 'text/plain'));
    })->bind('robots');

    $app->mount('/feeds/', new RSSFeeds());
    $app->mount('/account/', new Account());
    $app->mount('/login/', new Login());
    $app->mount('/developers/', new Developers());
    $app->mount('/lightbox/', new Lightbox());

    $app->mount('/datafiles/', new Datafiles());
    $app->mount('/permalink/', new Permalink());

    $app->mount('/admin/', new Root());
    $app->mount('/admin/dashboard', new Dashboard());
    $app->mount('/admin/collection', new Collection());
    $app->mount('/admin/databox', new Databox());
    $app->mount('/admin/databoxes', new Databoxes());
    $app->mount('/admin/setup', new Setup());
    $app->mount('/admin/search-engine', new SearchEngine());
    $app->mount('/admin/connected-users', new ConnectedUsers());
    $app->mount('/admin/publications', new Publications());
    $app->mount('/admin/users', new Users());
    $app->mount('/admin/fields', new Fields());
    $app->mount('/admin/task-manager', new TaskManager());
    $app->mount('/admin/subdefs', new Subdefs());
    $app->mount('/admin/description', new Description());
    $app->mount('/admin/tests/connection', new ConnectionTest());
    $app->mount('/admin/tests/pathurl', new PathFileTest());

    $app->mount('/prod/query/', new Query());
    $app->mount('/prod/order/', new Order());
    $app->mount('/prod/baskets', new Basket());
    $app->mount('/prod/download', new Download());
    $app->mount('/prod/story', new Story());
    $app->mount('/prod/WorkZone', new WorkZone());
    $app->mount('/prod/lists', new UsrLists());
    $app->mount('/prod/MustacheLoader', new MustacheLoader());
    $app->mount('/prod/records/', new Records());
    $app->mount('/prod/records/edit', new Edit());
    $app->mount('/prod/records/property', new Property());
    $app->mount('/prod/records/movecollection', new MoveCollection());
    $app->mount('/prod/bridge/', new Bridge());
    $app->mount('/prod/push/', new Push());
    $app->mount('/prod/printer/', new Printer());
    $app->mount('/prod/share/', new Share());
    $app->mount('/prod/export/', new Export());
    $app->mount('/prod/TOU/', new TOU());
    $app->mount('/prod/feeds', new Feed());
    $app->mount('/prod/tooltip', new Tooltip());
    $app->mount('/prod/language', new Language());
    $app->mount('/prod/tools/', new Tools());
    $app->mount('/prod/lazaret/', new Lazaret());
    $app->mount('/prod/upload/', new Upload());
    $app->mount('/prod/', new Prod());

    $app->mount('/user/preferences/', new Preferences());
    $app->mount('/user/notifications/', new Notifications());

    $app->mount('/download/', new DoDownload());
    $app->mount('/session/', new Session());

    $app->error(function(\Exception $e) use ($app) {
        $request = $app['request'];

        if ($e instanceof \Bridge_Exception) {
            $params = array(
                'message'      => $e->getMessage()
                , 'file'         => $e->getFile()
                , 'line'         => $e->getLine()
                , 'r_method'     => $request->getMethod()
                , 'r_action'     => $request->getRequestUri()
                , 'r_parameters' => ($request->getMethod() == 'GET' ? array() : $request->request->all())
            );

            if ($e instanceof \Bridge_Exception_ApiConnectorNotConfigured) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/notconfigured.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiConnectorNotConnected) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiConnectorAccessTokenFailed) {
                $params = array_merge($params, array('account' => $app['current_account']));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/disconnected.html.twig', $params), 200, array('X-Status-Code' => 200));
            } elseif ($e instanceof \Bridge_Exception_ApiDisabled) {
                $params = array_merge($params, array('api' => $e->get_api()));

                $response = new Response($app['twig']->render('/prod/actions/Bridge/deactivated.html.twig', $params), 200, array('X-Status-Code' => 200));
            } else {
                $response = new Response($app['twig']->render('/prod/actions/Bridge/error.html.twig', $params), 200, array('X-Status-Code' => 200));
            }

            $response->headers->set('Phrasea-StatusCode', 200);

            return $response;
        }

        if ($request->getRequestFormat() == 'json') {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );

            return $app->json($datas, 200, array('X-Status-Code' => 200));
        }

        if ($e instanceof HttpExceptionInterface) {
            $headers = $e->getHeaders();

            if (isset($headers['X-Phraseanet-Redirect'])) {
                return new RedirectResponse($headers['X-Phraseanet-Redirect'], 302, array('X-Status-Code' => 302));
            }
        }

        if ($e instanceof \Exception_BadRequest) {
            return new Response('Bad Request', 400, array('X-Status-Code' => 400));
        }
        if ($e instanceof \Exception_Forbidden) {
            return new Response('Forbidden', 403, array('X-Status-Code' => 403));
        }

        if ($e instanceof \Exception_Session_NotAuthenticated) {
            $code = 403;
            $message = 'Forbidden';
        } elseif ($e instanceof \Exception_NotAllowed) {
            $code = 403;
            $message = 'Forbidden';
        } elseif ($e instanceof \Exception_NotFound) {
            $code = 404;
            $message = 'Not Found';
        } else {
            throw $e;
        }

        return new Response($message, $code, array('X-Status-Code' => $code));
    });

    return $app;
}, isset($environment) ? $environment : null);
