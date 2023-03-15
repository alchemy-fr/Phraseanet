<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

use Alchemy\Phrasea\Notification\Mail\MailInterface;
use Alchemy\Phrasea\Exception\LogicException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Deliverer
{
    /** @var \Swift_Mailer */
    private $mailer;

    /** @var EmitterInterface */
    private $emitter;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var string */
    private $prefix;

    public function __construct(\Swift_Mailer $mailer, EventDispatcherInterface $dispatcher, EmitterInterface $emitter, $prefix = '')
    {
        $this->mailer = $mailer;
        $this->emitter = $emitter;
        $this->dispatcher = $dispatcher;
        $this->prefix = $prefix ? sprintf('%s ', $prefix) : '';
    }

    /**
     * Delivers an email
     *
     * @param  MailInterface $mail
     * @param  Boolean       $readReceipt
     * @return int           the number of messages that have been sent
     *
     * @throws LogicException In case no Receiver provided
     * @throws LogicException In case a read-receipt is asked but no Emitter provided
     */
    public function deliver(MailInterface $mail, $readReceipt = false, array $attachments = null)
    {
        if (!$mail->getReceiver()) {
            throw new LogicException('You must provide a receiver for a mail notification');
        }

        $message = \Swift_Message::newInstance($this->prefix . $mail->getSubject(), $mail->renderHTML(), 'text/html', 'utf-8');
        $message->addPart($mail->getMessage(), 'text/plain', 'utf-8');

        $message->setFrom($this->emitter->getEmail(), $this->emitter->getName());
        $message->setTo($mail->getReceiver()->getEmail(), $mail->getReceiver()->getName());

        if ($mail->getEmitter()) {
            $message->setReplyTo($mail->getEmitter()->getEmail(), $mail->getEmitter()->getName());
        }

        if(is_array($attachments)) {
            foreach($attachments as $attachment) {
                $message->attach($attachment->As_Swift_Attachment());
            }
        }

        if ($readReceipt) {
            if (!$mail->getEmitter()) {
                throw new LogicException('You must provide an emitter for a ReadReceipt');
            }
            $message->setReadReceiptTo([$mail->getEmitter()->getEmail() => $mail->getEmitter()->getName()]);
        }

        if(!$this->mailer->getTransport()->isStarted()) {
            $this->mailer->getTransport()->start();
        }
        $ret = $this->mailer->send($message);
        $this->mailer->getTransport()->stop();

        $this->dispatcher->dispatch('phraseanet.notification.sent');

        return $ret;
    }

    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }
}
