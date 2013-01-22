<?php

namespace Alchemy\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Mail\MailInterface;

class Deliverer
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     *
     * @var \registry
     */
    private $registry;

    public function __construct(\Swift_Mailer $mailer, \registry $registry)
    {
        $this->mailer = $mailer;
        $this->registry = $registry;
    }

    public function deliver(MailInterface $mail, $readReceipt = false)
    {
        if (!$mail->receiver()) {
            throw new \LogicException('You should provide a receiver for a mail notification');
        }

        $prefix = $this->registry->get('GV_mail_prefix') ? ($this->registry->get('GV_mail_prefix') . ' ') : null;

        $message = \Swift_Message::newInstance($prefix . $mail->subject(), $mail->renderHTML(), 'text/html', 'utf-8');
        $message->addPart($mail->message(), 'text/plain', 'utf-8');

        $message->setFrom($this->registry->get('GV_defaulmailsenderaddr', 'no-reply@phraseanet.com'), $this->registry->get('GV_homeTitle', 'Phraseanet'));
        $message->setTo($mail->receiver()->email(), $mail->receiver()->name());

        if ($mail->emitter()) {
            $message->setReplyTo($mail->emitter()->email(), $mail->emitter()->name());
        }

        if ($readReceipt) {
            $message->setReadReceiptTo($readReceipt);
        }

        $this->mailer->send($message);
    }
}
