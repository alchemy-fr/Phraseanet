<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\ToolsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Tools implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.tools'] = $app->share(function (PhraseaApplication $app) {
            return (new ToolsController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDispatcher($app['dispatcher'])
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setSubDefinitionSubstituerLocator(new LazyLocator($app, 'subdef.substituer'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight(\ACL::IMGTOOLS);
        });

        $controllers->get('/', 'controller.prod.tools:indexAction');

        $controllers->post('/rotate/', 'controller.prod.tools:rotateAction')
            ->bind('prod_tools_rotate');

        $controllers->post('/image/', 'controller.prod.tools:imageAction')
            ->bind('prod_tools_image');

        $controllers->post('/hddoc/', 'controller.prod.tools:hddocAction')
            ->bind('prod_tools_hd_substitution');

        $controllers->post('/chgthumb/', 'controller.prod.tools:changeThumbnailAction')
            ->bind('prod_tools_thumbnail_substitution');

        $controllers->post('/thumb-extractor/confirm-box/', 'controller.prod.tools:submitConfirmBoxAction');

        $controllers->post('/thumb-extractor/apply/', 'controller.prod.tools:applyThumbnailExtractionAction');

        $controllers->post('/sharing-editor/{base_id}/{record_id}/', 'controller.prod.tools:editRecordSharing');

        return $controllers;
    }
}
