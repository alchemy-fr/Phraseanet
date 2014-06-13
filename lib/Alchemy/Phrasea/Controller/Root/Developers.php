<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Developers implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.account.developers'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->get('/applications/', 'controller.account.developers:listApps')
            ->bind('developers_applications');

        $controllers->get('/application/new/', 'controller.account.developers:displayFormApp')
            ->bind('developers_application_new');

        $controllers->post('/application/', 'controller.account.developers:newApp')
            ->bind('submit_developers_application');

        $controllers->get('/application/{application}/', 'controller.account.developers:getApp')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('developers_application');

        $controllers->delete('/application/{application}/', 'controller.account.developers:deleteApp')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('delete_developers_application');

        $controllers->post('/application/{application}/authorize_grant_password/', 'controller.account.developers:authorizeGrantPassword')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_developers_application_authorize_grant_password');

        $controllers->post('/application/{application}/access_token/', 'controller.account.developers:renewAccessToken')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_developers_application_token');

        $controllers->post('/application/{application}/callback/', 'controller.account.developers:renewAppCallback')
            ->before($app['middleware.api-application.converter'])
            ->assert('application', '\d+')
            ->bind('submit_application_callback');

        return $controllers;
    }

    /**
     * Delete application.
     *
     * @param Application    $app
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function deleteApp(Application $app, Request $request, ApiApplication $application)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, 'Bad request format, only JSON is allowed');
        }

        $app['manipulator.api-application']->delete($application);

        return $app->json(['success' => true]);
    }

    /**
     * Change application callback.
     *
     * @param Application    $app
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function renewAppCallback(Application $app, Request $request, ApiApplication $application)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, 'Bad request format, only JSON is allowed');
        }

        try {
            $app['manipulator.api-application']->setRedirectUri($application, $request->request->get("callback"));
        } catch (InvalidArgumentException $e) {
            return $app->json(['success' => false]);
        }

        return $app->json(['success' => true]);
    }

    /**
     * Authorize application to use a grant password type.
     *
     * @param Application    $app
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function renewAccessToken(Application $app, Request $request, ApiApplication $application)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, 'Bad request format, only JSON is allowed');
        }

        if (null === $account = $app['repo.api-accounts']->findByUserAndApplication($app['authentication']->getUser(), $application)) {
            $app->abort(404, sprintf('Account not found for application %s', $application->getName()));
        }

        if(null !== $devToken = $app['repo.api-oauth-tokens']->findDeveloperToken($account)) {
            $app['manipulator.api-oauth-token']->renew($devToken);
        } else {
            // dev tokens do not expires
            $devToken = $app['manipulator.api-oauth-token']->create($account);
        }

        return $app->json(['success' => true, 'token' => $devToken->getOauthToken()]);
    }

    /**
     * Authorize application to use a grant password type.
     *
     * @param Application    $app
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function authorizeGrantPassword(Application $app, Request $request, ApiApplication $application)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, 'Bad request format, only JSON is allowed');
        }

        $application->setGrantPassword((Boolean) $request->request->get('grant'));
        $app['manipulator.api-application']->update($application);

        return $app->json(['success' => true]);
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
        if ($request->request->get('type') === ApiApplication::DESKTOP_TYPE) {
            $form = new \API_OAuth2_Form_DevAppDesktop($app['request']);
        } else {
            $form = new \API_OAuth2_Form_DevAppInternet($app['request']);
        }

        $violations = $app['validator']->validate($form);

        if ($violations->count() === 0) {
            $application = $app['manipulator.api-application']->create(
                $form->getName(),
                $form->getType(),
                $form->getDescription(),
                sprintf('%s%s', $form->getSchemeWebsite(), $form->getWebsite()),
                $app['authentication']->getUser(),
                sprintf('%s%s', $form->getSchemeCallback(), $form->getCallback())
            );

            // create an account as well
            $app['manipulator.api-account']->create($application, $app['authentication']->getUser());

            return $app->redirectPath('developers_application', ['application' => $application->getId()]);
        }

        return $app['twig']->render('/developers/application_form.html.twig', [
            "violations" => $violations,
            "form"       => $form
        ]);
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
        return $app['twig']->render('developers/applications.html.twig', [
            "applications" => $app['repo.api-applications']->findByCreator($app['authentication']->getUser())
        ]);
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
        return $app['twig']->render('developers/application_form.html.twig', [
            "violations" => null,
            'form'       => null,
            'request'    => $request
        ]);
    }

    /**
     * Gets application information.
     *
     * @param Application    $app
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return mixed
     */
    public function getApp(Application $app, Request $request, ApiApplication $application)
    {
        $token = null;

        if (null !== $account = $app['repo.api-accounts']->findByUserAndApplication($app['authentication']->getUser(), $application)) {
            $token = $app['repo.api-oauth-tokens']->findDeveloperToken($account);
        }

        return $app['twig']->render('developers/application.html.twig', [
            "application" => $application,
            "user"        => $app['authentication']->getUser(),
            "token"       => $token
        ]);
    }
}
