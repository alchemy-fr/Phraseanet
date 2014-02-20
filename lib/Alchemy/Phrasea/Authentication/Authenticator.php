<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
use Browser;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\Session;
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

    public function getUser()
    {
        return $this->user;
    }

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

        foreach ($this->app['acl']->get($user)->get_granted_sbas() as $databox) {
            \cache_databox::insertClient($this->app, $databox);
        }
        $this->reinitUser();

        return $session;
    }

    private function populateSession(Session $session)
    {
        $user = $session->getUser($this->app);

        $rights = [];
        if ($this->app['acl']->get($user)->has_right('taskmanager')) {
            $rights[] = 'task-manager';
        }

        $this->session->set('usr_id', $user->getId());
        $this->session->set('websockets_rights', $rights);
        $this->session->set('session_id', $session->getId());
    }

    public function refreshAccount(Session $session)
    {
        if (!$this->em->getRepository('Phraseanet:Session')->findOneBy(['id' => $session->getId()])) {
            throw new RuntimeException('Unable to refresh the session, it does not exist anymore');
        }

        if (null === $user = $session->getUser()) {
            throw new RuntimeException('Unable to refresh the session');
        }

        $this->session->clear();
        $this->populateSession($session);

        foreach ($this->app['acl']->get($user)->get_granted_sbas() as $databox) {
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
        if (!$this->session->has('session_id')) {
            throw new RuntimeException('No session to close.');
        }

        if (null !== $session = $this->em->find('Phraseanet:Session', $this->session->get('session_id'))) {
            $this->em->remove($session);
            $this->em->flush();
        }

        $this->session->invalidate();
        $this->reinitUser();

        return $this;
    }

    public function reinitUser()
    {
        if ($this->isAuthenticated()) {
            $this->user = $this->app['manipulator.user']->getRepository()->find($this->session->get('usr_id'));
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
            if (null !== $this->em->find('Phraseanet:Session', $this->session->get('session_id'))) {
                return true;
            }
        }

        $this->session->invalidate();
        $this->reinitUser();

        return false;
    }
}
