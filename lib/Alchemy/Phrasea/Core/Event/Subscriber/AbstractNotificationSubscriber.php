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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractNotificationSubscriber implements EventSubscriberInterface
{
    use NotifierAware;

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));
    }

    protected function shouldSendNotificationFor(User $user, $type)
    {
        return $this->app['settings']->getUserNotificationSetting($user, $type);
    }
}
