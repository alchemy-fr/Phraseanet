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
        if (http_request::is_command_line()) {
            return;
        }

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
            try {
                $auth = new Session_Authentication_PersistentCookie($this->app, $request->cookies->get('persistent'));
                $this->app->openAccount($auth, $auth->getSessionId());
            } catch (Exception $e) {

            }
        }

        if (!$this->app->isAuthenticated()) {
            switch ($this->_directory) {
                case 'prod':
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
                case 'login':
                    if ($appbox->need_major_upgrade()) {
                        phrasea::redirect("/setup/");
                    }

                    return;
                    break;
                case 'api':
                    return;
                    break;
                case 'include':
                case '':
                    return;
                case 'setup':
                    if ($this->app['phraseanet.appbox']->upgradeavailable()) {
                        return;
                    } else {
                        phrasea::redirect('/login/');
                    }
                    break;
                default:
                    phrasea::redirect('/login/');
                    break;
                case 'lightbox':
                    $this->token_access();
                    if (!$this->app->isAuthenticated()) {
                        phrasea::redirect('/login/?redirect=' . $_SERVER['REQUEST_URI']);
                    }
                    break;
            }
        } elseif ($_SERVER['PHP_SELF'] === '/login/logout/') {
            return;
        }

        $user = $this->app['phraseanet.user'];

        switch ($this->_directory) {
            case 'admin':
            case 'taskmanager':
                if (!$user->ACL()->has_access_to_module('admin')) {
                    phrasea::headers(403);
                }
                break;
            case 'thesaurus2':
                if (!$user->ACL()->has_access_to_module('thesaurus')) {
                    phrasea::headers(403);
                }
                break;
            case 'client':
            case 'prod':
            case 'lightbox':
                $this->token_access();
                break;
            case 'upload':
                if (!$user->ACL()->has_right('addrecord')) {
                    phrasea::headers(403);
                }
                break;
            case 'report':
                if (!$user->ACL()->has_right('report')) {
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
     * If token is present in URL, sign on and redirect
     *
     * @return gatekeeper
     */
    protected function token_access()
    {
        $request = new http_request();
        $parm = $request->get_parms('LOG');

        if (is_null($parm["LOG"])) {
            return $this;
        }

        try {
            if ($this->app->isAuthenticated()) {
                $this->app->closeAccount();
            }
            $auth = new Session_Authentication_Token($this->app, $parm['LOG']);
            $this->app->openAccount($auth);
        } catch (Exception $e) {
            return phrasea::redirect("/login/?error=" . urlencode($e->getMessage()));
        }

        try {
            $datas = random::helloToken($this->app, $parm['LOG']);

            switch ($datas['type']) {
                default:
                    return $this;
                    break;
                case \random::TYPE_FEED_ENTRY:
                    return phrasea::redirect("/lightbox/feeds/entry/" . $datas['datas'] . "/");
                    break;
                case \random::TYPE_VALIDATE:
                case \random::TYPE_VIEW:
                    return phrasea::redirect("/lightbox/validate/" . $datas['datas'] . "/");
                    break;
            }
        } catch (Exception_NotFound $e) {

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
