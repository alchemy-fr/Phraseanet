<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Developers implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.account.developers'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/applications/', 'controller.account.developers:listApps')
            ->bind('developers_applications');

        $controllers->get('/application/new/', 'controller.account.developers:displayFormApp')
            ->bind('developers_application_new');

        $controllers->post('/application/', 'controller.account.developers:newApp')
            ->bind('submit_developers_application');

        $controllers->get('/application/{id}/', 'controller.account.developers:getApp')
            ->assert('id', '\d+')
            ->bind('developers_application');

        $controllers->delete('/application/{id}/', 'controller.account.developers:deleteApp')
            ->assert('id', '\d+')
            ->bind('delete_developers_application');

        $controllers->post('/application/{id}/authorize_grant_password/', 'controller.account.developers:authorizeGrantpassword')
            ->assert('id', '\d+')
            ->bind('submit_developers_application_authorize_grant_password');

        $controllers->post('/application/{id}/access_token/', 'controller.account.developers:renewAccessToken')
            ->assert('id', '\d+')
            ->bind('submit_developers_application_token');

        $controllers->post('/application/{id}/callback/', 'controller.account.developers:renewAppCallback')
            ->assert('id', '\d+')
            ->bind('submit_application_callback');

        return $controllers;
    }

    /**
     * Delete application
     *
     * @param  Application  $app     A Silex application where the controller is mounted on
     * @param  Request      $request The current request
     * @param  integer      $id      The application id
     * @return JsonResponse
     */
    public function deleteApp(Application $app, Request $request, $id)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $error = false;

        try {
            $clientApp = new \API_OAuth2_Application($app, $id);
            $clientApp->delete();
        } catch (NotFoundHttpException $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Change application callback
     *
     * @param  Application  $app     A Silex application where the controller is mounted on
     * @param  Request      $request The current request
     * @param  integer      $id      The application id
     * @return JsonResponse
     */
    public function renewAppCallback(Application $app, Request $request, $id)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $error = false;

        try {
            $clientApp = new \API_OAuth2_Application($app, $id);

            if (null !== $request->request->get("callback")) {
                $clientApp->set_redirect_uri($request->request->get("callback"));
            } else {
                $error = true;
            }
        } catch (NotFoundHttpException $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Authorize application to use a grant password type
     *
     * @param  Application  $app     A Silex application where the controller is mounted on
     * @param  Request      $request The current request
     * @param  integer      $id      The application id
     * @return JsonResponse
     */
    public function renewAccessToken(Application $app, Request $request, $id)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $error = false;
        $accessToken = null;

        try {
            $clientApp = new \API_OAuth2_Application($app, $id);
            $account = $clientApp->get_user_account($app['authentication']->getUser());

            $token = $account->get_token();

            if ($token instanceof \API_OAuth2_Token) {
                $token->renew();
            } else {
                $token = \API_OAuth2_Token::create($app['phraseanet.appbox'], $account);
            }

            $accessToken = $token->get_value();
        } catch (\Exception $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error, 'token'   => $accessToken));
    }

    /**
     * Authorize application to use a grant password type
     *
     * @param  Application  $app     A Silex application where the controller is mounted on
     * @param  Request      $request The current request
     * @param  integer      $id      The application id
     * @return JsonResponse
     */
    public function authorizeGrantpassword(Application $app, Request $request, $id)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $error = false;

        try {
            $clientApp = new \API_OAuth2_Application($app, $id);
            $clientApp->set_grant_password((bool) $request->request->get('grant', false));
        } catch (NotFoundHttpException $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Create a new developer applications
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function newApp(Application $app, Request $request)
    {
        if ($request->request->get('type') === \API_OAuth2_Application::DESKTOP_TYPE) {
            $form = new \API_OAuth2_Form_DevAppDesktop($app['request']);
        } else {
            $form = new \API_OAuth2_Form_DevAppInternet($app['request']);
        }

        $violations = $app['validator']->validate($form);

        if ($violations->count() === 0) {
            $application = \API_OAuth2_Application::create($app, $app['authentication']->getUser(), $form->getName());
            $application
                ->set_description($form->getDescription())
                ->set_redirect_uri($form->getSchemeCallback() . $form->getCallback())
                ->set_type($form->getType())
                ->set_website($form->getSchemeWebsite() . $form->getWebsite());

            return $app->redirectPath('developers_application', array('id' => $application->get_id()));
        }

        $var = array(
            "violations" => $violations,
            "form"       => $form
        );

        return $app['twig']->render('/developers/application_form.html.twig', $var);
    }

    /**
     * List of apps created by the user
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function listApps(Application $app, Request $request)
    {
        return $app['twig']->render('developers/applications.html.twig', array(
            "applications" => \API_OAuth2_Application::load_dev_app_by_user($app, $app['authentication']->getUser())
        ));
    }

    /**
     * Display form application
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function displayFormApp(Application $app, Request $request)
    {
        return $app['twig']->render('developers/application_form.html.twig', array(
            "violations" => null,
            'form'       => null,
            'request'    => $request
        ));
    }

    /**
     * Get application information
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @param  integer     $id      The application id
     * @return Response
     */
    public function getApp(Application $app, Request $request, $id)
    {
        try {
            $client = new \API_OAuth2_Application($app, $id);
        } catch (NotFoundHttpException $e) {
            $app->abort(404);
        }

        $token = $client->get_user_account($app['authentication']->getUser())->get_token()->get_value();

        return $app['twig']->render('developers/application.html.twig', array(
            "application" => $client,
            "user"        => $app['authentication']->getUser(),
            "token"       => $token
        ));
    }
}
