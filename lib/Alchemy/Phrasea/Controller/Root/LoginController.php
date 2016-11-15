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

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Authentication\AccountCreator;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Exception\AuthenticationException;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordAuthenticationInterface;
use Alchemy\Phrasea\Authentication\Phrasea\PasswordEncoder;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Authentication\RecoveryService;
use Alchemy\Phrasea\Authentication\RegistrationService;
use Alchemy\Phrasea\Authentication\SuggestionFinder;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\Event\ValidationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\FormProcessingException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Helper\User\Manage;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\FeedItemRepository;
use Alchemy\Phrasea\Model\Repositories\FeedRepository;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrAuthProviderRepository;
use Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRecoverPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;
use Doctrine\ORM\EntityManagerInterface;
use RandomLib\Generator;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;

class LoginController extends Controller
{
    use DispatcherAware;
    use EntityManagerAware;
    use NotifierAware;

    /**
     * @param Request $request
     * @return array
     */
    public function getDefaultTemplateVariables(Request $request)
    {
        $items = [];

        foreach ($this->getFeedItemRepository()->loadLatest($this->app, 20) as $item) {
            $record = $item->getRecord($this->app);
            $preview = $record->get_subdef('preview');
            $permalink = $preview->get_permalink();

            $items[] = [
                'record' => $record,
                'preview' => $preview,
                'permalink' => $permalink
            ];
        }

        $conf = $this->getConf();
        $browser = $this->getBrowser();
        
        return [
            'last_publication_items' => $items,
            'instance_title' => $conf->get(['registry', 'general', 'title']),
            'has_terms_of_use' => $this->app->hasTermsOfUse(),
            'meta_description' =>  $conf->get(['registry', 'general', 'description']),
            'meta_keywords' => $conf->get(['registry', 'general', 'keywords']),
            'browser_name' => $browser->getBrowser(),
            'browser_version' => $browser->getVersion(),
            'available_language' => $this->app['locales.available'],
            'locale' => $this->app['locale'],
            'current_url' => $request->getUri(),
            'flash_types' => $this->app->getAvailableFlashTypes(),
            'recaptcha_display' => $this->app->isCaptchaRequired(),
            'unlock_usr_id' => $this->app->getUnlockAccountData(),
            'guest_allowed' => $this->app->isGuestAllowed(),
            'register_enable' => $this->getRegistrationManager()->isRegistrationEnabled(),
            'display_layout' => $conf->get(['registry', 'general', 'home-presentation-mode']),
            'authentication_providers' => $this->app['authentication.providers'],
            'registration_fields' => $this->getRegistrationFields(),
            'registration_optional_fields' => $this->getOptionalRegistrationFields(),
        ];
    }

    public function getRegistrationFieldsAction()
    {
        return $this->app->json($this->getRegistrationFields());
    }

    public function getCgusAction(Request $request)
    {
        return $this->render('login/cgus.html.twig', array_merge(
            ['cgus' => \databox_cgu::getHome($this->app)],
            $this->getDefaultTemplateVariables($request)
        ));
    }

    public function getLanguage()
    {
        $response =  $this->app->json([
            'validation_blank'          => $this->app->trans('Please provide a value.'),
            'validation_choice_min'     => $this->app->trans('Please select at least %s choice.'),
            'validation_email'          => $this->app->trans('Please provide a valid email address.'),
            'validation_ip'             => $this->app->trans('Please provide a valid IP address.'),
            'validation_length_min'     => $this->app->trans('Please provide a longer value. It should have %s character or more.'),
            'password_match'            => $this->app->trans('Please provide the same passwords.'),
            'email_match'               => $this->app->trans('Please provide the same emails.'),
            'accept_tou'                => $this->app->trans('Please accept the terms of use to register.'),
            'no_collection_selected'    => $this->app->trans('No collection selected'),
            'one_collection_selected'   => $this->app->trans('%d collection selected'),
            'collections_selected'      => $this->app->trans('%d collections selected'),
            'all_collections'           => $this->app->trans('Select all collections'),
            // password strength
            'weak'                      => $this->app->trans('Weak'),
            'ordinary'                  => $this->app->trans('Ordinary'),
            'good'                      => $this->app->trans('Good'),
            'great'                     => $this->app->trans('Great'),
        ]);

        $response->setExpires(new \DateTime('+1 day'));

        return $response;
    }

