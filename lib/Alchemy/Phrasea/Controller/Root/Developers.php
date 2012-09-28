<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Developers implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication($app);
        });

        /**
         * List of apps created by the user
         *
         * name         : developers_applications
         *
         * description  : List all user applications
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/applications/', $this->call('listApps'))
            ->bind('developers_applications');



        /**
         * Get the form to create a new application
         *
         * name         : developers_application_new
         *
         * description  : Display form to create a new user application
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/application/new/', $this->call('displayFormApp'))
            ->bind('developers_application_new');

        /**
         * Create a new app
         *
         * name         : submit_developers_application
         *
         * description  : POST request to create a new user app
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/application/', $this->call('newApp'))
            ->bind('submit_developers_application');


        /**
         * Get application information
         *
         * name         : developers_application
         *
         * description  : Get application information
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/application/{id}/', $this->call('getApp'))
            ->assert('id', '\d+')
            ->bind('developers_application');

        /**
         * Delete application
         *
         * name         : delete_developers_application
         *
         * description  : Delete selected application
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->delete('/application/{id}/', $this->call('deleteApp'))
            ->assert('id', '\d+')
            ->bind('delete_developers_application');

        /**
         * Allow authentification paswword grant method
         *
         * name         : submit_developers_application_authorize_grant_password
         *
         * description  : Authorize application to use a grant password type, which allow end user to
         *                authenticate himself with their credentials (login/password)
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/application/{id}/authorize_grant_password/', $this->call('authorizeGrantpassword'))
            ->assert('id', '\d+')
            ->bind('submit_developers_application_authorize_grant_password');

        /**
         * Renew access token
         *
         * name         : submit_developers_application_token
         *
         * description  : Regenerate an access token for the current app linked to the authenticated user
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/application/{id}/access_token/', $this->call('renewAccessToken'))
            ->assert('id', '\d+')
            ->bind('submit_developers_application_token');

        /**
         * Update application callback
         *
         * name         : submit_application_callback
         *
         * description  : Change callback used by application
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/application/{id}/callback/', $this->call('renewAppCallback'))
            ->assert('id', '\d+')
            ->bind('submit_application_callback');

        return $controllers;
    }

    /**
     * Delete application
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @param   integer         $id      The application id
     * @return  JsonResponse
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
        } catch (\Exception_NotFound $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Change application callback
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @param   integer         $id      The application id
     * @return  JsonResponse
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
        } catch (\Exception_NotFound $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Authorize application to use a grant password type
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @param   integer         $id      The application id
     * @return  JsonResponse
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
            $account = $clientApp->get_user_account($app['phraseanet.user']);

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
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @param   integer         $id      The application id
     * @return  JsonResponse
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
        } catch (\Exception_NotFound $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Create a new developer applications
     *
     * @param   Application $app     A Silex application where the controller is mounted on
     * @param   Request     $request The current request
     * @return  Response
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
            $application = \API_OAuth2_Application::create($app, $app['phraseanet.user'], $form->getName());
            $application
                ->set_description($form->getDescription())
                ->set_redirect_uri($form->getSchemeCallback() . $form->getCallback())
                ->set_type($form->getType())
                ->set_website($form->getSchemeWebsite() . $form->getWebsite());

            return $app->redirect(sprintf('/developers/application/%d/', $application->get_id()));
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
     * @param   Application $app     A Silex application where the controller is mounted on
     * @param   Request     $request The current request
     * @return  Response
     */
    public function listApps(Application $app, Request $request)
    {
        return $app['twig']->render('developers/applications.html.twig', array(
            "applications" => \API_OAuth2_Application::load_dev_app_by_user($app, $app['phraseanet.user'])
        ));
    }

    /**
     * Display form application
     *
     * @param   Application $app     A Silex application where the controller is mounted on
     * @param   Request     $request The current request
     * @return  Response
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
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @param   integer         $id      The application id
     * @return  Response
     */
    public function getApp(Application $app, Request $request, $id)
    {
        try {
            $client = new \API_OAuth2_Application($app, $id);
        } catch (\Exception_NotFound $e) {
            $app->abort(404);
        }

        $token = $client->get_user_account($app['phraseanet.user'])->get_token()->get_value();

        return $app['twig']->render('developers/application.html.twig', array(
            "application" => $client,
            "user"        => $app['phraseanet.user'],
            "token"       => $token
        ));
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
