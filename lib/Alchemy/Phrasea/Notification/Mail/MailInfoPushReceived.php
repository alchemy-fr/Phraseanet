<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoPushReceived extends AbstractMailWithLink
{
    private $basket;
    private $pusher;

    public function setBasket(Basket $basket)
    {
        $this->basket = $basket;
    }

    public function setPusher(\User_Adapter $pusher)
    {
        $this->pusher = $pusher;
    }

    public function subject()
    {
        return sprintf(_('Reception of %s'), $this->basket->getName());
    }

    public function message()
    {
        return
            sprintf(_('You just received a push containing %s documents from %s'), $this->pusher->get_display_name())
            . "\n" . $this->message;
    }

    public function buttonText()
    {
        return _('Watch it online');
    }

    public function buttonURL()
    {
        return $this->url;
    }
}
