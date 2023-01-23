<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Api\V1Controller;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class V1 extends Api implements ControllerProviderInterface, ServiceProviderInterface
{
    const VERSION = '2.0.0';

    public static $extendedContentTypes = [
        'json' => ['application/vnd.phraseanet.record-extended+json'],
        'yaml' => ['application/vnd.phraseanet.record-extended+yaml'],
        'jsonp' => ['application/vnd.phraseanet.record-extended+jsonp'],
    ];

    public function register(Application $app)
    {
        $app['controller.api.v1'] = $app->share(function (PhraseaApplication $app) {
            return (new V1Controller($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDispatcher($app['dispatcher'])
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setJsonBodyHelper(new LazyLocator($app, 'json.body_helper'));
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

        $controllers->get('/monitor/scheduler/', 'controller.api.v1:getSchedulerAction')
            ->before('controller.api.v1:ensureAdmin');

        $controllers->get('/monitor/tasks/', 'controller.api.v1:indexTasksAction')
            ->before('controller.api.v1:ensureAdmin');
        $controllers->get('/monitor/task/{task}/', 'controller.api.v1:showTaskAction')
            ->convert('task', $app['converter.task-callback'])
            ->before('controller.api.v1:ensureAdmin')
            ->assert('task', '\d+');
        $controllers->post('/monitor/task/{task}/', 'controller.api.v1:setTaskPropertyAction')
            ->convert('task', $app['converter.task-callback'])
            ->before('controller.api.v1:ensureAdmin')
            ->assert('task', '\d+');
        $controllers->post('/monitor/task/{task}/start/', 'controller.api.v1:startTaskAction')
            ->convert('task', $app['converter.task-callback'])
            ->before('controller.api.v1:ensureAdmin');
        $controllers->post('/monitor/task/{task}/stop/', 'controller.api.v1:stopTaskAction')
            ->convert('task', $app['converter.task-callback'])
            ->before('controller.api.v1:ensureAdmin');

        $controllers->get('/monitor/phraseanet/', 'controller.api.v1:showPhraseanetConfigurationAction')
            ->before('controller.api.v1:ensureAdmin');

        $controllers->get('/collections/{base_id}/', 'controller.api.v1:getDataboxCollectionAction')
            ->before('controller.api.v1:ensureAccessToBase')
            ->assert('base_id', '\d+');

        $controllers->get('/databoxes/list/', 'controller.api.v1:listDataboxesAction');

        $controllers->get('/databoxes/{databox_id}/collections/', 'controller.api.v1:getDataboxCollectionsAction')
            ->before('controller.api.v1:ensureAccessToDatabox')
            ->assert('databox_id', '\d+');
        $controllers->get('/databoxes/{any_id}/collections/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/databoxes/{databox_id}/status/', 'controller.api.v1:getDataboxStatusAction')
            ->before('controller.api.v1:ensureAccessToDatabox')
            ->before('controller.api.v1:ensureCanSeeDataboxStructure')
            ->assert('databox_id', '\d+');
        $controllers->get('/databoxes/{any_id}/status/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/databoxes/{databox_id}/metadatas/', 'controller.api.v1:getDataboxMetadataAction')
            ->before('controller.api.v1:ensureAccessToDatabox')
            ->before('controller.api.v1:ensureCanSeeDataboxStructure')
            ->assert('databox_id', '\d+');
        $controllers->get('/databoxes/{any_id}/metadatas/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/databoxes/{databox_id}/termsOfUse/', 'controller.api.v1:getDataboxTermsAction')
            ->before('controller.api.v1:ensureAccessToDatabox')
            ->assert('databox_id', '\d+');
        $controllers->get('/databoxes/{any_id}/termsOfUse/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/quarantine/list/', 'controller.api.v1:listQuarantineAction');

        $controllers->get('/quarantine/item/{lazaret_id}/', 'controller.api.v1:listQuarantineItemAction');
        $controllers->get('/quarantine/item/{any_id}/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/records/add/', 'controller.api.v1:addRecordAction');

        $controllers->post('/embed/substitute/', 'controller.api.v1:substituteAction');

        $controllers->match('/search/', 'controller.api.v1:searchAction');

        $controllers->match('/records/search/', 'controller.api.v1:searchRecordsAction');

        $controllers->get('/records/{databox_id}/{record_id}/caption/', 'controller.api.v1:getRecordCaptionAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/caption/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/records/{databox_id}/{record_id}/metadatas/', 'controller.api.v1:getRecordMetadataAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/metadatas/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/records/{databox_id}/{record_id}/status/', 'controller.api.v1:getRecordStatusAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/status/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/records/{databox_id}/{record_id}/related/', 'controller.api.v1:getRelatedRecordsAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/related/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/records/{databox_id}/{record_id}/embed/', 'controller.api.v1:getEmbeddedRecordAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/embed/', 'controller.api.v1:getBadRequestAction');

        $controllers->post(
            '/records/{databox_id}/{record_id}/setmetadatas/',
            'controller.api.v1:setRecordMetadataAction'
        )
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->before('controller.api.v1:ensureCanModifyRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->post('/records/{any_id}/{anyother_id}/setmetadatas/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/records/{databox_id}/{record_id}/setstatus/', 'controller.api.v1:setRecordStatusAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->before('controller.api.v1:ensureCanModifyRecordStatus')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->post('/records/{any_id}/{anyother_id}/setstatus/', 'controller.api.v1:getBadRequestAction');

        $controllers->post(
            '/records/{databox_id}/{record_id}/setcollection/',
            'controller.api.v1:setRecordCollectionAction'
        )
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->before('controller.api.v1:ensureCanMoveRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->post(
            '/records/{wrong_databox_id}/{wrong_record_id}/setcollection/',
            'controller.api.v1:getBadRequestAction'
        );

        $controllers->delete('/records/{databox_id}/{record_id}/', 'controller.api.v1:deleteRecordAction')
            ->before('controller.api.v1:ensureCanDeleteToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->get('/records/{databox_id}/{record_id}/', 'controller.api.v1:getRecordAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/records/{any_id}/{anyother_id}/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/baskets/list/', 'controller.api.v1:searchBasketsAction');

        $controllers->post('/baskets/add/', 'controller.api.v1:createBasketAction');

        $controllers->get('/baskets/{basket}/content/', 'controller.api.v1:getBasketAction')
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access'])
            ->assert('basket', '\d+');
        $controllers->get('/baskets/{wrong_basket}/content/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/baskets/{basket}/setname/', 'controller.api.v1:setBasketTitleAction')
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-is-owner'])
            ->assert('basket', '\d+');
        $controllers->post('/baskets/{wrong_basket}/setname/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/baskets/{basket}/setdescription/', 'controller.api.v1:setBasketDescriptionAction')
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-is-owner'])
            ->assert('basket', '\d+');
        $controllers->post('/baskets/{wrong_basket}/setdescription/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/baskets/{basket}/delete/', 'controller.api.v1:deleteBasketAction')
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-is-owner'])
            ->assert('basket', '\d+');
        $controllers->post('/baskets/{wrong_basket}/delete/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/feeds/list/', 'controller.api.v1:searchPublicationsAction');

        $controllers->get('/feeds/content/', 'controller.api.v1:getPublicationsAction');

        $controllers->get('/feeds/entry/{entry_id}/', 'controller.api.v1:getFeedEntryAction')
            ->assert('entry_id', '\d+');
        $controllers->get('/feeds/entry/{entry_id}/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/feeds/{feed_id}/content/', 'controller.api.v1:getPublicationAction')
            ->assert('feed_id', '\d+');
        $controllers->get('/feeds/{wrong_feed_id}/content/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/stories/{databox_id}/{record_id}/embed/', 'controller.api.v1:getStoryEmbedAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/stories/{any_id}/{anyother_id}/embed/', 'controller.api.v1:getBadRequestAction');

        $controllers->get('/stories/{databox_id}/{record_id}/', 'controller.api.v1:getStoryAction')
            ->before('controller.api.v1:ensureCanAccessToRecord')
            ->assert('databox_id', '\d+')
            ->assert('record_id', '\d+');
        $controllers->get('/stories/{any_id}/{anyother_id}/', 'controller.api.v1:getBadRequestAction');

        $controllers->post('/stories', 'controller.api.v1:createStoriesAction')
            ->before('controller.api.v1:ensureJsonContentType');

        $controllers->post('/stories/{databox_id}/{story_id}/addrecords', 'controller.api.v1:addRecordsToStoryAction')
            ->before('controller.api.v1:ensureJsonContentType')
            ->assert('databox_id', '\d+')
            ->assert('story_id', '\d+');

        $controllers->delete('/stories/{databox_id}/{story_id}/delrecords', 'controller.api.v1:delRecordsFromStoryAction')
            ->before('controller.api.v1:ensureJsonContentType')
            ->assert('databox_id', '\d+')
            ->assert('story_id', '\d+');

        $controllers->post('/stories/{databox_id}/{story_id}/setcover', 'controller.api.v1:setStoryCoverAction')
            ->before('controller.api.v1:ensureJsonContentType')
            ->assert('databox_id', '\d+')
            ->assert('story_id', '\d+');

        $controllers->get('/me/', 'controller.api.v1:getCurrentUserAction');
        $controllers->delete('/me/', 'controller.api.v1:deleteCurrentUserAction');
        $controllers->get('/me/structures/', 'controller.api.v1:getCurrentUserStructureAction');
        $controllers->get('/me/subdefs/', 'controller.api.v1:getCurrentUserSubdefsAction');
        $controllers->get('/me/collections/', 'controller.api.v1:getCurrentUserCollectionsAction');

        $controllers->post('/me/request-collections/', 'controller.api.v1:createCollectionRequests');
        $controllers->post('/me/update-account/', 'controller.api.v1:updateCurrentUserAction');
        $controllers->post('/me/update-password/', 'controller.api.v1:updateCurrentUserPasswordAction');

        $controllers->post('/accounts/reset-password/{email}/', 'controller.api.v1:requestPasswordReset')
            ->before('controller.api.v1:ensureUserManagementRights');

        $controllers->post('/accounts/update-password/{token}/', 'controller.api.v1:resetPassword')
            ->before('controller.api.v1:ensureUserManagementRights');

        $controllers->post('/accounts/access-demand/', 'controller.api.v1:createAccessDemand')
            ->before('controller.api.v1:ensureUserManagementRights');

        $controllers->post('/accounts/unlock/{token}/', 'controller.api.v1:unlockAccount')
            ->before('controller.api.v1:ensureUserManagementRights');

        // the api route for the uploader service
        $controllers->post('/upload/enqueue/', 'controller.api.v1:sendAssetsInQueue');

        return $controllers;
    }
}
