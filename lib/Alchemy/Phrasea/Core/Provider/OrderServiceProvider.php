<?php

/*
 * This file is part of alchemy/pipeline-component.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\Event\Subscriber\OrderSubscriber;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Order\ValidationNotifier\MailNotifier;
use Alchemy\Phrasea\Order\ValidationNotifier\WebhookNotifier;
use Alchemy\Phrasea\Order\ValidationNotifierRegistry;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrderServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['events.order_subscriber'] = $app->share(function (Application $app) {
            $notifierRegistry = new ValidationNotifierRegistry();

            $notifierRegistry->registerNotifier(Order::NOTIFY_MAIL, new MailNotifier($app));
            $notifierRegistry->registerNotifier(Order::NOTIFY_WEBHOOK, new WebhookNotifier(
                new LazyLocator($app, 'manipulator.webhook-event')
            ));

            return new OrderSubscriber($app, $notifierRegistry);
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['events.order_subscriber']);
    }
}
