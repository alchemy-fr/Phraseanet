<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class Controller
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
    
    /**
     * @return \appbox
     */
    public function getApplicationBox()
    {
        return $this->app['phraseanet.appbox'];
    }

    /**
     * @param int $id
     * @return \databox
     */
    public function findDataboxById($id)
    {
        $appbox = $this->getApplicationBox();

        return $appbox->get_databox($id);
    }

    /**
     * @param string $name
     * @param array  $context
     * @return string
     */
    public function render($name, array $context = [])
    {
        /** @var \Twig_Environment $twig */
        $twig = $this->app['twig'];
        return $twig->render(
            $name,
            $context
        );
    }

    /**
     * @param string $name
     * @param array  $context
     * @param int    $status
     * @param array  $headers
     * @return Response
     */
    public function renderResponse($name, array $context = [], $status = 200, array $headers = [])
    {
        return new Response($this->render($name, $context), $status, $headers);
    }

    /**
     * @return ACLProvider
     */
    public function getAclProvider()
    {
        return $this->app['acl'];
    }

    /**
     * @return Authenticator
     */
    public function getAuthenticator()
    {
        return $this->app['authentication'];
    }

    /**
     * @param User|null $user
     * @return \ACL
     */
    public function getAclForUser(User $user = null)
    {
        $aclProvider = $this->getAclProvider();

        if (null === $user) {
            $user = $this->getAuthenticatedUser();
        }

        return $aclProvider->get($user);
    }

    /**
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        return $this->getAuthenticator()->getUser();
    }

    public function setSessionFormToken($formName)
    {
        $randomValue = bin2hex(random_bytes(35));
        $this->app['session']->set($formName.'_token', $randomValue);

        return $randomValue;
    }

    public function getSessionFormToken($formName)
    {
        return $this->app['session']->get($formName.'_token');
    }

    public function isCrsfValid(Request $request, $formName)
    {
        if (!$request->isMethod("POST")) {
            return false;
        }

        $formTokenName = $formName . '_token';
        $formToken = (string) $request->request->get($formTokenName);

        if (empty($formToken) || $formToken != $this->getSessionFormToken($formName)) {
            return false;
        }

        return true;
    }

    /**
     * @return PropertyAccess
     */
    protected function getConf()
    {
        return $this->app['conf'];
    }
}
