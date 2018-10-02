<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\PersistentCookie;

use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Model\Entities\Session;
use Doctrine\ORM\EntityRepository;

class Manager
{
    private $browser;
    private $encoder;
    private $repository;

    public function __construct(PasswordEncoder $encoder, EntityRepository $repo, \Browser $browser)
    {
        $this->browser = $browser;
        $this->encoder = $encoder;
        $this->repository = $repo;
    }

    /**
     * Returns a Session give a cookie value
     *
     * @param string $cookieValue
     *
     * @return false|Session
     */
    public function getSession($cookieValue)
    {
        $session = $this->repository->findOneBy(['token' => $cookieValue]);

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
