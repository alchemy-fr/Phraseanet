<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core\Event\Listener;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Model\Entities\ApiOauthToken;
use Alchemy\Phrasea\Model\Manipulator\ApiLogManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class OAuthResponseListener
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function __invoke(FilterResponseEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $session = $this->getSession();
        /** @var ApiOauthToken $token */
        $token = $session->get('token');
        $this->getApiLogManipulator()->create($token->getAccount(), $request, $response);
        $this->getApiOAuthTokenManipulator()->setLastUsed($token, new \DateTime());
        $session->set('token', null);
        if (null !== $this->getAuthenticator()->getUser()) {
            $this->getAuthenticator()->closeAccount();
        }

        $dispatcher->removeListener($eventName, $this);
    }

    /**
     * @return ApiLogManipulator
     */
    private function getApiLogManipulator()
    {
        return $this->app['manipulator.api-log'];
    }

    /**
     * @return ApiOauthTokenManipulator
     */
    private function getApiOAuthTokenManipulator()
    {
        return $this->app['manipulator.api-oauth-token'];
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return Authenticator
     */
    private function getAuthenticator()
    {
        return $this->app['authentication'];
    }
}
