<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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

    /**
     *
     * @param  Application                                  $appbox
     * @param  type                                    $persistent_cookie
     * @return Session_Authentication_PersistentCookie
     */
    public function __construct(Application $app, $persistent_cookie)
    {
        $this->app= $app;
        $this->persistent_cookie = $persistent_cookie;

        $browser = Browser::getInstance();

        $conn = $this->app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT usr_id, session_id, nonce, token FROM cache WHERE token = :token';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':token' => $this->persistent_cookie));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row || count($row) == 0) {
            throw new Exception_Session_WrongToken();
        }

        $string = $browser->getBrowser() . '_' . $browser->getPlatform();

        if (User_Adapter::salt_password($this->app, $string, $row['nonce']) !== $row['token']) {
            throw new Exception_Session_WrongToken();
        }

        $this->user = User_Adapter::getInstance($row['usr_id'], $this->app);
        $this->ses_id = (int) $row['session_id'];

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
