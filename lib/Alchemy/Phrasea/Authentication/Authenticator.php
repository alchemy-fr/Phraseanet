<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\User;
use Browser;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Authenticator
{
    private $app;
    private $browser;
    private $session;
    private $em;
    private $user;

    public function __construct(Application $app, Browser $browser, SessionInterface $session, EntityManager $em)
    {
        // design error, circular reference
        $this->app = $app;
        $this->browser = $browser;
        $this->session = $session;
        $this->em = $em;

        $this->reinitUser();
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return $this
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Open user session
     *
     * @param User $user
     *
     * @return Session
     *
     * @throws \Exception_InternalServerError
     */
    public function openAccount(User $user)
    {
        $this->session->remove('usr_id');
        $this->session->remove('session_id');

        $session = new Session();
        $session->setBrowserName($this->browser->getBrowser())
            ->setBrowserVersion($this->browser->getVersion())
            ->setPlatform($this->browser->getPlatform())
            ->setUserAgent($this->browser->getUserAgent())
            ->setUser($user);

        $this->em->persist($session);
        $this->em->flush();

        $this->populateSession($session);

        foreach ($this->app->getAclForUser($user)->get_granted_sbas() as $databox) {
            \cache_databox::insertClient($this->app, $databox);
        }
        $this->reinitUser();

        return $session;
    }

    private function populateSession(Session $session)
    {
        $user = $session->getUser();
        $user->setLastConnection($session->getCreated());
        // reset inactivity email when login
        $user->setNbInactivityEmail(0);
        $user->setLastInactivityEmail(null);

        $this->em->persist($user);
        $this->em->flush();

        $this->session->set('usr_id', $user->getId());
        $this->session->set('session_id', $session->getId());
    }

    public function refreshAccount(Session $session)
    {
        if (!$this->app['repo.sessions']->find($session->getId())) {
            throw new RuntimeException('Unable to refresh the session, it does not exist anymore');
        }

        if (null === $user = $session->getUser()) {
            throw new RuntimeException('Unable to refresh the session');
        }

        $this->session->clear();
        $this->populateSession($session);

        foreach ($this->app->getAclForUser($user)->get_granted_sbas() as $databox) {
            \cache_databox::insertClient($this->app, $databox);
        }

        $this->reinitUser();

        return $session;
    }

    /**
     * Closes user session
     */
    public function closeAccount()
    {
        if ($this->session->has('session_id')) {
            if (null !== $session = $this->app['repo.sessions']->find($this->session->get('session_id'))) {
                $this->em->remove($session);
                $this->em->flush();
            }
        }

        $this->session->invalidate();
        $this->reinitUser();

        return $this;
    }

    public function reinitUser()
    {
        if ($this->isAuthenticated()) {
            $this->user = $this->app['repo.users']->find($this->session->get('usr_id'));
        } else {
            $this->user = null;
        }

        return $this->user;
    }

    /**
     * Tell if current a session is open
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        if (!$this->session->has('usr_id')) {
            return false;
        }

        if ($this->session->has('session_id')) {
            if (null !== $this->app['repo.sessions']->find($this->session->get('session_id'))) {
                return true;
            }
        }

        $this->session->invalidate();
        $this->reinitUser();

        return false;
    }
}
