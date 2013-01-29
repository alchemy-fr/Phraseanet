<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Session_Authentication_PersistentCookie implements Session_Authentication_Interface
{
    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var type
     */
    protected $persistent_cookie;

    /**
     *
     * @var int
     */
    protected $ses_id;

    public function __construct(Application $app, $persistent_cookie)
    {
        $this->app= $app;
        $this->persistent_cookie = $persistent_cookie;

        $dql = 'SELECT s FROM Entities\Session s
            WHERE s.token = :token';

        $query = $app['EM']->createQuery($dql);
        $query->setParameters(array('token'  => $persistent_cookie));
        $session = $query->getOneOrNullResult();

        if (! $session) {
            throw new \Exception_Session_WrongToken('Persistent cookie value does not have any valid session');
        }

        $string = $app['browser']->getBrowser() . '_' . $app['browser']->getPlatform();

        if (\User_Adapter::salt_password($this->app, $string, $session->getNonce()) !== $session->getToken()) {
            throw new \Exception_Session_WrongToken('Persistent cookie value is corrupted');
        }

        $this->user = $session->getUser($app);
        $this->ses_id = $session->getId();

        return $this;
    }

    public function prelog()
    {
        return $this;
    }

    /**
     *
     * @return User_Adapter
     */
    public function get_user()
    {
        return $this->user;
    }

    /**
     *
     * @return int
     */
    public function getSessionId()
    {
        return $this->ses_id;
    }

    /**
     *
     * @return User_Adapter
     */
    public function signOn()
    {
        return $this->user;
    }

    /**
     *
     * @return Session_Authentication_PersistentCookie
     */
    public function postlog()
    {
        return $this;
    }
}
