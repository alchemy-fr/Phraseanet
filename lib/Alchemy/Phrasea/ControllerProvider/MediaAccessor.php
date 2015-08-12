<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Controller\MediaAccessorController;
use Alchemy\Phrasea\Model\Provider\DefaultSecretProvider;
use Doctrine\ORM\EntityManager;
use RandomLib\Factory;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class MediaAccessor implements ServiceProviderInterface, ControllerProviderInterface
{
    public function register(Application $app)
    {
        $app['repo.secrets'] = $app->share(function (Application $app) {
            /** @var EntityManager $manager */
            $manager = $app['EM'];

            return $manager->getRepository('Entities\Secret');
        });

        $app['provider.secrets'] = $app->share(function (Application $app) {
            $factory = new Factory();

            return new DefaultSecretProvider($app['repo.secrets'], $factory->getMediumStrengthGenerator());
        });

        $app['controller.media_accessor'] = $app->share(function (Application $app) {
            $controller = new MediaAccessorController($app);

            $controller
                ->setAllowedAlgorithms(['HS256'])
                ->setKeyStorage($app['provider.secrets']);

            return $controller;
        });

        $app['controller.media_accessor.route_prefix'] = '/medias';
    }

    public function boot(Application $app)
    {
        // Intentionally left empty
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/{token}', 'controller.media_accessor:showAction')
            ->bind('media_accessor');

        return $controllers;
    }
}
