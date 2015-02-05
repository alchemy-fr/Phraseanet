<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Root implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.root'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/language/{locale}/', 'controller.root:setLocale')
            ->bind('set_locale');

        $controllers
            ->get('/', 'controller.root:getRoot')
            ->bind('root');

        $controllers
            ->get('/available-languages', 'controller.root:getAvailableLanguages')
            ->bind('available_languages');

        $controllers
            ->get('/robots.txt', 'controller.root:getRobots')
            ->bind('robots');

        return $controllers;
    }

    public function getRobots(Application $app, Request $request)
    {
        if ($app['conf']->get(['registry', 'general', 'allow-indexation']) === true) {
            $buffer = "User-Agent: *\n" . "Allow: /\n";
        } else {
            $buffer = "User-Agent: *\n" . "Disallow: /\n";
        }

        return new Response($buffer, 200, ['Content-Type' => 'text/plain']);
    }

    public function getRoot(Application $app, Request $request)
    {
        return $app->redirectPath('homepage');
    }

    public function setLocale(Application $app, Request $request, $locale)
    {
        $response = $app->redirectPath('root');
        $response->headers->setCookie(new Cookie('locale', $locale));

        return $response;
    }

    public function getAvailableLanguages(Application $app, Request $request)
    {
        return $app->json($app['locales.available']);
    }
}
