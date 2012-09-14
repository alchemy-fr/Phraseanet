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
use Alchemy\Phrasea\Controller\Root\RSSFeeds;
use Alchemy\Phrasea\Controller\Root\Account;
use Alchemy\Phrasea\Controller\Root\Developers;
use Alchemy\Phrasea\Controller\Root\Login;
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
use Alchemy\Phrasea\Controller\Admin\Sphinx;
use Alchemy\Phrasea\Controller\Admin\Subdefs;
use Alchemy\Phrasea\Controller\Admin\Users;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Controller\Login\Authenticate as AuthenticateController;
use Silex\Application as SilexApp;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function($environment = null) {

            $app = new PhraseaApplication($environment);

            $app->before(function () use ($app) {
                    return $app['firewall']->requireSetup($app);
                });

            $app->get('/', function(SilexApp $app) {

                    if ($app['browser']->isMobile()) {
                        return $app->redirect("/login/?redirect=lightbox");
                    } elseif ($app['browser']->isNewGeneration()) {
                        return $app->redirect("/login/?redirect=prod");
                    } else {
                        return $app->redirect("/login/?redirect=client");
                    }
                });

            $app->get('/robots.txt', function(SilexApp $app) {

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

            $app->mount('/admin/', new Root());
            $app->mount('/admin/dashboard', new Dashboard());
            $app->mount('/admin/collection', new Collection());
            $app->mount('/admin/databox', new Databox());
            $app->mount('/admin/databoxes', new Databoxes());
            $app->mount('/admin/setup', new Setup());
            $app->mount('/admin/sphinx', new Sphinx());
            $app->mount('/admin/connected-users', new ConnectedUsers());
            $app->mount('/admin/publications', new Publications());
            $app->mount('/admin/users', new Users());
            $app->mount('/admin/fields', new Fields());
            $app->mount('/admin/subdefs', new Subdefs());
            $app->mount('/admin/description', new Description());
            $app->mount('/admin/tests/connection', new ConnectionTest());
            $app->mount('/admin/tests/pathurl', new PathFileTest());

            return $app;
        }, isset($environment) ? $environment: null
);
