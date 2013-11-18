<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Browser;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function setUser(\User_Adapter $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Open user session
     *
     * @param \User_Adapter $user
     *
     * @return Session
     *
     * @throws \Exception_InternalServerError
     */
    public function openAccount(\User_Adapter $user)
    {
        $this->session->remove('usr_id');
        $this->session->remove('session_id');

        $this->session->set('usr_id', $user->get_id());

        $session = new Session();
        $session->setBrowserName($this->browser->getBrowser())
            ->setBrowserVersion($this->browser->getVersion())
            ->setPlatform($this->browser->getPlatform())
            ->setUserAgent($this->browser->getUserAgent())
            ->setUsrId($user->get_id());

        $this->em->persist($session);
        $this->em->flush();

        $this->session->set('session_id', $session->getId());

        foreach ($this->app['acl']->get($user)->get_granted_sbas() as $databox) {
            \cache_databox::insertClient($this->app, $databox);
        }
        $this->reinitUser();

        return $session;
    }

    public function refreshAccount(Session $session)
    {
        if (!$this->em->getRepository('Alchemy\Phrasea\Model\Entities\Session')->findOneBy(['id' => $session->getId()])) {
            throw new RuntimeException('Unable to refresh the session, it does not exist anymore');
        }

        try {
            $user = \User_Adapter::getInstance($session->getUsrId(), $this->app);
        } catch (NotFoundHttpException $e) {
            throw new RuntimeException('Unable to refresh the session', $e->getCode(), $e);
        }

        $this->session->clear();
        $this->session->set('usr_id', $session->getUsrId());
        $this->session->set('session_id', $session->getId());

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

        if (null !== $session = $this->em->find('Alchemy\Phrasea\Model\Entities\Session', $this->session->get('session_id'))) {
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
            $this->user = \User_Adapter::getInstance($this->session->get('usr_id'), $this->app);
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
            if (null !== $this->em->find('Alchemy\Phrasea\Model\Entities\Session', $this->session->get('session_id'))) {
                return true;
            }
        }

        $this->session->invalidate();
        $this->reinitUser();

        return false;
    }
}