    public function doRegistration(Request $request)
    {
        if (!$this->getRegistrationManager()->isRegistrationEnabled()) {
            $this->app->abort(404, 'Registration is disabled');
        }

        $form = $this->app->form(new PhraseaRegisterForm(
            $this->app, $this->getOptionalRegistrationFields(), $this->getRegistrationFields()
        ));

        if ('POST' === $request->getMethod()) {
            $requestData = $request->request->all();

            // Remove geocompleter field for validation this field is added client side
            // with jquery geonames plugin
            if (isset($requestData['geonameid']) && isset($requestData['geonameid-completer'])) {
                unset($requestData['geonameid-completer']);
            }

            // Remove multiselect field for validation this field is added client side
            // with bootstrap multiselect plugin
            if (isset($requestData['multiselect'])) {
                unset($requestData['multiselect']);
            }

            $form->submit($requestData);
            $data = $form->getData();

            $provider = null;
            if ($data['provider-id']) {
                try {
                    $provider = $this->findProvider($data['provider-id']);
                } catch (NotFoundHttpException $e) {
                    $this->app->addFlash('error', $this->app->trans('You tried to register with an unknown provider'));

                    return $this->app->redirectPath('login_register');
                }

                try {
                    $token = $provider->getToken();
                } catch (NotAuthenticatedException $e) {
                    $this->app->addFlash('error', $this->app->trans('You tried to register with an unknown provider'));

                    return $this->app->redirectPath('login_register');
                }

                $userAuthProvider = $this->getUserAuthProviderRepository()
                    ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

                if (null !== $userAuthProvider) {
                    $this->postAuthProcess($request, $userAuthProvider->getUser());

                    if (null !== $redirect = $request->query->get('redirect')) {
                        $redirection = '../' . $redirect;
                    } else {
                        $redirection = $this->app->path('prod');
                    }

                    return $this->app->redirect($redirection);
                }
            }

            try {
                if ($form->isValid()) {
                    $captcha = $this->getRecaptcha()->bind($request);

                    $conf = $this->getConf();
                    if ($conf->get(['registry', 'webservices', 'captcha-enabled']) && !$captcha->isValid()) {
                        throw new FormProcessingException($this->app->trans('Invalid captcha answer.'));
                    }

                    $registrationService = $this->getRegistrationService();
                    $providerId = isset($data['provider-id']) ? $data['provider-id'] : null;
                    $selectedCollections = isset($data['collections']) ? $data['collections'] : null;

                    $user = $registrationService->registerUser($data, $selectedCollections, $providerId);

                    try {
                        $this->sendAccountUnlockEmail($user, $request);
                        $this->app->addFlash('info', $this->app->trans('login::notification: demande de confirmation par mail envoyee'));
                    } catch (InvalidArgumentException $e) {
                        // todo, log this failure
                        $this->app->addFlash('error', $this->app->trans('Unable to send your account unlock email.'));
                    }

                    return $this->app->redirectPath('homepage');
                }
            } catch (FormProcessingException $e) {
                $this->app->addFlash('error', $e->getMessage());
            }
        } elseif (null !== $request->query->get('providerId')) {
            $provider = $this->findProvider($request->query->get('providerId'));
            $identity = $provider->getIdentity();

            $form->setData(array_filter([
                'email'       => $identity->getEmail(),
                'firstname'   => $identity->getFirstname(),
                'lastname'    => $identity->getLastname(),
                'company'     => $identity->getCompany(),
                'provider-id' => $provider->getId(),
            ]));
        }

        $url = $this->app['geonames.server-uri'];
        return $this->render('login/register-classic.html.twig', array_merge(
            $this->getDefaultTemplateVariables($request),
            [
                'geonames_server_uri' => str_replace(sprintf('%s:', parse_url($url, PHP_URL_SCHEME)), '', $url),
                'form' => $form->createView()
            ]));
    }

    private function attachProviderToUser(EntityManagerInterface $em, ProviderInterface $provider, User $user)
    {
        $usrAuthProvider = new UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUser($user);

        try {
            $provider->logout();
        } catch (RuntimeException $e) {
            // log these errors
        }

        $em->persist($usrAuthProvider);
    }

