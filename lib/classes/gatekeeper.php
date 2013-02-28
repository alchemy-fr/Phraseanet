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
use Symfony\Component\HttpFoundation\Request;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class gatekeeper
{
    /**
     *
     * @var string
     */
    protected $_directory;

    /**
     *
     * @var string
     */
    protected $_script_name;

    /**
     *
     * @var string
     */
    protected $_PHP_SELF;

    /**
     *
     * @var gatekeeper
     */
    protected static $_instance;
    protected $app;

    /**
     *
     * @return gatekeeper
     */
    public static function getInstance(Application $app)
    {
        if (!(self::$_instance instanceof self))
            self::$_instance = new self($app);

        return self::$_instance;
    }

    /**
     *
     * @return gatekeeper
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Redirect to the correct guest location
     *
     * @return gatekeeper
     */
    protected function give_guest_access()
    {
        $request = http_request::getInstance();

        $parm = $request->get_parms('nolog', 'redirect');

        if (!is_null($parm['nolog']) && phrasea::guest_allowed($this->app)) {
            try {
                $auth = new Session_Authentication_Guest($this->app);
                $this->app->openAccount($auth);
            } catch (Exception $e) {
                $url = '/login/?redirect=' . $parm['redirect']
                    . '&error=' . urlencode($e->getMessage());
                phrasea::redirect($url);
            }
            phrasea::redirect('/' . $this->_directory . '/');
        }

        return $this;
    }

    /**
     * Checks if session is open
     * Redirect if session is missing
     *
     * @return Void
     */
    public function require_session()
    {
        if ($this->app->isAuthenticated()) {
            return true;
        }
        phrasea::headers(403);

        return;
    }
}
