<?php

namespace Alchemy\Phrasea\Notification\Mail;

interface MailWithLinkInterface
{
    public function setButtonUrl($url);
    public function setExpiration(\DateTime $expiration);
}
