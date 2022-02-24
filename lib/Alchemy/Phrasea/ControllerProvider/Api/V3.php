<?php

namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Api\V1Controller;
use Alchemy\Phrasea\Controller\Api\V3\V3Controller;
use Alchemy\Phrasea\Controller\Api\V3\V3RecordController;
use Alchemy\Phrasea\Controller\Api\V3\V3ResultHelpers;
use Alchemy\Phrasea\Controller\Api\V3\V3SearchController;
use Alchemy\Phrasea\Controller\Api\V3\V3SearchRawController;
use Alchemy\Phrasea\Controller\Api\V3\V3StoriesController;
use Alchemy\Phrasea\Controller\Api\V3\V3SubdefsServiceController;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


class V3 extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    const VERSION = '3.0.0';

    public function register(Application $app)
    {
        $app['controller.api.v3.resulthelpers'] = $app->share(function (PhraseaApplication $app) {
            return (new V3ResultHelpers(
                $app['conf'],
                $app['media_accessor.subdef_url_generator'],
                $app['authentication'],
                $app['url_generator']
            ));
        });
        $app['controller.api.v3.subdefs_service'] = $app->share(function (PhraseaApplication $app) {
            return (new V3SubdefsServiceController($app))
                ->setJsonBodyHelper($app['json.body_helper'])
                ->setDispatcher($app['dispatcher'])
                ;
        });
        $app['controller.api.v3.records'] = $app->share(function (PhraseaApplication $app) {
            return (new V3RecordController($app))
                ->setJsonBodyHelper($app['json.body_helper'])
                ->setDispatcher($app['dispatcher'])
                ;
        });
        $app['controller.api.v3.search'] = $app->share(function (PhraseaApplication $app) {
            return (new V3SearchController($app));
        });
        $app['controller.api.v3.searchraw'] = $app->share(function (PhraseaApplication $app) {
            return (new V3SearchRawController($app));
        });
        $app['controller.api.v3.stories'] = $app->share(function (PhraseaApplication $app) {
            return (new V3StoriesController($app));
        });
        $app['controller.api.v3'] = $app->share(function (PhraseaApplication $app) {
            return (new V3Controller($app));
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        if (! $this->isApiEnabled($app)) {
            return $app['controllers_factory'];
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->before(new OAuthListener());

        /**
         * @uses V3StoriesController::childrenAction_GET()
         * @uses V1Controller::ensureCanAccessToRecord()
         */
        $controllers->get('/stories/{databox_id}/{record_id}/children/', 'controller.api.v3.stories:childrenAction_GET')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');

        /**
         * nb : uses the record controller to get a story
         * @uses V3RecordController::indexAction_GET()
         * @uses V1Controller::ensureCanAccessToRecord()
         */
        $controllers->get('/stories/{databox_id}/{record_id}/', 'controller.api.v3.records:indexAction_GET')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+')
            ->value('must_be_story', true);

        /**
         * @uses V3SearchController::helloAction()
         */
        $controllers->match('/hello/', 'controller.api.v3.searchraw:helloAction');

        /**
         * @uses V3SearchController::searchAction()
         */
        $controllers->match('/search/', 'controller.api.v3.search:searchAction');

        /**
         * @uses V3SearchController::searchRawAction()
         */
        $controllers->match('/searchraw/', 'controller.api.v3.searchraw:searchRawAction');

        /**
         * @uses V3RecordController::indexAction_GET()
         */
        $controllers->get('/records/{databox_id}/{record_id}/', 'controller.api.v3.records:indexAction_GET')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('api.v3.records:indexAction_GET');

        /**
         * @uses V3RecordController::indexAction_PATCH()
         * @uses V1Controller::ensureCanAccessToRecord()
         * @uses V1Controller::ensureCanModifyRecord()
         */
        $controllers->patch('/records/{databox_id}/{record_id}/', 'controller.api.v3.records:indexAction_PATCH')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->before('controller.api.v1:ensureCanModifyRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');

        /**
         * @uses V3RecordController::indexAction_POST()
         * @uses V1Controller::ensureCanAccessToRecord()
         * @uses V1Controller::ensureCanModifyRecord()
         */
        $controllers->post('/records/{base_id}/', 'controller.api.v3.records:indexAction_POST')
            ->assert('base_id', '\d+');

        /**
         * @uses V1Controller::getBadRequestAction()
         */
        $controllers->match('/records/{any_id}/{anyother_id}/setmetadatas/', 'controller.api.v1:getBadRequestAction');

        if ($this->isApiSubdefServiceEnabled($app)) {
            /**
             * @uses V3SubdefsServiceController::callbackAction_POST()
             */
            $controllers->post('/subdefs_service_callback/', 'controller.api.v3.subdefs_service:callbackAction_POST');

            /**
             * @uses V3SubdefsServiceController::indexAction_POST()
             */
            $controllers->post('/subdefs_service/', 'controller.api.v3.subdefs_service:indexAction_POST');
        }

        /**
         * @uses V3Controller::getDataboxSubdefsAction()
         */
        $controllers->get('/databoxes/{databox_id}/subdefs/', 'controller.api.v3:getDataboxSubdefsAction')
            ->before('controller.api.v1:ensureAccessToDatabox')
            ->before('controller.api.v1:ensureCanSeeDataboxStructure')
            ->assert('databox_id', '\d+');

        $controllers->get('/databoxes/subdefs/', 'controller.api.v3:getDataboxSubdefsAction');

        return $controllers;
    }

    private function isApiSubdefServiceEnabled(Application $application)
    {
        /** @var PropertyAccess $config */
        $config = $application['conf'];

        return $config->get([ 'registry', 'api-clients', 'api-subdef_service' ], false);
    }
}
