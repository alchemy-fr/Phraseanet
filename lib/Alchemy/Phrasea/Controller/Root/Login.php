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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Exception\AuthenticationException;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\Event\RegistrationEvent;
use Alchemy\Phrasea\Core\Event\ValidationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\FormProcessingException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRecoverPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;
use Doctrine\ORM\EntityManager;
use igorw;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;

class Login implements ControllerProviderInterface
{
    public static function getDefaultTemplateVariables(Application $app)
    {
        $items = [];

        foreach ($app['repo.feed-items']->loadLatest($app, 20) as $item) {
            $record = $item->getRecord($app);
            $preview = $record->get_subdef('preview');
            $permalink = $preview->get_permalink();

            $items[] = [
                'record' => $record,
                'preview' => $preview,
                'permalink' => $permalink
            ];
        }

        return [
            'last_publication_items' => $items,
            'instance_title' => $app['conf']->get(['registry', 'general', 'title']),
            'has_terms_of_use' => $app->hasTermsOfUse(),
            'meta_description' =>  $app['conf']->get(['registry', 'general', 'description']),
            'meta_keywords' => $app['conf']->get(['registry', 'general', 'keywords']),
            'browser_name' => $app['browser']->getBrowser(),
            'browser_version' => $app['browser']->getVersion(),
            'available_language' => $app['locales.available'],
            'locale' => $app['locale'],
            'current_url' => $app['request']->getUri(),
            'flash_types' => $app->getAvailableFlashTypes(),
            'recaptcha_display' => $app->isCaptchaRequired(),
            'unlock_usr_id' => $app->getUnlockAccountData(),
            'guest_allowed' => $app->isGuestAllowed(),
            'register_enable' => $app['registration.manager']->isRegistrationEnabled(),
            'display_layout' => $app['conf']->get(['registry', 'general', 'home-presentation-mode']),
            'authentication_providers' => $app['authentication.providers'],
            'registration_fields' => $app['registration.fields'],
            'registration_optional_fields' => $app['registration.optional-fields']
        ];
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['login.controller'] = $this;

        $controllers->before(function (Request $request) use ($app) {
            if ($request->getPathInfo() == $app->path('homepage')) {
                return;
            }
        });

        // Displays the homepage
        $controllers->get('/', 'login.controller:login')
            ->before(function (Request $request) use ($app) {
                    if (null !== $response = $app['firewall']->requireNotAuthenticated()) {

                        return $response;
                    }
                })
            ->bind('homepage');

        // Authentication end point
        $controllers->post('/authenticate/', 'login.controller:authenticate')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })
            ->bind('login_authenticate');

        // Guest access end point
        $controllers->match('/authenticate/guest/', 'login.controller:authenticateAsGuest')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })
            ->bind('login_authenticate_as_guest')
            ->method('GET|POST');

        // Authenticate with an AuthProvider
        $controllers->get('/provider/{providerId}/authenticate/', 'login.controller:authenticateWithProvider')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })
            ->bind('login_authentication_provider_authenticate');

        // AuthProviders callbacks
        $controllers->get('/provider/{providerId}/callback/', 'login.controller:authenticationCallback')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_authentication_provider_callback');

        // Logout end point
        $logoutController = $controllers->get('/logout/', 'login.controller:logout')
            ->bind('logout');

        $app['firewall']->addMandatoryAuthentication($logoutController);

        // Registration end point ; redirects to classic registration or AuthProvider registration
        $controllers->get('/register/', 'login.controller:displayRegisterForm')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_register');

        // Classic registration end point
        $controllers->match('/register-classic/', 'login.controller:doRegistration')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })
            ->bind('login_register_classic');

        // Provide a JSON serialization of registration fields configuration
        $controllers->get('/registration-fields/', function (PhraseaApplication $app, Request $request) {
            return $app->json($app['registration.fields']);
        })->bind('login_registration_fields');

        // Unlocks an email address that is currently locked
        $controllers->get('/register-confirm/', 'login.controller:registerConfirm')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_register_confirm');

        // Displays a form to send an account unlock email again
        $controllers->get('/send-mail-confirm/', 'login.controller:sendConfirmMail')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_send_mail');

        // Forgot password end point
        $controllers->match('/forgot-password/', 'login.controller:forgotPassword')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_forgot_password');

        // Renew password end point
        $controllers->match('/renew-password/', 'login.controller:renewPassword')
            ->before(function (Request $request) use ($app) {
                if (null !== $response = $app['firewall']->requireNotAuthenticated()) {
                    return $response;
                }
            })->bind('login_renew_password');

        // Displays Terms of use
        $controllers->get('/cgus', function (PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/cgus.html.twig', array_merge(
                ['cgus' => \databox_cgu::getHome($app)],
                self::getDefaultTemplateVariables($app)
            ));
        })->bind('login_cgus');

        $controllers->get('/language.json', 'login.controller:getLanguage')
            ->bind('login_language');

        return $controllers;
    }

    public function getLanguage(Application $app, Request $request)
    {
        $response =  $app->json([
            'validation_blank'          => $app->trans('Please provide a value.'),
            'validation_choice_min'     => $app->trans('Please select at least %s choice.'),
            'validation_email'          => $app->trans('Please provide a valid email address.'),
            'validation_ip'             => $app->trans('Please provide a valid IP address.'),
            'validation_length_min'     => $app->trans('Please provide a longer value. It should have %s character or more.'),
            'password_match'            => $app->trans('Please provide the same passwords.'),
            'email_match'               => $app->trans('Please provide the same emails.'),
            'accept_tou'                => $app->trans('Please accept the terms of use to register.'),
            'no_collection_selected'    => $app->trans('No collection selected'),
            'one_collection_selected'   => $app->trans('%d collection selected'),
            'collections_selected'      => $app->trans('%d collections selected'),
            'all_collections'           => $app->trans('Select all collections'),
            // password strength
            'weak'                      => $app->trans('Weak'),
            'ordinary'                  => $app->trans('Ordinary'),
            'good'                      => $app->trans('Good'),
            'great'                     => $app->trans('Great'),
        ]);

        $response->setExpires(new \DateTime('+1 day'));

        return $response;
    }

    public function doRegistration(PhraseaApplication $app, Request $request)
    {
        if (!$app['registration.manager']->isRegistrationEnabled()) {
            $app->abort(404, 'Registration is disabled');
        }

        $form = $app->form(new PhraseaRegisterForm(
            $app, $app['registration.optional-fields'], $app['registration.fields']
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

            $form->bind($requestData);
            $data = $form->getData();

            $provider = null;
            if ($data['provider-id']) {
                try {
                    $provider = $this->findProvider($app, $data['provider-id']);
                } catch (NotFoundHttpException $e) {
                    $app->addFlash('error', $app->trans('You tried to register with an unknown provider'));

                    return $app->redirectPath('login_register');
                }

                try {
                    $token = $provider->getToken();
                } catch (NotAuthenticatedException $e) {
                    $app->addFlash('error', $app->trans('You tried to register with an unknown provider'));

                    return $app->redirectPath('login_register');
                }

                $userAuthProvider = $app['repo.usr-auth-providers']
                    ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

                if (null !== $userAuthProvider) {
                    $this->postAuthProcess($app, $userAuthProvider->getUser());

                    if (null !== $redirect = $request->query->get('redirect')) {
                        $redirection = '../' . $redirect;
                    } else {
                        $redirection = $app->path('prod');
                    }

                    return $app->redirect($redirection);
                }
            }

            try {
                if ($form->isValid()) {
                    $captcha = $app['recaptcha']->bind($request);

                    if ($app['conf']->get(['registry', 'webservices', 'captcha-enabled']) && !$captcha->isValid()) {
                        throw new FormProcessingException($app->trans('Invalid captcha answer.'));
                    }

                    if ($app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
                        $selected = null;
                    } else {
                        $selected = isset($data['collections']) ? $data['collections'] : null;
                    }
                    $inscriptions = $app['registration.manager']->getRegistrationSummary();
                    $inscOK = [];

                    foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
                        foreach ($databox->get_collections() as $collection) {
                            if (null !== $selected && !in_array($collection->get_base_id(), $selected)) {
                                continue;
                            }

                            if ($canRegister = igorw\get_in($inscriptions, [$databox->get_sbas_id(), 'config', 'collections', $collection->get_base_id(), 'can-register'])) {
                                $inscOK[$collection->get_base_id()] = $canRegister;
                            }
                        }
                    }

                    if (!isset($data['login'])) {
                        $data['login'] = $data['email'];
                    }

                    $user = $app['manipulator.user']->createUser($data['login'], $data['password'], $data['email'], false);

                    if (isset($data['geonameid'])) {
                        $app['manipulator.user']->setGeonameId($user, $data['geonameid']);
                    }

                    foreach ([
                        'gender'    => 'setGender',
                        'firstname' => 'setFirstName',
                        'lastname'  => 'setLastName',
                        'address'   => 'setAddress',
                        'zipcode'   => 'setZipCode',
                        'tel'       => 'setPhone',
                        'fax'       => 'setFax',
                        'job'       => 'setJob',
                        'company'   => 'setCompany',
                        'position'  => 'setActivity',
                    ] as $property => $method) {
                        if (isset($data[$property])) {
                            call_user_func([$user, $method], $data[$property]);
                        }
                    }

                    $app['EM']->persist($user);
                    $app['EM']->flush();

                    if (null !== $provider) {
                        $this->attachProviderToUser($app['EM'], $provider, $user);
                        $app['EM']->flush();
                    }

                    $registrationsOK = [];
                    if ($app['conf']->get(['registry', 'registration', 'auto-register-enabled'])) {
                        $template_user = $app['repo.users']->findByLogin(User::USER_AUTOREGISTER);
                        $app['acl']->get($user)->apply_model($template_user, array_keys($inscOK));
                    }

                    $autoReg = $app['acl']->get($user)->get_granted_base();

                    array_walk($inscOK, function ($authorization, $baseId) use ($app, $user, &$registrationsOK) {
                        if (false === $authorization || $app['acl']->get($user)->has_access_to_base($baseId)) {
                            return;
                        }

                        $collection = \collection::get_from_base_id($app, $baseId);
                        $app['manipulator.registration']->createRegistration($user, $collection);
                        $registrationsOK[$baseId] = $collection;
                    });

                    $app['dispatcher']->dispatch(PhraseaEvents::REGISTRATION_AUTOREGISTER, new RegistrationEvent($user, $autoReg));
                    $app['dispatcher']->dispatch(PhraseaEvents::REGISTRATION_CREATE, new RegistrationEvent($user, $registrationsOK));

                    $user->setMailLocked(true);

                    try {
                        $this->sendAccountUnlockEmail($app, $user);
                        $app->addFlash('info', $app->trans('login::notification: demande de confirmation par mail envoyee'));
                    } catch (InvalidArgumentException $e) {
                        // todo, log this failure
                        $app->addFlash('error', $app->trans('Unable to send your account unlock email.'));
                    }

                    return $app->redirectPath('homepage');
                }
            } catch (FormProcessingException $e) {
                $app->addFlash('error', $e->getMessage());
            }
        } elseif (null !== $request->query->get('providerId')) {
            $provider = $this->findProvider($app, $request->query->get('providerId'));
            $identity = $provider->getIdentity();

            $form->setData(array_filter([
                'email'       => $identity->getEmail(),
                'firstname'   => $identity->getFirstname(),
                'lastname'    => $identity->getLastname(),
                'company'     => $identity->getCompany(),
                'provider-id' => $provider->getId(),
            ]));
        }

        return $app['twig']->render('login/register-classic.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            [
                'geonames_server_uri' => str_replace(sprintf('%s:', parse_url($app['geonames.server-uri'], PHP_URL_SCHEME)), '', $app['geonames.server-uri']),
                'form' => $form->createView()
        ]));
    }

    private function attachProviderToUser(EntityManager $em, ProviderInterface $provider, User $user)
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
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function sendConfirmMail(PhraseaApplication $app, Request $request)
    {
        if (null === $usrId = $request->query->get('usr_id')) {
            $app->abort(400, 'Missing usr_id parameter.');
        }

        if (null === $user = $app['repo.users']->find((int) $usrId)) {
            $app->addFlash('error', $app->trans('Invalid link.'));

            return $app->redirectPath('homepage');
        }

        try {
            $this->sendAccountUnlockEmail($app, $user);
            $app->addFlash('success', $app->trans('login::notification: demande de confirmation par mail envoyee'));
        } catch (InvalidArgumentException $e) {
            // todo, log this failure
            $app->addFlash('error', $app->trans('Unable to send your account unlock email.'));
        }

        return $app->redirectPath('homepage');
    }

    /**
     * Sends an account unlock email.
     *
     * @param PhraseaApplication $app
     * @param User               $user
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function sendAccountUnlockEmail(PhraseaApplication $app, User $user)
    {
        $receiver = Receiver::fromUser($user);

        $token = $app['manipulator.token']->createAccountUnlockToken($user);

        $mail = MailRequestEmailConfirmation::create($app, $receiver);
        $mail->setButtonUrl($app->url('login_register_confirm', ['code' => $token->getValue()]));
        $mail->setExpiration($token->getExpiration());

        $app['notification.deliverer']->deliver($mail);
    }

    /**
     * Validation of email adress
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function registerConfirm(PhraseaApplication $app, Request $request)
    {
        if (null === $code = $request->query->get('code')) {
            $app->addFlash('error', $app->trans('Invalid unlock link.'));

            return $app->redirectPath('homepage');
        }

        if (null === $token = $app['repo.tokens']->findValidToken($code)) {
            $app->addFlash('error', $app->trans('Invalid unlock link.'));

            return $app->redirectPath('homepage');
        }

        $user = $token->getUser();

        if (!$user->isMailLocked()) {
            $app->addFlash('info', $app->trans('Account is already unlocked, you can login.'));

            return $app->redirectPath('homepage');
        }

        $app['manipulator.token']->delete($token);
        $user->setMailLocked(false);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            $app->addFlash('success', $app->trans('Account has been unlocked, you can now login.'));

            return $app->redirectPath('homepage');
        }

        $app['manipulator.token']->delete($token);

        if (count($app['acl']->get($user)->get_granted_base()) > 0) {
            $mail = MailSuccessEmailConfirmationRegistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            $app->addFlash('success', $app->trans('Account has been unlocked, you can now login.'));
        } else {
            $mail = MailSuccessEmailConfirmationUnregistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            $app->addFlash('info', $app->trans('Account has been unlocked, you still have to wait for admin approval.'));
        }

        return $app->redirectPath('homepage');
    }

    public function renewPassword(PhraseaApplication $app, Request $request)
    {
        if (null === $tokenValue = $request->get('token')) {
            $app->abort(401, 'A token is required');
        }

        if (null === $token = $app['repo.tokens']->findValidToken($tokenValue)) {
            $app->abort(401, 'A token is required');
        }

        $form = $app->form(new PhraseaRecoverPasswordForm($app['repo.tokens']));
        $form->setData(['token' => $token->getValue()]);

        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                $app['manipulator.user']->setPassword($token->getUser(), $data['password']);
                $app['manipulator.token']->delete($token);
                $app->addFlash('success', $app->trans('login::notification: Mise a jour du mot de passe avec succes'));

                return $app->redirectPath('homepage');
            }
        }

        return $app['twig']->render('login/renew-password.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            ['form' => $form->createView()]
        ));
    }

    /**
     * Submit the new password
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function forgotPassword(PhraseaApplication $app, Request $request)
    {
        $form = $app->form(new PhraseaForgotPasswordForm());

        try {
            if ('POST' === $request->getMethod()) {
                $form->bind($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    if (null === $user = $app['repo.users']->findByEmail($data['email'])) {
                        throw new FormProcessingException(_('phraseanet::erreur: Le compte n\'a pas ete trouve'));
                    }

                    try {
                        $receiver = Receiver::fromUser($user);
                    } catch (InvalidArgumentException $e) {
                        throw new FormProcessingException($app->trans('Invalid email address'));
                    }

                    $token = $app['manipulator.token']->createResetPasswordToken($user);

                    $url = $app->url('login_renew_password', ['token' => $token->getValue()], true);

                    $mail = MailRequestPasswordUpdate::create($app, $receiver);
                    $mail->setLogin($user->getLogin());
                    $mail->setButtonUrl($url);

                    $app['notification.deliverer']->deliver($mail);
                    $app->addFlash('info', $app->trans('phraseanet:: Un email vient de vous etre envoye'));

                    return $app->redirectPath('login_forgot_password');
                }
            }
        } catch (FormProcessingException $e) {
            $app->addFlash('error', $e->getMessage());
        }

        return $app['twig']->render('login/forgot-password.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            [
            'form'  => $form->createView(),
        ]));
    }

    /**
     * Get the register form
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function displayRegisterForm(PhraseaApplication $app, Request $request)
    {
        if (!$app['registration.manager']->isRegistrationEnabled()) {
            $app->abort(404, 'Registration is disabled');
        }

        if (0 < count($app['authentication.providers'])) {
            return $app['twig']->render('login/register.html.twig', self::getDefaultTemplateVariables($app));
        } else {
            return $app->redirectPath('login_register_classic');
        }
    }

    /**
     * Logout from Phraseanet
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function logout(PhraseaApplication $app, Request $request)
    {
        $app['dispatcher']->dispatch(PhraseaEvents::LOGOUT, new LogoutEvent($app));
        $app['authentication']->closeAccount();

        $app->addFlash('info', $app->trans('Vous etes maintenant deconnecte. A bientot.'));

        $response = $app->redirectPath('homepage', [
            'redirect' => $request->query->get("redirect")
        ]);

        $response->headers->clearCookie('persistent');
        $response->headers->clearCookie('last_act');

        return $response;
    }

    /**
     * Login into Phraseanet
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function login(PhraseaApplication $app, Request $request)
    {
        try {
            $app['phraseanet.appbox']->get_connection();
        } catch (\Exception $e) {
            $app->addFlash('error', $app->trans('login::erreur: No available connection - Please contact sys-admin'));
        }

        $feeds = $app['repo.feeds']->findBy(['public' => true], ['updatedOn' => 'DESC']);

        $form = $app->form(new PhraseaAuthenticationForm($app));
        $form->setData([
            'redirect' => $request->query->get('redirect')
        ]);

        return $app['twig']->render('login/index.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            [
                'feeds'             => $feeds,
                'form'              => $form->createView(),
        ]));
    }

    /**
     * Authenticate to phraseanet
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function authenticate(PhraseaApplication $app, Request $request)
    {
        $form = $app->form(new PhraseaAuthenticationForm($app));
        $redirector = function (array $params = []) use ($app) {
            return $app->redirectPath('homepage', $params);
        };

        try {
            return $this->doAuthentication($app, $request, $form, $redirector);
        } catch (AuthenticationException $e) {
            return $e->getResponse();
        }
    }

    public function authenticateAsGuest(PhraseaApplication $app, Request $request)
    {
        if (!$app->isGuestAllowed()) {
            $app->abort(403, $app->trans('Phraseanet guest-access is disabled'));
        }

        $context = new Context(Context::CONTEXT_GUEST);
        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        do {
            $login = uniqid('guest');
        } while (null !== $app['repo.users']->findOneBy(['login' => $login]));

        $user = $app['manipulator.user']->createUser($login, $app['random.medium']->generateString(128));
        $invite_user = $app['repo.users']->findByLogin(User::USER_GUEST);

        $usr_base_ids = array_keys($app['acl']->get($user)->get_granted_base());
        $app['acl']->get($user)->revoke_access_from_bases($usr_base_ids);

        $invite_base_ids = array_keys($app['acl']->get($invite_user)->get_granted_base());
        $app['acl']->get($user)->apply_model($invite_user, $invite_base_ids);

        $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app, $app['browser'], $request->request->get('redirect'));
        $response->headers->setCookie(new Cookie('invite-usr-id', $user->getId()));

        $event = new PostAuthenticate($request, $response, $user, $context);
        $app['dispatcher']->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        return $response;
    }

    public function generateAuthResponse(Application $app, \Browser $browser, $redirect)
    {
        if ($browser->isMobile()) {
            $response = $app->redirectPath('lightbox');
        } elseif ($redirect) {
            $response = new RedirectResponse('../' . ltrim($redirect,'/'));
        } elseif (true !== $browser->isNewGeneration()) {
            $response = $app->redirectPath('get_client');
        } else {
            $response = $app->redirectPath('prod');
        }

        $response->headers->clearCookie('last_act');

        return $response;
    }

    // move this in an event
    public function postAuthProcess(PhraseaApplication $app, User $user)
    {
        $date = new \DateTime('+' . (int) $app['conf']->get(['registry', 'actions', 'validation-reminder-days']) . ' days');

        foreach ($app['repo.validation-participants']
            ->findNotConfirmedAndNotRemindedParticipantsByExpireDate($date) as $participant) {

            /* @var $participant ValidationParticipant */

            $validationSession = $participant->getSession();
            $basket = $validationSession->getBasket();

            if (null === $token = $app['repo.tokens']->findValidationToken($basket, $participant->getUser())) {
                continue;
            }

            $url = $app->url('lightbox_validation', ['basket' => $basket->getId(), 'LOG' => $token->getValue()]);
            $app['dispatcher']->dispatch(PhraseaEvents::VALIDATION_REMINDER, new ValidationEvent($participant, $basket, $url));

            $participant->setReminded(new \DateTime('now'));
            $app['EM']->persist($participant);
        }

        $app['EM']->flush();

        $session = $app['authentication']->openAccount($user);

        if ($user->getLocale() != $app['locale']) {
            $user->setLocale($app['locale']);
        }

        $width = $height = null;
        if ($app['request']->cookies->has('screen')) {
            $data = explode('x', $app['request']->cookies->get('screen'));
            $width = $data[0];
            $height = $data[1];
        }
        $session->setIpAddress($app['request']->getClientIp())
            ->setScreenHeight($height)
            ->setScreenWidth($width);

        $app['EM']->persist($session);
        $app['EM']->flush();

        return $session;
    }

    public function authenticateWithProvider(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        return $provider->authenticate($request->query->all());
    }

    public function authenticationCallback(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        // triggers what's necessary
        try {
            $provider->onCallback($request);
            $token = $provider->getToken();
        } catch (NotAuthenticatedException $e) {
            $app['session']->getFlashBag()->add('error', $app->trans('Unable to authenticate with %provider_name%', ['%provider_name%' => $provider->getName()]));

            return $app->redirectPath('homepage');
        }

        $userAuthProvider = $app['repo.usr-auth-providers']
            ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

        if (null !== $userAuthProvider) {
            $this->postAuthProcess($app, $userAuthProvider->getUser());

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $app->path('prod');
            }

            return $app->redirect($redirection);
        }

        try {
            $user = $app['authentication.suggestion-finder']->find($token);
        } catch (NotAuthenticatedException $e) {
            $app->addFlash('error', $app->trans('Unable to retrieve provider identity'));

            return $app->redirectPath('homepage');
        }

        if (null !== $user) {
            $this->attachProviderToUser($app['EM'], $provider, $user);
            $app['EM']->flush();

            $this->postAuthProcess($app, $user);

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $app->path('prod');
            }

            return $app->redirect($redirection);
        }

        if ($app['authentication.providers.account-creator']->isEnabled()) {
            $user = $app['authentication.providers.account-creator']->create($app, $token->getId(), $token->getIdentity()->getEmail(), $token->getTemplates());

            $this->attachProviderToUser($app['EM'], $provider, $user);
            $app['EM']->flush();

            $this->postAuthProcess($app, $user);

            if (null !== $redirect = $request->query->get('redirect')) {
                $redirection = '../' . $redirect;
            } else {
                $redirection = $app->path('prod');
            }

            return $app->redirect($redirection);
        } elseif ($app['registration.manager']->isRegistrationEnabled()) {
            return $app->redirectPath('login_register_classic', ['providerId' => $providerId]);
        }

        $app->addFlash('error', $app->trans('Your identity is not recognized.'));

        return $app->redirectPath('homepage');
    }

    /**
     *
     * @param  PhraseaApplication    $app
     * @param  string                $providerId
     * @return ProviderInterface
     * @throws NotFoundHttpException
     */
    private function findProvider(PhraseaApplication $app, $providerId)
    {
        try {
            return $app['authentication.providers']->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }
    }

    private function doAuthentication(PhraseaApplication $app, Request $request, FormInterface $form, $redirector)
    {
        if (!is_callable($redirector)) {
            throw new InvalidArgumentException('Redirector should be callable');
        }

        $context = new Context(Context::CONTEXT_NATIVE);
        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        $form->bind($request);
        if (!$form->isValid()) {
            $app->addFlash('error', $app->trans('An unexpected error occured during authentication process, please contact an admin'));

            throw new AuthenticationException(call_user_func($redirector));
        }

        $params = [];

        if (null !== $redirect = $request->get('redirect')) {
            $params['redirect'] = ltrim($redirect, '/');
        }

        try {
            $usr_id = $app['auth.native']->getUsrId($request->request->get('login'), $request->request->get('password'), $request);
        } catch (RequireCaptchaException $e) {
            $app->requireCaptcha();
            $app->addFlash('warning', $app->trans('Please fill the captcha'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        } catch (AccountLockedException $e) {
            $app->addFlash('warning', $app->trans('login::erreur: Vous n\'avez pas confirme votre email'));
            $app->addUnlockAccountData($e->getUsrId());

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        if (null === $usr_id) {
            $app['session']->getFlashBag()->set('error', $app->trans('login::erreur: Erreur d\'authentification'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        $user = $app['repo.users']->find($usr_id);

        $session = $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app, $app['browser'], $request->request->get('redirect'));
        $response->headers->clearCookie('invite-usr-id');

        if ($request->request->get('remember-me') == '1') {
            $nonce = $app['random.medium']->generateString(64);
            $string = $app['browser']->getBrowser() . '_' . $app['browser']->getPlatform();

            $token = $app['auth.password-encoder']->encodePassword($string, $nonce);

            $session->setToken($token)->setNonce($nonce);

            $response->headers->setCookie(new Cookie('persistent', $token, time() + $app['phraseanet.configuration']['session']['lifetime']));

            $app['EM']->persist($session);
            $app['EM']->flush();
        }

        $event = new PostAuthenticate($request, $response, $user, $context);
        $app['dispatcher']->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        return $event->getResponse();
    }
}
