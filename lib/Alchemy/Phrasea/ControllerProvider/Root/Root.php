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
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Root implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Container $app)
    {
        $app['controller.root'] = function (PhraseaApplication $app) {
            return (new RootController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'));
        };
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
