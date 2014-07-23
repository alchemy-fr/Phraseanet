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

class Oauth2 implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['oauth'] = $app->share(function ($app) {
            return new \API_OAuth2_Adapter($app);
        });

        /**
         * AUTHORIZE ENDPOINT
         *
         * Authorization endpoint - used to obtain authorization from the
         * resource owner via user-agent redirection.
         */
        $authorize_func = function () use ($app) {
            $request = $app['request'];
            $oauth2_adapter = $app['oauth'];

            $context = new Context(Context::CONTEXT_OAUTH2_NATIVE);
            $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

            //Check for auth params, send error or redirect if not valid
            $params = $oauth2_adapter->getAuthorizationRequestParameters($request);

            $app_authorized = false;
            $error = $request->get('error', '');

            $client = \API_OAuth2_Application::load_from_client_id($app, $params['client_id']);

            $oauth2_adapter->setClient($client);

            $action_accept = $request->get("action_accept");
            $action_login = $request->get("action_login");

            $template = "api/auth/end_user_authorization.html.twig";

            $custom_template = sprintf(
                "%s/config/templates/web/api/auth/end_user_authorization/%s.html.twig"
                , $app['root.path']
                , $client->get_id()
            );

            if (file_exists($custom_template)) {
                $template = sprintf(
                    'api/auth/end_user_authorization/%s.html.twig'
                    , $client->get_id()
                );
            }

            if (!$app['authentication']->isAuthenticated()) {
                if ($action_login !== null) {
                    try {
                        $usr_id = $app['auth.native']->getUsrId($request->get("login"), $request->get("password"), $request);

                        if (null === $usr_id) {

                            return $app->redirectPath('oauth2_authorize', array_merge(array('error' => 'login'), $params));
                        }
                    } catch (RequireCaptchaException $e) {
                        return $app->redirectPath('oauth2_authorize', array_merge(array('error' => 'captcha'), $params));
                    } catch (AccountLockedException $e) {
                        return $app->redirectPath('oauth2_authorize', array_merge(array('error' => 'account-locked'), $params));
                    }

                    $app['authentication']->openAccount(\User_Adapter::getInstance($usr_id, $app));
                } else {
                    return new Response($app['twig']->render($template, array('error' => $error, "auth" => $oauth2_adapter)));
                }
            }

            //check if current client is already authorized by current user
            $user_auth_clients = \API_OAuth2_Application::load_authorized_app_by_user(
                    $app, $app['authentication']->getUser()
            );

            foreach ($user_auth_clients as $auth_client) {
                if ($client->get_client_id() == $auth_client->get_client_id()) {
                    $app_authorized = true;
                }
            }

            $account = $oauth2_adapter->updateAccount($app['authentication']->getUser()->get_id());

            $params['account_id'] = $account->get_id();

            if (!$app_authorized && $action_accept === null) {
                $params = array(
                    "auth"         => $oauth2_adapter,
                    "error"        => $error,
                );

                return new Response($app['twig']->render($template, $params));
            } elseif (!$app_authorized && $action_accept !== null) {
                $app_authorized = (Boolean) $action_accept;
                $account->set_revoked(!$app_authorized);
            }

            //if native app show template
            if ($oauth2_adapter->isNativeApp($params['redirect_uri'])) {
                $params = $oauth2_adapter->finishNativeClientAuthorization($app_authorized, $params);

                return new Response($app['twig']->render("api/auth/native_app_access_token.html.twig", $params));
            }

            $oauth2_adapter->finishClientAuthorization($app_authorized, $params);

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
                throw new HttpException(400, 'This route requires the use of the https scheme', null, array('content-type' => 'application/json'));
            }

            $app['oauth']->grantAccessToken($request);
            ob_flush();
            flush();

            // As OAuth2 library already outputs response content, we need to send an empty
            // response to avoid breaking silex controller
            return '';
        });

        return $controllers;
    }
}
