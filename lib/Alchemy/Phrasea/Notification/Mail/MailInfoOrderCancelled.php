<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoOrderCancelled extends AbstractMail
{
    private $deliverer;
    private $quantity;

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function setDeliverer(\User_Adapter $deliverer)
    {
        $this->deliverer = $deliverer;
    }

    public function subject()
    {
        return _('push::mail:: Refus d\'elements de votre commande');
    }

    public function message()
    {
        return sprintf(
            _('%s a refuse %d elements de votre commande'),
            $this->deliverer->get_display_name(),
            $this->quantity
        );
    }

    public function buttonText()
    {
        return _('See my order');
    }

    public function buttonURL()
    {
        return sprintf('%sprod/', $this->app['phraseanet.registry']->get('GV_ServerName'));
    }
}
