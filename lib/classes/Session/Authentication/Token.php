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
class Session_Authentication_Token implements Session_Authentication_Interface
{
    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var string
     */
    protected $token;
    protected $user;

    public function __construct(Application $app, $token)
    {
        $this->app = $app;
        $this->token = $token;

        try {
            $datas = random::helloToken($app, $token);
            $usr_id = $datas['usr_id'];
            $this->user = User_Adapter::getInstance($usr_id, $this->app);
        } catch (Exception_NotFound $e) {
            throw new Exception_Session_WrongToken();
        }

        return $this;
    }

    /**
     *
     * @return Session_Authentication_Token
     */
    public function prelog()
    {
        return $this;
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
     * @return User_Adapter
     */
    public function get_user()
    {
        return $this->user;
    }

    /**
     *
     * @return Session_Authentication_Token
     */
    public function postlog()
    {
        return $this;
    }
}
