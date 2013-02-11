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
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Session Authentication Object for guest access
 *
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Session_Authentication_Guest implements Session_Authentication_Interface
{
    /**
     *
     * @var Application
     */
    protected $app;

    /**
     *
     * @var User_Adapter
     */
    protected $user;

    /**
     *
     * @param  Application                  $app
     * @return Session_Authentication_Guest
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $nonce = random::generatePassword(16);
        $password = random::generatePassword(24);
        $this->user = User_Adapter::create($this->app, 'invite', $password, null, false, true);

        return $this;
    }

    /**
     *
     * @return Session_Authentication_Guest
     */
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
     * @return User_Adapter
     */
    public function signOn()
    {
        $inviteUsrid = User_Adapter::get_usr_id_from_login($this->app, 'invite');
        $invite_user = User_Adapter::getInstance($inviteUsrid, $this->app);

        $usr_base_ids = array_keys($this->user->ACL()->get_granted_base());
        $this->user->ACL()->revoke_access_from_bases($usr_base_ids);

        $invite_base_ids = array_keys($invite_user->ACL()->get_granted_base());
        $this->user->ACL()->apply_model($invite_user, $invite_base_ids);

        return $this->user;
    }

    /**
     *
     * @return Session_Authentication_Guest
     */
    public function postlog()
    {
        $this->app['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'addInviteCookie'), -128);

        return $this;
    }

    public function addInviteCookie(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->setCookie(new Cookie('invite-usr-id', $this->user->get_id()));

        $event->setResponse($response);
    }
}
