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
use Alchemy\Phrasea\Controller\Prod\RecordController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Record implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.records'] = $app->share(function (PhraseaApplication $app) {
            return (new RecordController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setSearchEngineLocator(new LazyLocator($app, 'phraseanet.SE'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        /** @uses RecordController::getRecord() */
        $controllers->match('/', 'controller.prod.records:getRecord')
            ->bind('record_details')
            ->method('GET|POST');

        /** @uses RecordController::getRecordById() */
        $controllers->get('/record/{sbasId}/{recordId}/', 'controller.prod.records:getRecordById')
            ->bind('record_single')
            ->assert('sbasId', '\d+')
            ->assert('recordId', '\d+');

        /** @uses RecordController::doDeleteRecords() */
        $controllers->post('/delete/', 'controller.prod.records:doDeleteRecords')
            ->bind('record_delete');

        /** @uses RecordController::whatCanIDelete() */
        $controllers->post('/delete/what/', 'controller.prod.records:whatCanIDelete')
            ->bind('record_what_can_i_delete');

        /** @uses RecordController::renewUrl() */
        $controllers->post('/renew-url/', 'controller.prod.records:renewUrl')
            ->bind('record_renew_url');

        return $controllers;
    }
}
