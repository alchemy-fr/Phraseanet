<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailSuccessAccountInactifDelete extends AbstractMail
{
    /** @var string */
    private $lastConnection;

    /** @var string */
    private $lastInactivityEmail;

    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;
    }

    public function setLastInactivityEmail($lastInactivityEmail)
    {
        $this->lastInactivityEmail = $lastInactivityEmail;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        return $this->app->trans('mail:: account deletion confirmation object', [], 'messages', $this->getLocale());
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        if (!$this->lastConnection) {
            throw new LogicException('You must set a lastConnection before calling getMessage');
        }

        if (!$this->lastInactivityEmail) {
            throw new LogicException('You must set a lastInactivityEmail before calling getMessage');
        }

        return
            $this->app->trans("mail:: account deletion confirmation  hello", [], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail::account deletion confirmation no activities since %lastConnection%", [
                '%lastConnection%'  =>  $this->lastConnection
            ], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail::account deletion last relance at  %lastInactivityEmail%", [
                '%lastInactivityEmail%'  =>  $this->lastInactivityEmail
            ], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail::account deletion confirmation account deleted", [], 'messages', $this->getLocale())
        ;
    }

    /**
     * @inheritDoc
     */
    public function getButtonText()
    {
    }

    /**
     * @inheritDoc
     */
    public function getButtonURL()
    {
    }
}
