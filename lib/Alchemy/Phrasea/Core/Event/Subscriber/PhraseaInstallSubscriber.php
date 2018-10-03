<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\InstallFinishEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Process;

class PhraseaInstallSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::INSTALL_FINISH => 'onInstallFinished'
        ];
    }

    public function onInstallFinished(InstallFinishEvent $event)
    {
        $this->createNavigatorApplication();
        $this->createOfficePluginApplication();
        $this->createAdobeCCPluginApplication();
        $this->generateProxies();
    }

    private function createNavigatorApplication()
    {
        $application = $this->app['manipulator.api-application']->create(
            \API_OAuth2_Application_Navigator::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_Navigator::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_Navigator::CLIENT_SECRET);

        $this->app['manipulator.api-application']->update($application);
    }

    private function createOfficePluginApplication()
    {
        $application = $this->app['manipulator.api-application']->create(
            \API_OAuth2_Application_OfficePlugin::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_OfficePlugin::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_OfficePlugin::CLIENT_SECRET);

        $this->app['manipulator.api-application']->update($application);
    }

    private function createAdobeCCPluginApplication()
    {
        $application = $this->app['manipulator.api-application']->create(
            \API_OAuth2_Application_AdobeCCPlugin::CLIENT_NAME,
            ApiApplication::DESKTOP_TYPE,
            '',
            'http://www.phraseanet.com',
            null,
            ApiApplication::NATIVE_APP_REDIRECT_URI
        );

        $application->setGrantPassword(true);
        $application->setClientId(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_ID);
        $application->setClientSecret(\API_OAuth2_Application_AdobeCCPlugin::CLIENT_SECRET);

        $this->app['manipulator.api-application']->update($application);
    }

    private function generateProxies()
    {
        $process = new Process('php ' . $this->app['root.path']. '/bin/developer orm:generate:proxies');
        $process->setTimeout(300);
        $process->run();
    }
}
