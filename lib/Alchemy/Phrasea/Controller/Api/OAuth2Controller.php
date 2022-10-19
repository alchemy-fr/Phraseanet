<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Authentication\SuggestionFinder;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OAuth2Controller extends Controller
{
    use DispatcherAware;

    /** @var \API_OAuth2_Adapter */
    private $oAuth2Adapter;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->oAuth2Adapter = $app['oauth2-server'];
    }

    /**
     * AUTHORIZE ENDPOINT
     *
     * Authorization endpoint - used to obtain authorization from the
     * resource owner via user-agent redirection.
     * @param Request $request
     * @return string|Response
     */
    public function authorizeAction(Request $request)
    {
        $context = new Context(Context::CONTEXT_OAUTH2_NATIVE);
        $this->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        //Check for auth params, send error or redirect if not valid
        $params = $this->oAuth2Adapter->getAuthorizationRequestParameters($request);

        $appAuthorized = false;
        $error = $request->get('error', '');

        /** @var ApiApplicationRepository $appRepository */
        $appRepository = $this->app['repo.api-applications'];
        if (null === $client = $appRepository->findByClientId($params['client_id'])) {
            throw new NotFoundHttpException(sprintf('Application with client id %s could not be found', $params['client_id']));
        }

        $this->oAuth2Adapter->setClient($client);

        $actionAccept = $request->get("action_accept");
        $actionLogin = $request->get("action_login");

        $template = "api/auth/end_user_authorization.html.twig";

        $custom_template = sprintf(
            "%s/config/templates/web/api/auth/end_user_authorization/%s.html.twig"
            , $this->app['root.path']
            , $client->getId()
        );

        if (file_exists($custom_template)) {
            $template = sprintf(
                'api/auth/end_user_authorization/%s.html.twig'
                , $client->getId()
            );
        }

        if (!$this->getAuthenticator()->isAuthenticated()) {
            if ($actionLogin !== null) {
                try {
                    /** @var PasswordAuthenticationInterface $authentication */
                    $authentication = $this->app['auth.native'];
                    if (null === $usrId = $authentication->getUsrId($request->get("login"), $request->get("password"), $request)) {
                        $this->getSession()->getFlashBag()
                            ->set('error', $this->app->trans('login::erreur: Erreur d\'authentification'));

                        return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'login'), $params));
                    }
                } catch (RequireCaptchaException $e) {
                    return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'captcha'), $params));
                } catch (AccountLockedException $e) {
                    return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'account-locked'), $params));
                }

                $user = $this->getUserRepository()->find($usrId);
                $this->getAuthenticator()->openAccount($user);
                $event = new PostAuthenticate($request, new Response(), $user, $context);
                $this->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);
            } else {
                $r = new Response($this->render($template, array('error' => $error, "auth" => $this->oAuth2Adapter)));
                $r->headers->set('Content-Type', 'text/html');

                return $r;
            }
        }

        $account = $this->oAuth2Adapter->updateAccount($this->getAuthenticatedUser());

        //check if current client is already authorized by current user
        $clients = $appRepository->findAuthorizedAppsByUser($this->getAuthenticatedUser());

        foreach ($clients as $authClient) {
            if ($client->getClientId() == $authClient->getClientId()) {
                $appAuthorized = true;
                break;
            }
        }

        $params['account_id'] = $account->getId();

        if (!$appAuthorized && $actionAccept === null) {
            $params = [
                "auth"  => $this->oAuth2Adapter,
                "error" => $error,
            ];

            $r = new Response($this->render($template, $params));
            $r->headers->set('Content-Type', 'text/html');

            return $r;
        } elseif (!$appAuthorized && $actionAccept !== null) {
            $appAuthorized = (Boolean) $actionAccept;
            if ($appAuthorized) {
                $this->getApiAccountManipulator()
                    ->authorizeAccess($account);
            } else {
                $this->getApiAccountManipulator()
                    ->revokeAccess($account);
            }
        }

        //if native app show template
        if ($this->oAuth2Adapter->isNativeApp($params['redirect_uri'])) {
            $params = $this->oAuth2Adapter->finishNativeClientAuthorization($appAuthorized, $params);

            $r = new Response($this->render("api/auth/native_app_access_token.html.twig", $params));
            $r->headers->set('Content-Type', 'text/html');

            return $r;
        }

        $this->oAuth2Adapter->finishClientAuthorization($appAuthorized, $params);

        // As OAuth2 library already outputs response content, we need to send an empty
        // response to avoid breaking silex controller
        return '';
    }

    public function authorizeWithProviderAction(Request $request, $providerId)
    {
        $context = new Context(Context::CONTEXT_OAUTH2_NATIVE);
        $this->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        //Check for auth params, send error or redirect if not valid
        $params = $this->oAuth2Adapter->getAuthorizationRequestParameters($request);

        /** @var ApiApplicationRepository $appRepository */
        $appRepository = $this->app['repo.api-applications'];
        if (null === $client = $appRepository->findByClientId($params['client_id'])) {
            throw new NotFoundHttpException(sprintf('Application with client id %s could not be found', $params['client_id']));
        }

        $provider = $this->findProvider($providerId);

        return $provider->authenticate($request->query->all());
    }

    public function authorizeCallbackAction(Request $request, $providerId)
    {
        $context = new Context(Context::CONTEXT_OAUTH2_NATIVE);
        $provider = $this->findProvider($providerId);

        /*
         * some api client (parade) did want to pass parameters into oauth2 callback url
         * but we prevent this for openid
         * The parameters can be passed in session, we restore them
         */
        $customParms = $this->getSession()->get($provider->getId() . '.parms', []);
        if(!is_array($customParms)) {
            $customParms = [];
        }
        $params = $this->oAuth2Adapter->getAuthorizationRequestParameters($request, $customParms);

        // triggers what's necessary
        try {
            $provider->onCallback($request);
            $token = $provider->getToken();
        } catch (NotAuthenticatedException $e) {
            $this->getSession()->getFlashBag()->add('error', $this->app->trans('Unable to authenticate with %provider_name%', ['%provider_name%' => $provider->getName()]));

            return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'login'), $params));
        }

        $userAuthProvider = $this->getUserAuthProviderRepository()
            ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

        if($userAuthProvider == null){
            unset($params['state']);

            return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'login'), $params));
        }

        try {
            $user = $this->getAuthenticationSuggestionFinder()->find($token);
        } catch (NotAuthenticatedException $e) {
            $this->app->addFlash('error', $this->app->trans('Unable to retrieve provider identity'));

            return $this->app->redirectPath('oauth2_authorize', array_merge(array('error' => 'login'), $params));
        }

        $this->getAuthenticator()->openAccount($userAuthProvider->getUser());
        $event = new PostAuthenticate($request, new Response(), $user, $context);
        $this->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        /** @var ApiApplicationRepository $appRepository */
        $appRepository = $this->app['repo.api-applications'];
        if (null === $client = $appRepository->findByClientId($params['client_id'])) {
            throw new NotFoundHttpException(sprintf('Application with client id %s could not be found', $params['client_id']));
        }

        $this->oAuth2Adapter->setClient($client);

        $account = $this->oAuth2Adapter->updateAccount($this->getAuthenticatedUser());

        //check if current client is already authorized by current user
        $clients = $appRepository->findAuthorizedAppsByUser($this->getAuthenticatedUser());
        $appAuthorized = false;

        foreach ($clients as $authClient) {
            if ($client->getClientId() == $authClient->getClientId()) {
                $appAuthorized = true;
                break;
            }
        }

        $params['account_id'] = $account->getId();

        //if native app show template
        if ($this->oAuth2Adapter->isNativeApp($params['redirect_uri'])) {
            $params = $this->oAuth2Adapter->finishNativeClientAuthorization($appAuthorized, $params);

            $r = new Response($this->render("api/auth/native_app_access_token.html.twig", $params));
            $r->headers->set('Content-Type', 'text/html');

            return $r;
        }

        $this->oAuth2Adapter->finishClientAuthorization($appAuthorized, $params);

        // As OAuth2 library already outputs response content, we need to send an empty
        // response to avoid breaking silex controller
        return '';

    }

    /**
     *  TOKEN ENDPOINT
     *  Token endpoint - used to exchange an authorization grant for an access token.
     * @param Request $request
     * @return string
     */
    public function tokenAction(Request $request)
    {
        /** @var PropertyAccess $config */
        $config = $this->app['conf'];

        if ( ! $request->isSecure() && $config->get(['registry', 'api-clients', 'api-require-ssl'], true) == true) {
            throw new HttpException(400, 'This route requires the use of the https scheme: ' . $config->get(['registry', 'api-clients', 'api-require-ssl']), null, ['content-type' => 'application/json']);
        }

        $this->oAuth2Adapter->grantAccessToken();
        ob_flush();
        flush();

        // As OAuth2 library already outputs response content, we need to send an empty
        // response to avoid breaking silex controller
        return '';
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return ApiAccountManipulator
     */
    public function getApiAccountManipulator()
    {
        return $this->app['manipulator.api-account'];
    }

    /**
     * @param  string $providerId
     * @return ProviderInterface
     */
    private function findProvider($providerId)
    {
        try {
            return $this->getAuthenticationProviders()->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }
    }

    /**
     * @return ProvidersCollection
     */
    private function getAuthenticationProviders()
    {
        return $this->app['authentication.providers'];
    }

    /**
     * @return UsrAuthProviderRepository
     */
    private function getUserAuthProviderRepository()
    {
        return $this->app['repo.usr-auth-providers'];
    }

    /**
     * @return SuggestionFinder
     */
    private function getAuthenticationSuggestionFinder()
    {
        return $this->app['authentication.suggestion-finder'];
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }
}
