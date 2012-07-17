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

use Alchemy\Phrasea\Controller\Root as Controller;
use Silex\Application as SilexApp;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function() {
            $app = new SilexApp();

            $app['Core'] = \bootstrap::getCore();

            $app->register(new ValidatorServiceProvider());

            $app->before(function () use ($app) {
                    $app['Core']['Firewall']->requireSetup($app);
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

                    if ($app['Core']['Registry']->get('GV_allow_search_engine') === true) {
                        $buffer = "User-Agent: *\n" . "Allow: /\n";
                    } else {
                        $buffer = "User-Agent: *\n" . "Disallow: /\n";
                    }

                    $response = new Response($buffer, 200, array('Content-Type' => 'text/plain'));
                    $response->setCharset('UTF-8');

                    return $response;
                });

            $app->mount('/feeds/', new Controller\RSSFeeds());
            $app->mount('/account/', new Controller\Account());
            $app->mount('/developers/', new Controller\Developers());
            $app->mount('/login/', new Controller\Login());

            return $app;
        }
);
