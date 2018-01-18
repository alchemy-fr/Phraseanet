<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Report;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Report\ActivityController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Activity implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.report.activity'] = $app->share(function (PhraseaApplication $app) {
            return new ActivityController($app);
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

        $controllers->post('/users/connexions', 'controller.report.activity:doReportConnexionsByUsers')
            ->bind('report_activity_users_connexions');

        $controllers->post('/users/downloads', 'controller.report.activity:doReportDownloadsByUsers')
            ->bind('report_activity_users_downloads');;

        $controllers->post('/questions/best-of', 'controller.report.activity:doReportBestOfQuestions')
            ->bind('report_activity_questions_bestof');

        $controllers->post('/questions/no-best-of', 'controller.report.activity:doReportNoBestOfQuestions')
            ->bind('report_activity_questions_nobestof');

        $controllers->post('/instance/hours', 'controller.report.activity:doReportSiteActiviyPerHours')
            ->bind('report_activity_instance_hours');

        $controllers->post('/instance/days', 'controller.report.activity:doReportSiteActivityPerDays')
            ->bind('report_activity_instance_days');

        $controllers->post('/documents/pushed', 'controller.report.activity:doReportPushedDocuments')
            ->bind('report_activity_documents_pushed');

        $controllers->post('/documents/added', 'controller.report.activity:doReportAddedDocuments')
            ->bind('report_activity_documents_added');

        $controllers->post('/documents/edited', 'controller.report.activity:doReportEditedDocuments')
            ->bind('report_activity_documents_edited');

        $controllers->post('/documents/validated', 'controller.report.activity:doReportValidatedDocuments')
            ->bind('report_activity_documents_validated');

        $controllers->post('/documents/sent', 'controller.report.activity:doReportSentDocuments')
            ->bind('report_activity_documents_sent');

        return $controllers;
    }
}
