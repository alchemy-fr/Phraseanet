<?php

namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Api\V3\V3MetadatasController;
use Alchemy\Phrasea\Controller\Api\V3\V3SearchController;
use Alchemy\Phrasea\Controller\Api\V3\V3StoriesController;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


class V3 extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    const VERSION = '3.0.0';

    /**
     * @param Application $app
     *
     * @uses V3MetadatasController::setmetadatasAction()
     * @uses V3SearchController::searchAction()
     * @uses V3StoriesController::getStoryAction()
     */
    public function register(Application $app)
    {
        $app['controller.api.v3.metadatas'] = $app->share(function (PhraseaApplication $app) {
            return (new V3MetadatasController($app))
                ->setJsonBodyHelper($app['json.body_helper'])
            ;
        });
        $app['controller.api.v3.search'] = $app->share(function (PhraseaApplication $app) {
            return (new V3SearchController($app));
        });
        $app['controller.api.v3.stories'] = $app->share(function (PhraseaApplication $app) {
            return (new V3StoriesController($app));
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

        $controllers->get('/stories/{databox_id}/{record_id}/', 'controller.api.v3.stories:getStoryAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->match('/search/', 'controller.api.v3.search:searchAction');

        $controllers->patch('/records/{databox_id}/{record_id}/setmetadatas/', 'controller.api.v3.metadatas:setmetadatasAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->before('controller.api.v1:ensureCanModifyRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->match('/records/{any_id}/{anyother_id}/setmetadatas/', 'controller.api.v1:getBadRequestAction');

        return $controllers;
    }
}
