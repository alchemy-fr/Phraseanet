<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Client;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Security\Firewall;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootController extends Controller
{
    /**
     * @return Firewall
     */
    private function getFirewall()
    {
        return $this->app['firewall'];
    }

    /**
     * Gets client main page
     *
     * @param Request $request
     * @return Response
     */
    public function getClientAction(Request $request)
    {
        if (!$this->getAuthenticator()->isAuthenticated() && null !== $request->query->get('nolog')) {
            return $this->app->redirectPath('login_authenticate_as_guest', ['redirect' => 'client']);
        }
        if (null !== $response = $this->getFirewall()->requireAuthentication()) {
            return $response;
        }

        return $this->app->redirect($this->app->path('prod', array('client')));
    }
}
