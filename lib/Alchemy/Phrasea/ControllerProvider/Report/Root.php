<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Report;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Report\RootController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Root implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.report'] = $app->share(function (PhraseaApplication $app) {
            return new RootController($app);
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
            $firewall->requireAccessToModule('report');
        });

        $controllers->get('/', 'controller.report:indexAction')
            ->bind('report');

        $controllers->get('/dashboard', 'controller.report:getDashboard')
            ->bind('report_dashboard');

        $controllers->post('/init', 'controller.report:initReport')
            ->bind('report_init');

        $controllers->post('/connexions', 'controller.report:doReportConnexions')
            ->bind('report_connexions');

        $controllers->post('/questions', 'controller.report:doReportQuestions')
            ->bind('report_questions');

        $controllers->post('/downloads', 'controller.report:doReportDownloads')
            ->bind('report_downloads');

        $controllers->post('/documents', 'controller.report:doReportDocuments')
            ->bind('report_documents');

        $controllers->post('/clients', 'controller.report:doReportClients')
            ->bind('report_clients');

        return $controllers;
    }
}
