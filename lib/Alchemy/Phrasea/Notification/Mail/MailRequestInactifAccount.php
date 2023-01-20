<?php

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailRequestInactifAccount extends AbstractMail
{

    private $login;
    /** @var string */
    private $lastConnection;
    /** @var string */
    private $deleteDate;

    public function setLogin($login)
    {
        $this->login = $login;
    }

    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;
    }

    public function setDeleteDate($deleteDate)
    {
        $this->deleteDate = $deleteDate;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        return $this->app->trans("mail:: inactif account", [], 'messages', $this->getLocale());
    }

    /**
     * @inheritDoc
     */
    public function getMessage()
    {
        if (!$this->login) {
            throw new LogicException('You must set a login before calling getMessage');
        }

        if (!$this->lastConnection) {
            throw new LogicException('You must set a lastConnection before calling getMessage');
        }

        if (!$this->deleteDate) {
            throw new LogicException('You must set a deleteDate before calling getMessage');
        }

        return
            $this->app->trans("mail:: inactif account hello", [], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail:: inactif account info with login")
            . "\n\n <strong>" . $this->login . "</strong> \n\n"
            . $this->app->trans("mail:: inactif account on application %application% is inactif since %lastConnection%", [
                '%application%' => '<a href="'.$this->getPhraseanetURL().'" target="_blank">'.$this->getPhraseanetTitle().'</a>',
                '%lastConnection%'  =>  $this->lastConnection,
            ], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail:: inactif account keep account info , connect before %deleteDate%", [
                '%deleteDate%' => $this->deleteDate
            ], 'messages', $this->getLocale())
            . "\n" .
            $this->app->trans("mail:: inactif account delete account info , account will be deleted on %deleteDate%", [
                '%deleteDate%' => $this->deleteDate
            ], 'messages', $this->getLocale())

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
