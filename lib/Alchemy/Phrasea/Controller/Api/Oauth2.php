<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Oauth2 implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.oauth2'] = $this;

        $controllers = $app['controllers_factory'];

        /**
         * AUTHORIZE ENDPOINT
         *
         * Authorization endpoint - used to obtain authorization from the
         * resource owner via user-agent redirection.
         */
        $authorize_func = function () use ($app) {
            $request = $app['request'];
            $oauth2Adapter = $app['oauth2-server'];

            $context = new Context(Context::CONTEXT_OAUTH2_NATIVE);
            $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

            //Check for auth params, send error or redirect if not valid
            $params = $oauth2Adapter->getAuthorizationRequestParameters($request);

            $appAuthorized = false;
            $errorMessage = false;

            if (null === $client = $app['repo.api-applications']->findByClientId($params['client_id'])) {
                throw new NotFoundHttpException(sprintf('Application with client id %s could not be found', $params['client_id']));
            }

            $oauth2Adapter->setClient($client);

            $actionAccept = $request->get("action_accept");
            $actionLogin = $request->get("action_login");

            $template = "api/auth/end_user_authorization.html.twig";

            $custom_template = sprintf(
                "%s/config/templates/web/api/auth/end_user_authorization/%s.html.twig"
                , $app['root.path']
                , $client->getId()
            );

            if (file_exists($custom_template)) {
                $template = sprintf(
                    'api/auth/end_user_authorization/%s.html.twig'
                    , $client->getId()
                );
            }

            if (!$app['authentication']->isAuthenticated()) {
                if ($actionLogin !== null) {
                    try {
                        if (null === $usrId = $app['auth.native']->getUsrId($request->get("login"), $request->get("password"), $request)) {
                            $app['session']->getFlashBag()->set('error', $app->trans('login::erreur: Erreur d\'authentification'));

                            return $app->redirectPath('oauth2_authorize');
                        }
                    } catch (RequireCaptchaException $e) {
                        return $app->redirectPath('oauth2_authorize', ['error' => 'captcha']);
                    } catch (AccountLockedException $e) {
                        return $app->redirectPath('oauth2_authorize', ['error' => 'account-locked']);
                    }

                    $app['authentication']->openAccount($app['repo.users']->find($usrId));
                }

                return new Response($app['twig']->render($template, ["auth" => $oauth2Adapter]));
            }

            //check if current client is already authorized by current user
            $clients = $app['repo.api-applications']->findAuthorizedAppsByUser($app['authentication']->getUser());

            foreach ($clients as $authClient) {
                if ($client->getClientId() == $authClient->getClientId()) {
                    $appAuthorized = true;
                    break;
                }
            }

            $account = $oauth2Adapter->updateAccount($app['authentication']->getUser());

            $params['account_id'] = $account->getId();

            if (!$appAuthorized && $actionAccept === null) {
                $params = [
                    "auth"         => $oauth2Adapter,
                    "errorMessage" => $errorMessage,
                ];

                return new Response($app['twig']->render($template, $params));
            } elseif (!$appAuthorized && $actionAccept !== null) {
                $appAuthorized = (Boolean) $actionAccept;

                if ($appAuthorized) {
                    $app['manipulator.api-account']->authorizeAccess($account);
                } else {
                    $app['manipulator.api-account']->revokeAccess($account);
                }
            }

            //if native app show template
            if ($oauth2Adapter->isNativeApp($params['redirect_uri'])) {
                $params = $oauth2Adapter->finishNativeClientAuthorization($appAuthorized, $params);

                return new Response($app['twig']->render("api/auth/native_app_access_token.html.twig", $params));
            }

            $oauth2Adapter->finishClientAuthorization($appAuthorized, $params);

            // As OAuth2 library already outputs response content, we need to send an empty
            // response to avoid breaking silex controller
            return '';
        };

        $controllers->match('/authorize', $authorize_func)
            ->method('GET|POST')
            ->bind('oauth2_authorize');

        /**
         *  TOKEN ENDPOINT
         *  Token endpoint - used to exchange an authorization grant for an access token.
         */
        $controllers->post('/token', function (\Silex\Application $app, Request $request) {
            if ( ! $request->isSecure()) {
                throw new HttpException(400, 'This route requires the use of the https scheme', null, ['content-type' => 'application/json']);
            }

            $app['oauth2-server']->grantAccessToken($request);
            ob_flush();
            flush();

            // As OAuth2 library already outputs response content, we need to send an empty
            // response to avoid breaking silex controller
            return '';
        });

        return $controllers;
    }
}