    /**
     * Send a confirmation mail after register
     *
     * @param  Request $request The current request
     * @return RedirectResponse
     */
    public function sendConfirmMail(Request $request)
    {
        if (null === $usrId = $request->query->get('usr_id')) {
            $this->app->abort(400, 'Missing usr_id parameter.');
        }

        $user = $this->getUserRepository()->find((int) $usrId);
        if (!$user instanceof User) {
            $this->app->addFlash('error', $this->app->trans('Invalid link.'));

            return $this->app->redirectPath('homepage');
        }

        try {
            $this->sendAccountUnlockEmail($user, $request);
            $this->app->addFlash('success', $this->app->trans('login::notification: demande de confirmation par mail envoyee'));
        } catch (InvalidArgumentException $e) {
            // todo, log this failure
            $this->app->addFlash('error', $this->app->trans('Unable to send your account unlock email.'));
        }

        return $this->app->redirectPath('homepage');
    }

    private function sendAccountUnlockEmail(User $user, Request $request)
    {
        $helper = new Manage($this->app, $request);

        $helper->sendAccountUnlockEmail($user);
    }

    /**
     * Validation of email address
     *
     * @param  Request $request The current request
     * @return RedirectResponse
     */
    public function registerConfirm(Request $request)
    {
        if (null === $code = $request->query->get('code')) {
            $this->app->addFlash('error', $this->app->trans('Invalid unlock link.'));

            return $this->app->redirectPath('homepage');
        }

        if (null === $token = $this->getTokenRepository()->findValidToken($code)) {
            $this->app->addFlash('error', $this->app->trans('Invalid unlock link.'));

            return $this->app->redirectPath('homepage');
        }

        $user = $token->getUser();

        if (!$user->isMailLocked()) {
            $this->app->addFlash('info', $this->app->trans('Account is already unlocked, you can login.'));

            return $this->app->redirectPath('homepage');
        }

        $tokenManipulator = $this->getTokenManipulator();
        $tokenManipulator->delete($token);
        $user->setMailLocked(false);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            $this->app->addFlash('success', $this->app->trans('Account has been unlocked, you can now login.'));

            return $this->app->redirectPath('homepage');
        }

        $tokenManipulator->delete($token);

        if (count($this->getAclForUser($user)->get_granted_base()) > 0) {
            $mail = MailSuccessEmailConfirmationRegistered::create($this->app, $receiver);
            $this->deliver($mail);

            $this->app->addFlash('success', $this->app->trans('Account has been unlocked, you can now login.'));
        } else {
            $mail = MailSuccessEmailConfirmationUnregistered::create($this->app, $receiver);
            $this->deliver($mail);

            $this->app->addFlash('info', $this->app->trans('Account has been unlocked, you still have to wait for admin approval.'));
        }

