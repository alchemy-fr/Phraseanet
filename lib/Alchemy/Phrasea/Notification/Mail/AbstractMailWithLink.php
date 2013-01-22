<?php

namespace Alchemy\Phrasea\Notification\Mail;

abstract class AbstractMailWithLink  extends AbstractMail implements MailWithLinkInterface
{
    protected $url;
    protected $expiration;

    public function setButtonUrl($url)
    {
        $this->url = $url;
    }

    public function setExpiration(\DateTime $expiration = null)
    {
        $this->expiration = $expiration;
    }
}
