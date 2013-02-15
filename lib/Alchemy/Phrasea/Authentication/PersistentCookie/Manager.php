<?php

namespace Alchemy\Phrasea\Authentication\PersistentCookie;

use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Doctrine\ORM\EntityManager;

class Manager
{
    private $browser;
    private $encoder;
    private $em;

    public function __construct(PasswordEncoder $encoder, EntityManager $em, \Browser $browser)
    {
        $this->browser = $browser;
        $this->encoder = $encoder;
        $this->em = $em;
    }

    public function getSession($cookieValue)
    {
        $session = $this->em
            ->getRepository('Entities\Session')
            ->findOneBy(array('token' => $cookieValue));

        if (!$session) {
            return false;
        }

        $string = sprintf('%s_%s', $this->browser->getBrowser(), $this->browser->getPlatform());
        if (!$this->encoder->isPasswordValid($session->getToken(), $string, $session->getNonce())) {
            return false;
        }

        return $session;
    }
}
