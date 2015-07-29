<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\AccountDeletedEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AccountDeletedEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;;
    }

    public function onAccountDeleted(AccountDeletedEvent $event)
    {
        $appbox = $this->app['phraseanet.appbox'];

        \API_Webhook::create($appbox, \API_Webhook::USER_DELETED, array(
            'user_id' => $event->getUserId(),
            'email' => $event->getEmailAddress()
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(PhraseaEvents::ACCOUNT_DELETED => 'onAccountDeleted');
    }

}
