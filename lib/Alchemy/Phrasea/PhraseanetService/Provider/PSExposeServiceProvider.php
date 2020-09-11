<?php

namespace Alchemy\Phrasea\PhraseanetService\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\PhraseanetService\Controller\PSExposeController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class PSExposeServiceProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    /**
     * @inheritDoc
     */
    public function register(Application $app)
    {
        $app['controller.ps.expose'] = $app->share(function (PhraseaApplication $app) {
            return new PSExposeController($app);
        });
    }

    /**
     * @inheritDoc
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->match('/expose/create-publication/', 'controller.ps.expose:createPublicationAction')
            ->method('POST')
            ->bind('ps_expose_create_publication');

        $controllers->match('/expose/list-publication/', 'controller.ps.expose:listPublicationAction')
            ->method('GET')
            ->bind('ps_expose_list_publication');

        return $controllers;
    }

    /**
     * @inheritDoc
     */
    public function boot(Application $app)
    {

    }
}
