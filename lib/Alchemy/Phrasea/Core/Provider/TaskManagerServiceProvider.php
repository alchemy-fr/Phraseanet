<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Monolog\Handler\SyslogHandler;
use Monolog\Handler\NativeMailerHandler;
use Alchemy\Phrasea\Exception\RuntimeException;

class TaskManagerServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['task-manager'] = $app->share(function(Application $app) {

            $logger = clone $app['monolog'];

            $options = $app['phraseanet.configuration']['main']['task-manager']['options'];

            if (isset($options['syslog_level']) && null !== $syslogLevel = constant($options['syslog_level'])) {
                $handler = new SyslogHandler("Phraseanet-Task", "user", $syslogLevel);
                $logger->pushHandler($handler);
            }

            if (isset($options['maillog_level']) && null !== $maillogLevel = constant($options['maillog_level'])) {
                if ('' === $adminMail = trim($app['phraseanet.registry']->get('GV_adminMail'))) {
                    throw new RuntimeException("Admininstrator mail must be set to get log by mail.");
                }
                $senderMail = $app['phraseanet.registry']->get('GV_defaultmailsenderaddr');

                $handler = new NativeMailerHandler($adminMail, "Phraseanet-Task", $senderMail, $maillogLevel);
                $logger->pushHandler($handler);
            }

            return new \task_manager($app, $logger);
        });
    }

    public function boot(Application $app)
    {
    }
}
