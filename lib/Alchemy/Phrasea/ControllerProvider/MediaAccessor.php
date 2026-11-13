<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Controller\MediaAccessorController;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use Alchemy\Phrasea\Model\Entities\Secret;
use Alchemy\Phrasea\Model\Provider\DefaultSecretProvider;
use Doctrine\ORM\EntityManagerInterface;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class MediaAccessor implements ServiceProviderInterface, ControllerProviderInterface
{
    use ControllerProviderTrait;

    public function register(Container $app)
    {
        $app['repo.secrets'] = function (Application $app) {
            /** @var EntityManagerInterface $manager */
            $manager = $app['orm.em'];
            return $manager->getRepository(Secret::class);
        };

        $app['provider.secrets'] = function (Application $app) {
            return new DefaultSecretProvider($app['repo.secrets'], $app['random.medium']);
        };

        $app['media_accessor.subdef_url_generator'] = function (Application $app) {
            $defaultTTL = (int)$app['conf']->get(['registry', 'general', 'default-subdef-url-ttl'], 0);

            return new MediaSubDefinitionUrlGenerator($app['url_generator'], $app['provider.secrets'], $defaultTTL);
        };


        $app['controller.media_accessor'] = function (Application $app) {
            return (new MediaAccessorController($app))
                ->setAllowedAlgorithms(['HS256'])
                ->setKeyStorage($app['provider.secrets']);
        };

        $app['controller.media_accessor.route_prefix'] = '/medias';
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $controllers->get('/{token}', 'controller.media_accessor:showAction')
            ->bind('media_accessor');

        return $controllers;
    }
}
