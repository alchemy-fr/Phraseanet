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
use Alchemy\Phrasea\Controller\Login\Authenticate as AuthenticateController;
use Silex\Application as SilexApp;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {

            $app = new PhraseaApplication();

            $app->before(function () use ($app) {
                    $app['phraseanet.core']['Firewall']->requireSetup($app);
                });

            $app->get('/', function(SilexApp $app) {
                    $browser = \Browser::getInstance();

                    if ($browser->isMobile()) {
                        return $app->redirect("/login/?redirect=/lightbox");
                    } elseif ($browser->isNewGeneration()) {
                        return $app->redirect("/login/?redirect=/prod");
                    } else {
                        return $app->redirect("/login/?redirect=/client");
                    }
                });

            $app->get('/robots.txt', function(SilexApp $app) {

                    if ($app['phraseanet.core']['Registry']->get('GV_allow_search_engine') === true) {
                        $buffer = "User-Agent: *\n" . "Allow: /\n";
                    } else {
                        $buffer = "User-Agent: *\n" . "Disallow: /\n";
                    }

                    return new Response($buffer, 200, array('Content-Type' => 'text/plain'));
                });

            $app->mount('/feeds/', new RSSFeeds());
            $app->mount('/account/', new Account());
            $app->mount('/login/authenticate/', new AuthenticateController());
            $app->mount('/login/', new Login());
            $app->mount('/developers/', new Developers());

            return $app;
        }
);
