<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailSuccessAccountInactifDelete extends AbstractMail
{
    /** @var string */
    private $lastConnection;

    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;
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

        return
            $this->app->trans("mail:: account deletion confirmation  hello", [], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail::account deletion confirmation no activities since %lastConnection%", [
                '%lastConnection%'  =>  $this->lastConnection
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
