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

use ACL;
use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\ToolsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerCollection;
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

    /**
     *
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight(ACL::IMGTOOLS);
        });

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::indexAction */
        $controllers->get('/', 'controller.prod.tools:indexAction');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::rotateAction */
        $controllers->post('/rotate/', 'controller.prod.tools:rotateAction')
            ->bind('prod_tools_rotate');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::imageAction */
        $controllers->post('/image/', 'controller.prod.tools:imageAction')
            ->bind('prod_tools_image');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::hddocAction */
        $controllers->post('/hddoc/', 'controller.prod.tools:hddocAction')
            ->bind('prod_tools_hd_substitution');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::changeThumbnailAction */
        $controllers->post('/chgthumb/', 'controller.prod.tools:changeThumbnailAction')
            ->bind('prod_tools_thumbnail_substitution');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::submitConfirmBoxAction */
        $controllers->post('/thumb-extractor/confirm-box/', 'controller.prod.tools:submitConfirmBoxAction');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::applyThumbnailExtractionAction */
        $controllers->post('/thumb-extractor/apply/', 'controller.prod.tools:applyThumbnailExtractionAction');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::editRecordSharing */
        $controllers->post('/sharing-editor/{base_id}/{record_id}/', 'controller.prod.tools:editRecordSharing');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::saveMetasAction */
        $controllers->post('/metadata/save/', 'controller.prod.tools:saveMetasAction')
            ->bind('prod_tools_metadata_save');

        /** @uses \Alchemy\Phrasea\Controller\Prod\ToolsController::videoEditorAction */
        $controllers->get('/videoEditor', 'controller.prod.tools:videoEditorAction');

        return $controllers;
    }
}
