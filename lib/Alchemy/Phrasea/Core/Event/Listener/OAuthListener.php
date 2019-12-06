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
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\ControllerProvider\Api\V1;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\ControllerProvider\Api\V3;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\ApiOAuth2EndEvent;
use Alchemy\Phrasea\Core\Event\ApiOAuth2StartEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\ApiOauthToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class OAuthListener
{
    /** @var array */
    private $verifyOptions = [
        'scope'            => null,
        'exit_not_present' => true,
        'exit_invalid'     => true,
        'exit_expired'     => true,
        'exit_scope'       => true,
        'realm'            => null,
    ];

    public function __construct(array $options = [])
    {
        if ($options) {
            $this->setVerifyOptions($options);
        }
    }

    public function __invoke(Request $request, Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];

        $context = new Context(Context::CONTEXT_OAUTH2_TOKEN);
        $dispatcher->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));
        $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_START, new ApiOAuth2StartEvent());

        /** @var \API_OAuth2_Adapter $oauth2 */
        $oauth2 = $app['oauth2-server'];

        if (false === $this->verifyAccessToken($oauth2)) {
            $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());

            return null;
        }

        $token = $app['token'];

        if (!$token instanceof ApiOauthToken) {
            throw new NotFoundHttpException('Provided token is not valid.');
        }

        $this->getSession($app)->set('token', $token);

        $oAuth2Account = $token->getAccount();
        // Sets the Api Version
        
        $CalledController = $request->attributes->get('_controller');
        if (mb_strpos($CalledController, 'controller.api.v1') !== FALSE) {
            $request->attributes->set('api_version', V1::VERSION);
        } elseif(mb_strpos($CalledController, 'controller.api.v2') !== FALSE) {
            $request->attributes->set('api_version', V2::VERSION);
        } elseif(mb_strpos($CalledController, 'controller.api.v3') !== FALSE) {
            $request->attributes->set('api_version', V3::VERSION);
        } else {
            $request->attributes->set('api_version', $oAuth2Account->getApiVersion());
        }

        $oAuth2App = $oAuth2Account->getApplication();

        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        if ($oAuth2App->getClientId() == \API_OAuth2_Application_Navigator::CLIENT_ID
            && !$conf->get(['registry', 'api-clients', 'navigator-enabled'])
        ) {
            return Result::createError($request, 403, 'The use of Phraseanet Navigator is not allowed')->createResponse();
        }

        if ($oAuth2App->getClientId() == \API_OAuth2_Application_OfficePlugin::CLIENT_ID
            && !$conf->get(['registry', 'api-clients', 'office-enabled'])
        ) {
            return Result::createError($request, 403, 'The use of Office Plugin is not allowed.')->createResponse();
        }

        if ($oAuth2App->getClientId() == \API_OAuth2_Application_AdobeCCPlugin::CLIENT_ID
            && !$conf->get(['registry', 'api-clients', 'adobe_cc-enabled'])
        ) {
            return Result::createError($request, 403, 'The use of AdobeCC Plugin is not allowed.')->createResponse();
        }

        $authentication = $this->getAuthenticator($app);

        if ($authentication->isAuthenticated()) {
            $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());
            $this->registerClosingAccountCallback($dispatcher, $app);

            return null;
        }

        $authentication->openAccount($oAuth2Account->getUser());
        $oauth2->rememberSession($app['session']);
        $dispatcher->dispatch(PhraseaEvents::API_OAUTH2_END, new ApiOAuth2EndEvent());
        $this->registerClosingAccountCallback($dispatcher, $app);

        return null;
    }

    /**
     * @param \OAuth2 $oauth2
     * @return bool
     */
    private function verifyAccessToken(\OAuth2 $oauth2)
    {
        return $oauth2->verifyAccessToken(
            $this->verifyOptions['scope'],
            $this->verifyOptions['exit_not_present'],
            $this->verifyOptions['exit_invalid'],
            $this->verifyOptions['exit_expired'],
            $this->verifyOptions['exit_scope'],
            $this->verifyOptions['realm']
        );
    }

    public function setVerifyOptions(array $options)
    {
        $this->verifyOptions = array_merge($this->verifyOptions, array_intersect_key($options, $this->verifyOptions));
    }

    public function getVerifyOptions()
    {
        return $this->verifyOptions;
    }

    private function registerClosingAccountCallback(EventDispatcherInterface $dispatcher, Application $app)
    {
        $dispatcher->addListener(KernelEvents::RESPONSE, new OAuthResponseListener($app), -20);
    }


    /**
     * @param Application $app
     * @return Session
     */
    private function getSession(Application $app)
    {
        return $app['session'];
    }

    /**
     * @param Application $app
     * @return Authenticator
     */
    private function getAuthenticator(Application $app)
    {
        return $app['authentication'];
    }
}
