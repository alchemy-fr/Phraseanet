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
     * Check the current sub_directory on the domain name
     * Redirect if access is denied
     *
     * @return Void
     */
    public function check_directory(Request $request)
    {
        if (isset($_SERVER['PHP_SELF']) && trim($_SERVER['PHP_SELF'])) {
            $this->_PHP_SELF = $_SERVER['PHP_SELF'];

            $php_script = explode('/', $_SERVER['PHP_SELF']);

            if (trim($php_script[0]) == 0)
                array_shift($php_script);

            if (count($php_script) > 1)
                $this->_directory = $php_script[0];
            else
                $this->_directory = '';
            $this->_script_name = array_pop($php_script);
        }

        if (!$this->app->isAuthenticated()) {
            switch ($this->_directory) {
                case 'client':
                    $this->give_guest_access();
                    if ($request->isXmlHttpRequest()) {
                        phrasea::headers(404);
                    } else {
                        phrasea::redirect('/login/?redirect=' . $_SERVER['REQUEST_URI']);
                    }
                    break;
                case 'thesaurus2':
                    if ($this->_PHP_SELF == '/thesaurus2/xmlhttp/getterm.x.php'
                        || $this->_PHP_SELF == '/thesaurus2/xmlhttp/searchcandidate.x.php'
                        || $this->_PHP_SELF == '/thesaurus2/xmlhttp/getsy.x.php') {
                        return;
                    }
                    phrasea::redirect('/login/?redirect=/thesaurus2');
                    break;
                case 'report':
                    phrasea::redirect('/login/?redirect=' . $_SERVER['REQUEST_URI']);
                    break;
            }
        } elseif ($_SERVER['PHP_SELF'] === '/login/logout/') {
            return;
        }

        switch ($this->_directory) {
            case 'thesaurus2':
                if (!$this->app['phraseanet.user']->ACL()->has_access_to_module('thesaurus')) {
                    phrasea::headers(403);
                }
                break;
            case 'report':
                if (!$this->app['phraseanet.user']->ACL()->has_right('report')) {
                    phrasea::headers(403);
                }
                break;
            default:
                break;
        }

        return;
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
