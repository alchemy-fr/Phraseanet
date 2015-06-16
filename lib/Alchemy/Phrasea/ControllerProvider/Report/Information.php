<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Report;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Report\InformationController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Information implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.report.information'] = $app->share(function (PhraseaApplication $app) {
            return new InformationController($app);
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

        $controllers->post('/user', 'controller.report.information:doReportInformationUser')
            ->bind('report_infomations_user');

        $controllers->post('/browser', 'controller.report.information:doReportInformationBrowser')
            ->bind('report_infomations_browser');

        $controllers->post('/document', 'controller.report.information:doReportInformationDocument')
            ->bind('report_infomations_document');

        return $controllers;
    }
}