        return $this->app->redirectPath('homepage');
    }

    public function renewPassword(Request $request)
    {
        $service = $this->getRecoveryService();

        $form = $this->app->form(new PhraseaRecoverPasswordForm($this->getTokenRepository()));
        $form->setData(['token' => $request->get('token') ]);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $service->resetPassword($data['token'], $data['password']);
                $this->app->addFlash('success', $this->app->trans('login::notification: Mise a jour du mot de passe avec succes'));

                return $this->app->redirectPath('homepage');
            }
        }

        return $this->render('login/renew-password.html.twig', array_merge(
            $this->getDefaultTemplateVariables($request),
            ['form' => $form->createView() ]
        ));
    }

    /**
     * Submit the new password
     *
     * @param  Request $request The current request
     * @return RedirectResponse
     */
    public function forgotPassword(Request $request)
    {
        $form = $this->app->form(new PhraseaForgotPasswordForm());
        $service = $this->getRecoveryService();

        try {
            if ('POST' === $request->getMethod()) {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    try {
                        $service->requestPasswordResetToken($data['email'], true);
                    }
                    catch (InvalidArgumentException $ex) {
                        /** @Ignore */
                        $message = $this->app->trans($ex->getMessage());
                        throw new FormProcessingException($message, 0, $ex);
                    }

                    $this->app->addFlash('info', $this->app->trans('phraseanet:: Un email vient de vous etre envoye'));

                    return $this->app->redirectPath('login_forgot_password');
                }
            }
        } catch (FormProcessingException $e) {
            $this->app->addFlash('error', $e->getMessage());
        }

        return $this->render('login/forgot-password.html.twig', array_merge(
            $this->getDefaultTemplateVariables($request),
            [ 'form'  => $form->createView() ]
        ));
    }

    /**
     * Get the register form
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function displayRegisterForm(Request $request)
    {
        if (!$this->getRegistrationManager()->isRegistrationEnabled()) {
            $this->app->abort(404, 'Registration is disabled');
        }

        if (0 < count($this->getAuthenticationProviders())) {
            return $this->render('login/register.html.twig', $this->getDefaultTemplateVariables($request));
        } else {
            return $this->app->redirectPath('login_register_classic');
        }
    }

    /**
     * Logout from Phraseanet
     *
     * @param  Request $request The current request
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        $this->dispatch(PhraseaEvents::LOGOUT, new LogoutEvent($this->app));
        $this->getAuthenticator()->closeAccount();

        $this->app->addFlash('info', $this->app->trans('Vous etes maintenant deconnecte. A bientot.'));

        $response = $this->app->redirectPath('homepage', [
            'redirect' => $request->query->get("redirect")
        ]);

        $response->headers->clearCookie('persistent');
        $response->headers->clearCookie('last_act');

        return $response;
    }

    /**
     * Login into Phraseanet
     *
     * @param  Request $request The current request
     * @return Response
     */
    public function login(Request $request)
    {
        try {
            $this->getApplicationBox()->get_connection();
        } catch (\Exception $e) {
            $this->app->addFlash('error', $this->app->trans('login::erreur: No available connection - Please contact sys-admin'));
        }

        $feeds = $this->getFeedRepository()->findBy(['public' => true], ['updatedOn' => 'DESC']);

        $form = $this->app->form(new PhraseaAuthenticationForm($this->app));
        $form->setData([
            'redirect' => $request->query->get('redirect')
        ]);

        return $this->render('login/index.html.twig', array_merge(
            $this->getDefaultTemplateVariables($request),
            [
                'feeds' => $feeds,
                'form'  => $form->createView(),
            ]));
    }

    /**
     * Authenticate to phraseanet
     *
     * @param  Request $request The current request
     * @return RedirectResponse
     */
    public function authenticate(Request $request)
    {
        $form = $this->app->form(new PhraseaAuthenticationForm($this->app));
        $redirector = function (array $params = []) {
            return $this->app->redirectPath('homepage', $params);
        };

        try {
            return $this->doAuthentication($request, $form, $redirector);
        } catch (AuthenticationException $e) {
            return $e->getResponse();
        }
    }

    public function authenticateAsGuest(Request $request)
    {
        if (!$this->app->isGuestAllowed()) {
            $this->app->abort(403, $this->app->trans('Phraseanet guest-access is disabled'));
        }

        $context = new Context(Context::CONTEXT_GUEST);
        $this->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        do {
            $login = uniqid('guest');
        } while (null !== $this->getUserRepository()->findOneBy(['login' => $login]));

        $user = $this->getUserManipulator()->createUser($login, $this->getStringGenerator()->generateString(128));
        $invite_user = $this->getUserRepository()->findByLogin(User::USER_GUEST);

        $usr_base_ids = array_keys($this->getAclForUser($user)->get_granted_base());
        $this->getAclForUser($user)->revoke_access_from_bases($usr_base_ids);

        $invite_base_ids = array_keys($this->getAclForUser($invite_user)->get_granted_base());
        $this->getAclForUser($user)->apply_model($invite_user, $invite_base_ids);

        $this->postAuthProcess($request, $user);

        $response = $this->generateAuthResponse($this->getBrowser(), $request->request->get('redirect'));
        $response->headers->setCookie(new Cookie('invite-usr-id', $user->getId()));

        $event = new PostAuthenticate($request, $response, $user, $context);
        $this->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        return $response;
    }

    public function generateAuthResponse(\Browser $browser, $redirect)
    {
        if ($browser->isMobile()) {
            $response = $this->app->redirectPath('lightbox');
        } elseif ($redirect) {
            $response = new RedirectResponse('../' . ltrim($redirect,'/'));
        } elseif (true !== $browser->isNewGeneration()) {
            $response = $this->app->redirectPath('get_client');
        } else {
            $response = $this->app->redirectPath('prod');
        }

        $response->headers->clearCookie('last_act');

        return $response;
    }

    // move this in an event
    public function postAuthProcess(Request $request, User $user)
    {
        $date = new \DateTime('+' . (int) $this->getConf()->get(['registry', 'actions', 'validation-reminder-days']) . ' days');
        $manager = $this->getEntityManager();

        foreach ($this->getValidationParticipantRepository()->findNotConfirmedAndNotRemindedParticipantsByExpireDate($date) as $participant) {
            $validationSession = $participant->getSession();
            $basket = $validationSession->getBasket();

            if (null === $token = $this->getTokenRepository()->findValidationToken($basket, $participant->getUser())) {
                continue;
            }

            $url = $this->app->url('lightbox_validation', ['basket' => $basket->getId(), 'LOG' => $token->getValue()]);
            $this->dispatch(PhraseaEvents::VALIDATION_REMINDER, new ValidationEvent($participant, $basket, $url));

            $participant->setReminded(new \DateTime('now'));
            $manager->persist($participant);
        }

        $manager->flush();

        $session = $this->getAuthenticator()->openAccount($user);

        if ($user->getLocale() != $this->app['locale']) {
            $user->setLocale($this->app['locale']);
        }

        $width = $height = null;
        if ($request->cookies->has('screen')) {
            $data = array_filter((explode('x', $request->cookies->get('screen', ''))));
            if (count($data) === 2) {
                $width = $data[0];
                $height = $data[1];
            }
        }
        $session->setIpAddress($request->getClientIp())
            ->setScreenHeight($height)
            ->setScreenWidth($width);

        $manager->persist($session);
        $manager->flush();

        return $session;
    }

    public function authenticateWithProvider(Request $request, $providerId)
    {
        $provider = $this->findProvider($providerId);

        return $provider->authenticate($request->query->all());
    }

    public function authenticationCallback(Request $request, $providerId)
    {
        $provider = $this->findProvider($providerId);

        // triggers what's necessary
        try {
            $provider->onCallback($request);
            $token = $provider->getToken();
        } catch (NotAuthenticatedException $e) {
            $this->getSession()->getFlashBag()->add('error', $this->app->trans('Unable to authenticate with %provider_name%', ['%provider_name%' => $provider->getName()]));

            return $this->app->redirectPath('homepage');
        }

        $userAuthProvider = $this->getUserAuthProviderRepository()
            ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

        if (null !== $userAuthProvider) {
            $this->postAuthProcess($request, $userAuthProvider->getUser());

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $this->app->path('prod');
            }

            return $this->app->redirect($redirection);
        }

        try {
            $user = $this->getAuthenticationSuggestionFinder()->find($token);
        } catch (NotAuthenticatedException $e) {
            $this->app->addFlash('error', $this->app->trans('Unable to retrieve provider identity'));

            return $this->app->redirectPath('homepage');
        }

        $manager = $this->getEntityManager();
        if (null !== $user) {
            $this->attachProviderToUser($manager, $provider, $user);
            $manager->flush();

            $this->postAuthProcess($request, $user);

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $this->app->path('prod');
            }

            return $this->app->redirect($redirection);
        }

        $accountCreatorProvider = $this->getAuthenticationAccountCreatorProvider();
        if ($accountCreatorProvider->isEnabled()) {
            $user = $accountCreatorProvider->create($this->app, $token->getId(), $token->getIdentity()->getEmail(), $token->getTemplates());

            $this->attachProviderToUser($manager, $provider, $user);
            $manager->flush();

            $this->postAuthProcess($request, $user);

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $this->app->path('prod');
            }

            return $this->app->redirect($redirection);
        } elseif ($this->getRegistrationManager()->isRegistrationEnabled()) {
            return $this->app->redirectPath('login_register_classic', ['providerId' => $providerId]);
        }

        $this->app->addFlash('error', $this->app->trans('Your identity is not recognized.'));

        return $this->app->redirectPath('homepage');
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

    private function doAuthentication(Request $request, FormInterface $form, $redirector)
    {
        if (!is_callable($redirector)) {
            throw new InvalidArgumentException('Redirector should be callable');
        }

        $context = new Context(Context::CONTEXT_NATIVE);
        $this->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        $form->handleRequest($request);

        $resp = $form->getExtraData();

        if(!isset($resp["g-recaptcha-response"])) {
            if (!$form->isValid()) {
                $this->app->addFlash('error', $this->app->trans('An unexpected error occurred during authentication process, please contact an admin'));

                throw new AuthenticationException(call_user_func($redirector));
            }
        }

        $params = [];

        if (null !== $redirect = $request->get('redirect')) {
            $params['redirect'] = ltrim($redirect, '/');
        }

        try {
            $usr_id = $this->getPasswordAuthentication()->getUsrId(
                $request->request->get('login'),
                $request->request->get('password'),
                $request
            );
        } catch (RequireCaptchaException $e) {
            $this->app->requireCaptcha();
            $this->app->addFlash('warning', $this->app->trans('Please fill the captcha'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        } catch (AccountLockedException $e) {
            $this->app->addFlash('warning', $this->app->trans('login::erreur: Vous n\'avez pas confirme votre email'));
            $this->app->addUnlockAccountData($e->getUsrId());

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        if (null === $usr_id) {
            $this->getSession()->getFlashBag()->set('error', $this->app->trans('login::erreur: Erreur d\'authentification'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        $user = $this->getUserRepository()->find($usr_id);
        $session = $this->postAuthProcess($request, $user);

        $response = $this->generateAuthResponse($this->getBrowser(), $request->request->get('redirect'));
        $response->headers->clearCookie('invite-usr-id');

        if ($request->request->get('remember-me') == '1') {
            $nonce = $this->getStringGenerator()->generateString(64);
            $string = $this->getBrowser()->getBrowser() . '_' . $this->getBrowser()->getPlatform();

            $token = $this->getPasswordEncoder()
                ->encodePassword($string, $nonce);

            $session->setToken($token)->setNonce($nonce);

            $response->headers->setCookie(new Cookie('persistent', $token, time() + $this->getConfigurationStore()->offsetGet('session')['lifetime']));

            $manager = $this->getEntityManager();
            $manager->persist($session);
            $manager->flush();
        }

        $event = new PostAuthenticate($request, $response, $user, $context);
        $this->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        return $event->getResponse();
    }

    /**
     * @return FeedItemRepository
     */
    private function getFeedItemRepository()
    {
        return $this->app['repo.feed-items'];
    }

    /**
     * @return \Browser
     */
    private function getBrowser()
    {
        return $this->app['browser'];
    }

    /**
     * @return RegistrationManager
     */
    private function getRegistrationManager()
    {
        return $this->app['registration.manager'];
    }

    /**
     * @return string[]
     */
    private function getRegistrationFields()
    {
        return $this->app['registration.fields'];
    }

    /**
     * @return string[]
     */
    private function getOptionalRegistrationFields()
    {
        return $this->app['registration.optional-fields'];
    }

    /**
     * @return UsrAuthProviderRepository
     */
    private function getUserAuthProviderRepository()
    {
        return $this->app['repo.usr-auth-providers'];
    }

    /**
     * @return ReCaptcha
     */
    private function getRecaptcha()
    {
        return $this->app['recaptcha'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return RegistrationManipulator
     */
    private function getRegistrationManipulator()
    {
        return $this->app['manipulator.registration'];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->app['repo.tokens'];
    }

    /**
     * @return ProvidersCollection
     */
    private function getAuthenticationProviders()
    {
        return $this->app['authentication.providers'];
    }

    /**
     * @return FeedRepository
     */
    private function getFeedRepository()
    {
        return $this->app['repo.feeds'];
    }

    /**
     * @return Generator
     */
    private function getStringGenerator()
    {
        return $this->app['random.medium'];
    }

    /**
     * @return ValidationParticipantRepository
     */
    private function getValidationParticipantRepository()
    {
        return $this->app['repo.validation-participants'];
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return SuggestionFinder
     */
    private function getAuthenticationSuggestionFinder()
    {
        return $this->app['authentication.suggestion-finder'];
    }

    /**
     * @return AccountCreator
     */
    private function getAuthenticationAccountCreatorProvider()
    {
        return $this->app['authentication.providers.account-creator'];
    }

    /**
     * @return PasswordAuthenticationInterface
     */
    private function getPasswordAuthentication()
    {
        return $this->app['auth.native'];
    }

    /**
     * @return PasswordEncoder
     */
    private function getPasswordEncoder()
    {
        return $this->app['auth.password-encoder'];
    }

    /**
     * @return ConfigurationInterface
     */
    private function getConfigurationStore()
    {
        return $this->app['configuration.store'];
    }

    /**
     * @return RecoveryService
     */
    private function getRecoveryService()
    {
        return $this->app['authentication.recovery_service'];
    }

    /**
     * @return RegistrationService
     */
    private function getRegistrationService()
    {
        return $this->app['authentication.registration_service'];
    }
}
