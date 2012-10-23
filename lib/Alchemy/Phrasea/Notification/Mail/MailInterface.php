<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;

interface MailInterface
{
    /**
     * @return Emitter
     */
    public function emitter();
    /**
     * @return Receiver
     */
    public function receiver();
    public function subject();

    public function message();

    public function renderHTML();
}
