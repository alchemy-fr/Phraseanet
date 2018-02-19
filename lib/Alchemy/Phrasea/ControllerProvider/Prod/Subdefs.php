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
use Alchemy\Phrasea\Controller\Prod\SubdefsController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Silex\Application;

class Subdefs implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.subdefs'] = $app->share(function (PhraseaApplication $app) {
            return (new SubdefsController($app));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->get('/{databox_id}/{record_id}/metadatas/{subdef_name}/', 'controller.prod.subdefs:metadataAction')
            ->bind('prod_subdefs_metadata');

        return $controllers;
    }
}
