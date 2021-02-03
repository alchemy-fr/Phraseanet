<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Root\RootController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Root implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.root'] = $app->share(function (PhraseaApplication $app) {
            return (new RootController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);

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
}
