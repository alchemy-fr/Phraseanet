<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Entities\Basket;

class MailInfoOrderDelivered extends AbstractMail
{
    private $basket;
    private $deliverer;

    public function setBasket(Basket $basket)
    {
        $this->basket = $basket;
    }

    public function setDeliverer(\User_Adapter $deliverer)
    {
        $this->deliverer = $deliverer;
    }

    public function subject()
    {
        return sprintf(
            _('push::mail:: Reception de votre commande %s'), $this->basket->getName()
        );
    }

    public function message()
    {
        return sprintf(
            _('%s vous a delivre votre commande, consultez la en ligne a l\'adresse suivante'),
            $this->deliverer->get_display_name()
        );
    }

    public function buttonText()
    {
        return _('See my order');
    }

    public function buttonURL()
    {
        return sprintf(
            '%slightbox/compare/%s/',
            $this->app['phraseanet.registry']->get('GV_ServerName'),
            $this->basket->getId()
        );
    }
}
