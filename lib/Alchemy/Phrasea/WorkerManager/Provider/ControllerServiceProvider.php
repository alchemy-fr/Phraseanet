<?php

namespace Alchemy\Phrasea\WorkerManager\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Security\Firewall;
use Alchemy\Phrasea\WorkerManager\Controller\AdminConfigurationController;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerServiceProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['controller.worker.admin.configuration'] = $app->share(function (PhraseaApplication $app) {
            return new AdminConfigurationController($app);
        });

        // example of route to check webhook
        $app->post('/webhook', array($this, 'getWebhookData'));
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight(\ACL::TASKMANAGER);
        });

        $controllers->match('/',  'controller.worker.admin.configuration:indexAction')
            ->method('GET')
            ->bind('worker_admin');

        $controllers->match('/configuration',  'controller.worker.admin.configuration:configurationAction')
            ->method('GET|POST')
            ->bind('worker_admin_configuration');

        $controllers->match('/info',  'controller.worker.admin.configuration:infoAction')
            ->method('GET')
            ->bind('worker_admin_info');

        $controllers->match('/truncate',  'controller.worker.admin.configuration:truncateTableAction')
            ->method('POST')
            ->bind('worker_admin_truncate');

        $controllers->match('/delete-finished',  'controller.worker.admin.configuration:deleteFinishedAction')
            ->method('POST')
            ->bind('worker_admin_delete_finished');

        $controllers->match('/searchengine',  'controller.worker.admin.configuration:searchengineAction')
            ->method('GET|POST')
            ->bind('worker_admin_searchengine');

        $controllers->match('/subview',  'controller.worker.admin.configuration:subviewAction')
            ->method('GET|POST')
            ->bind('worker_admin_subview');

        $controllers->match('/metadata',  'controller.worker.admin.configuration:metadataAction')
            ->method('GET|POST')
            ->bind('worker_admin_metadata');

        $controllers->get('/populate-status',  'controller.worker.admin.configuration:populateStatusAction')
            ->bind('worker_admin_populate_status');

        $controllers->match('/pull-assets',  'controller.worker.admin.configuration:pullAssetsAction')
            ->method('GET|POST')
            ->bind('worker_admin_pullAssets');

        $controllers->match('/queue-monitor',  'controller.worker.admin.configuration:queueMonitorAction')
            ->method('GET')
            ->bind('worker_admin_queue_monitor');

        $controllers->match('/purge-queue',  'controller.worker.admin.configuration:purgeQueueAction')
            ->method('POST')
            ->bind('worker_admin_purge_queue');

        $controllers->match('/{workerId}/change-status',  'controller.worker.admin.configuration:changeStatusAction')
            ->method('POST')
            ->assert('workerId', '\d+')
            ->bind('worker_admin_change_status');

        return $controllers;
    }

    public function getWebhookData(Application $app, Request $request)
    {
        $messagePubliser = $this->getMessagePublisher($app);
        $messagePubliser->pushLog("RECEIVED ON phraseanet WEBHOOK URL TEST = ". $request->getUri() . " DATA : ". $request->getContent());

        return 0;
    }

    /**
     * @param Application $app
     * @return Firewall
     */
    private function getFirewall(Application $app)
    {
        return $app['firewall'];
    }

    /**
     * @param Application $app
     * @return MessagePublisher
     */
    private function getMessagePublisher(Application $app)
    {
        return $app['alchemy_worker.message.publisher'];
    }
}
