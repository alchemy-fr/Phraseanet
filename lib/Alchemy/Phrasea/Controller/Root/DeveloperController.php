<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Alchemy\Phrasea\Model\Repositories\ApiAccountRepository;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Alchemy\Phrasea\Model\Repositories\ApiOauthTokenRepository;
use Alchemy\Phrasea\Model\Repositories\WebhookEventDeliveryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DeveloperController extends Controller
{
    use EntityManagerAware;

    /**
     * Delete application.
     *
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function deleteApp(Request $request, ApiApplication $application)
    {
        $this->assertJsonRequestFormat($request);

        $this->getApiApplicationManipulator()->delete($application);

        return $this->app->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @throws HttpException
     */
    private function assertJsonRequestFormat(Request $request)
    {
        if (!$request->isXmlHttpRequest()
            || !array_key_exists(
                $request->getMimeType('json'),
                array_flip($request->getAcceptableContentTypes())
            )
        ) {
            $this->app->abort(400, 'Bad request format, only JSON is allowed');
        }
    }

    /**
     * @return ApiApplicationManipulator
     */
    private function getApiApplicationManipulator()
    {
        return $this->app['manipulator.api-application'];
    }

    /**
     * Change application callback.
     *
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function renewAppCallback(Request $request, ApiApplication $application)
    {
        $this->assertJsonRequestFormat($request);

        try {
            $this->getApiApplicationManipulator()
                ->setRedirectUri($application, $request->request->get("callback"));
        } catch (InvalidArgumentException $e) {
            return $this->app->json(['success' => false]);
        }

        return $this->app->json(['success' => true]);
    }

    /**
     * Change application webhook
     *
     * @param Request        $request The current request
     * @param ApiApplication $application
     * @return JsonResponse
     */
    public function renewAppWebhook(Request $request, ApiApplication $application)
    {
        $this->assertJsonRequestFormat($request);

        if (null !== $request->request->get("webhook")) {
            $this->getApiApplicationManipulator()
                ->setWebhookUrl($application, $request->request->get("webhook"));
        } else {
            return $this->app->json(['success' => false]);
        }

        return $this->app->json(['success' => true]);
    }

    /**
     * Authorize application to use a grant password type.
     *
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function renewAccessToken(Request $request, ApiApplication $application)
    {
        $this->assertJsonRequestFormat($request);

        $account = $this->getApiAccountRepository()
            ->findByUserAndApplication($this->getAuthenticatedUser(), $application);
        if (null === $account) {
            $this->app->abort(404, sprintf('Account not found for application %s', $application->getName()));
        }

        if (null !== $devToken = $this->getApiOAuthTokenRepository()->findDeveloperToken($account)) {
            $this->getApiOAuthTokenManipulator()->renew($devToken);
        } else {
            // dev tokens do not expires
            $devToken = $this->getApiOAuthTokenManipulator()->create($account);
        }

        return $this->app->json(['success' => true, 'token' => $devToken->getOauthToken()]);
    }

    /**
     * Authorize application to use a grant password type.
     *
     * @param Request        $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function authorizeGrantPassword(Request $request, ApiApplication $application)
    {
        $this->assertJsonRequestFormat($request);

        $application->setGrantPassword((bool)$request->request->get('grant'));
        $this->getApiApplicationManipulator()->update($application);

        return $this->app->json(['success' => true]);
    }

    /**
     * Create a new developer applications
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function newApp(Request $request)
    {
        if ($request->request->get('type') === ApiApplication::DESKTOP_TYPE) {
            $form = new \API_OAuth2_Form_DevAppDesktop($request);
        } else {
            $form = new \API_OAuth2_Form_DevAppInternet($request);
        }

        $violations = $this->getValidator()->validate($form);

        if ($violations->count() === 0) {
            $user = $this->getAuthenticatedUser();
            $application = $this->getApiApplicationManipulator()
                ->create(
                    $form->getName(),
                    $form->getType(),
                    $form->getDescription(),
                    sprintf('%s%s', $form->getSchemeWebsite(), $form->getWebsite()),
                    $user,
                    sprintf('%s%s', $form->getSchemeCallback(), $form->getCallback())
                );

            // create an account as well
            $this->getApiAccountManipulator()->create($application, $user, V2::VERSION);

            return $this->app->redirectPath('developers_application', ['application' => $application->getId()]);
        }

        return $this->render('/developers/application_form.html.twig', [
            "violations" => $violations,
            "form"       => $form,
        ]);
    }

    /**
     * List of apps created by the user
     *
     * @return Response
     */
    public function listApps()
    {
        return $this->render('developers/applications.html.twig', [
            "applications" => $this->getApiApplicationRepository()->findByCreator($this->getAuthenticatedUser())
        ]);
    }

    /**
     * Display form application
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function displayFormApp(Request $request)
    {
        return $this->render('developers/application_form.html.twig', [
            "violations" => null,
            'form'       => null,
            'request'    => $request
        ]);
    }

    /**
     * Gets application information.
     *
     * @param ApiApplication $application
     *
     * @return mixed
     */
    public function getApp(ApiApplication $application)
    {
        $user = $this->getAuthenticatedUser();
        $account = $this->getApiAccountRepository()->findByUserAndApplication($user, $application);
        $token = $account
            ? $this->getApiOAuthTokenRepository()->findDeveloperToken($account)
            : null;

        if (! $account) {
            throw new AccessDeniedHttpException();
        }

        $deliveries = $this->getWebhookDeliveryRepository()
            ->findLastDeliveries($account->getApplication(), 10);

        return $this->render('developers/application.html.twig', [
            "application" => $application,
            "deliveries"  => $deliveries,
            "user"        => $user,
            "token"       => $token,
            "webhook_event_list"    => $this->getWebhookEventsList()
        ]);
    }

    /**
     * Update listenedEvents in application
     *
     * @param Request $request
     * @param ApiApplication $application
     * @return JsonResponse
     */
    public function updateListenedEvent(Request $request, ApiApplication $application)
    {
        $manager = $this->getEntityManager();
        $eventName = $request->query->get('event_name');

        if ($request->request->get('action') == 'add') {
            $application->addListenedEvent($eventName);
        } elseif ($request->request->get('action') == 'remove') {
            $application->removeListenedEvent($eventName);
        }

        $manager->persist($application);
        $manager->flush();

        return $this->app->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @param ApiApplication $application
     *
     * @return JsonResponse
     */
    public function activeWebhook(Request $request, ApiApplication $application)
    {
        $manager = $this->getEntityManager();

        if ($request->request->get('action') == 'activate') {
            $application->setWebhookActive(true);
        } elseif ($request->request->get('action') == 'deactivate') {
            $application->setWebhookActive(false);
        }

        $manager->persist($application);
        $manager->flush();

        return $this->app->json(['success' => true]);
    }

    /**
     * @return ApiAccountRepository
     */
    private function getApiAccountRepository()
    {
        return $this->app['repo.api-accounts'];
    }

    /**
     * @return ApiOauthTokenRepository
     */
    private function getApiOAuthTokenRepository()
    {
        return $this->app['repo.api-oauth-tokens'];
    }

    /**
     * @return ApiOauthTokenManipulator
     */
    private function getApiOAuthTokenManipulator()
    {
        return $this->app['manipulator.api-oauth-token'];
    }

    /**
     * @return ValidatorInterface
     */
    private function getValidator()
    {
        return $this->app['validator'];
    }

    /**
     * @return ApiAccountManipulator
     */
    private function getApiAccountManipulator()
    {
        return $this->app['manipulator.api-account'];
    }

    /**
     * @return ApiApplicationRepository
     */
    private function getApiApplicationRepository()
    {
        return $this->app['repo.api-applications'];
    }

    /**
     * @return WebhookEventDeliveryRepository
     */
    private function getWebhookDeliveryRepository()
    {
        return $this->app['webhook.delivery_repository'];
    }

    private function getWebhookEventsList()
    {
        return [
            WebhookEvent::RECORD_TYPE   =>  [
                WebhookEvent::RECORD_CREATED    =>  $this->app->trans("developers:: record or story created"),
                WebhookEvent::RECORD_EDITED     =>  $this->app->trans('developers:: record or story edited'),
                WebhookEvent::RECORD_DELETED    => $this->app->trans('developers:: record or story deleted'),
                WebhookEvent::RECORD_MEDIA_SUBSTITUTED  => $this->app->trans('developers:: media substituted for record'),
                WebhookEvent::RECORD_COLLECTION_CHANGED => $this->app->trans('developers:: collection changed for record'),
                WebhookEvent::RECORD_STATUS_CHANGED     => $this->app->trans('developers:: status changed for record')
            ],
            WebhookEvent::RECORD_SUBDEF_TYPE    =>  [
                WebhookEvent::RECORD_SUBDEF_CREATED     => $this->app->trans('developers:: subdef created for a record'),
                WebhookEvent::RECORD_SUBDEF_FAILED      => $this->app->trans('developers:: subdef creation failed for a record'),
            ],
            WebhookEvent::USER_TYPE     =>      [
                WebhookEvent::USER_CREATED      =>  $this->app->trans('developers:: new user created'),
                WebhookEvent::USER_DELETED      =>  $this->app->trans('developers:: user deleted on phraseanet')
            ],
            WebhookEvent::USER_REGISTRATION_TYPE    =>  [
                WebhookEvent::USER_REGISTRATION_GRANTED     =>  $this->app->trans('developers:: user registration granted on phraseanet'),
                WebhookEvent::USER_REGISTRATION_REJECTED    =>  $this->app->trans('developers:: user registration rejected on phraseanet')
            ],
            WebhookEvent::FEED_ENTRY_TYPE       =>  [
                WebhookEvent::NEW_FEED_ENTRY    =>  $this->app->trans('developers:: new feed entry on phraseanet')
            ],
            WebhookEvent::ORDER_TYPE        =>      [
                WebhookEvent::ORDER_CREATED     =>  $this->app->trans('developers:: new order created'),
                WebhookEvent::ORDER_DELIVERED   =>  $this->app->trans('developers:: a order delivered'),
                WebhookEvent::ORDER_DENIED      =>  $this->app->trans('developers:: a order denied')
            ]
        ];
    }
}
