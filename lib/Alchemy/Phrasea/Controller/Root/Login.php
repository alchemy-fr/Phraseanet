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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Exception\AuthenticationException;
use Alchemy\Phrasea\Authentication\Context;
use Alchemy\Phrasea\Authentication\Exception\RecoveryException;
use Alchemy\Phrasea\Authentication\Exception\RegistrationException;
use Alchemy\Phrasea\Authentication\Provider\ProviderInterface;
use Alchemy\Phrasea\Authentication\RecoveryService;
use Alchemy\Phrasea\Authentication\RegistrationService;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\FormProcessingException;
use Alchemy\Phrasea\Exception\RuntimeException;
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
use Entities\UsrAuthProvider;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;

class Login implements ControllerProviderInterface
{
    public static function getDefaultTemplateVariables(Application $app)
    {
        $items = array();

        foreach (\Feed_Entry_Item::loadLatest($app, 20) as $item) {
            $record = $item->get_record();
            $preview = $record->get_subdef('preview');
            $permalink = $preview->get_permalink();

            $items[] = array(
                'record' => $record,
                'preview' => $preview,
                'permalink' => $permalink
            );
        }

        return array(
            'last_publication_items' => $items,
            'instance_title' => $app['phraseanet.registry']->get('GV_homeTitle'),
            'has_terms_of_use' => $app->hasTermsOfUse(),
            'meta_description' =>  $app['phraseanet.registry']->get('GV_metaDescription'),
            'meta_keywords' => $app['phraseanet.registry']->get('GV_metakeywords'),
            'browser_name' => $app['browser']->getBrowser(),
            'browser_version' => $app['browser']->getVersion(),
            'available_language' => $app['locales.available'],
            'locale' => $app['locale'],
            'current_url' => $app['request']->getUri(),
            'flash_types' => $app->getAvailableFlashTypes(),
            'recaptcha_display' => $app->isCaptchaRequired(),
            'unlock_usr_id' => $app->getUnlockAccountData(),
            'guest_allowed' => $app->isGuestAllowed(),
            'register_enable' => $app['registration.enabled'],
            'display_layout' => $app['phraseanet.registry']->get('GV_home_publi'),
            'authentication_providers' => $app['authentication.providers'],
            'registration_fields' => $app['registration.fields'],
            'registration_optional_fields' => $app['registration.optional-fields']
        );
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
                array('cgus' => \databox_cgu::getHome($app)),
                self::getDefaultTemplateVariables($app)
            ));
        })->bind('login_cgus');

        $controllers->get('/language.json', 'login.controller:getLanguage')
            ->bind('login_language');

        return $controllers;
    }

    public function getLanguage(Application $app, Request $request)
    {
        $response =  $app->json(array(
            'validation_blank'          => _('Please provide a value.'),
            'validation_choice_min'     => _('Please select at least %s choice.'),
            'validation_email'          => _('Please provide a valid email address.'),
            'validation_ip'             => _('Please provide a valid IP address.'),
            'validation_length_min'     => _('Please provide a longer value. It should have %s character or more.'),
            'password_match'            => _('Please provide the same passwords.'),
            'email_match'               => _('Please provide the same emails.'),
            'accept_tou'                => _('Please accept the terms of use to register.'),
            'no_collection_selected'    => _('No collection selected'),
            'one_collection_selected'   => _('%d collection selected'),
            'collections_selected'      => _('%d collections selected'),
            'all_collections'           => _('Select all collections'),
            // password strength
            'weak'                      => _('Weak'),
            'ordinary'                  => _('Ordinary'),
            'good'                      => _('Good'),
            'great'                     => _('Great'),
        ));

        $response->setExpires(new \DateTime('+1 day'));

        return $response;
    }

    public function doRegistration(PhraseaApplication $app, Request $request)
    {
        if (!$app['registration.enabled']) {
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

            $registrationService = $app['authentication.registration_service'];

            if ($data['provider-id']) {
                try {
                    $user = $registrationService->registerOauthUser($data['provider-id']);

                    if ($user !== null) {
                        $this->postAuthProcess($app, $user);

                        if (null !== $redirect = $request->query->get('redirect')) {
                            $redirection = '../' . $redirect;
                        } else {
                            $redirection = $app->path('prod');
                        }

                        return $app->redirect($redirection);
                    }
                } catch (InvalidArgumentException $e) {
                    $app->addFlash('error', _('You tried to register with an unknown provider'));

                    return $app->redirectPath('login_register');
                } catch (NotAuthenticatedException $e) {
                    $app->addFlash('error', _('You tried to register with an unknown provider'));

                    return $app->redirectPath('login_register');
                }
            }

            try {
                if ($form->isValid()) {
                    $captcha = $app['recaptcha']->bind($request);

                    if ($app['phraseanet.registry']->get('GV_captchas') && !$captcha->isValid()) {
                        throw new FormProcessingException(_('Invalid captcha answer.'));
                    }

                    if ($app['phraseanet.registry']->get('GV_autoselectDB')) {
                        $selected = null;
                    } else {
                        $selected = isset($data['collections']) ? $data['collections'] : null;
                    }

                    $user = $registrationService->registerUser(
                        $data,
                        $selected,
                        isset($data['provider-id']) ? $data['provider-id'] : null
                    );

                    try {
                        $this->sendAccountUnlockEmail($app, $user);
                        $app->addFlash('info', _('login::notification: demande de confirmation par mail envoyee'));
                    } catch (InvalidArgumentException $e) {
                        // todo, log this failure
                        $app->addFlash('error', _('Unable to send your account unlock email.'));
                    }

                    return $app->redirectPath('homepage');
                }
            } catch (FormProcessingException $e) {
                $app->addFlash('error', $e->getMessage());
            }
        } elseif (null !== $request->query->get('providerId')) {
            $provider = $this->findProvider($app, $request->query->get('providerId'));
            $identity = $provider->getIdentity();

            $form->setData(array_filter(array(
                'email'       => $identity->getEmail(),
                'firstname'   => $identity->getFirstname(),
                'lastname'    => $identity->getLastname(),
                'company'     => $identity->getCompany(),
                'provider-id' => $provider->getId(),
            )));
        }

        return $app['twig']->render('login/register-classic.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            array(
                'geonames_server_uri' => str_replace(sprintf('%s:', parse_url($app['geonames.server-uri'], PHP_URL_SCHEME)), '', $app['geonames.server-uri']),
                'form' => $form->createView()
        )));
    }

    private function attachProviderToUser(EntityManager $em, ProviderInterface $provider, \User_Adapter $user)
    {
        $usrAuthProvider = new UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUsrId($user->get_id());

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

        try {
            $user = \User_Adapter::getInstance((int) $usrId, $app);
        } catch (\Exception $e) {
            $app->addFlash('error', _('Invalid link.'));

            return $app->redirectPath('homepage');
        }

        try {
            $this->sendAccountUnlockEmail($app, $user);
            $app->addFlash('success', _('login::notification: demande de confirmation par mail envoyee'));
        } catch (InvalidArgumentException $e) {
            // todo, log this failure
            $app->addFlash('error', _('Unable to send your account unlock email.'));
        }

        return $app->redirectPath('homepage');
    }

    /**
     * Sends an account unlock email.
     *
     * @param PhraseaApplication $app
     * @param \User_Adapter      $user
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private function sendAccountUnlockEmail(PhraseaApplication $app, \User_Adapter $user)
    {
        $receiver = Receiver::fromUser($user);

        $expire = new \DateTime('+3 days');
        $token = $app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), $expire, $user->get_email());

        $mail = MailRequestEmailConfirmation::create($app, $receiver);
        $mail->setButtonUrl($app->url('login_register_confirm', array('code' => $token)));
        $mail->setExpiration($expire);

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
            $app->addFlash('error', _('Invalid unlock link.'));

            return $app->redirectPath('homepage');
        }

        /** @var RegistrationService $service */
        $service = $app['authentication.registration_service'];

        try {
            $user = $service->unlockAccount($code);
        } catch (NotFoundHttpException $e) {
            $app->addFlash('error', _('Invalid unlock link.'));

            return $app->redirectPath('homepage');
        } catch (RegistrationException $e) {
            if ($e->getCode() == RegistrationException::ACCOUNT_ALREADY_UNLOCKED) {
                $app->addFlash('info', _($e->getMessage()));
            }
            else {
                $app->addFlash('error', _($e->getMessage()));
            }

            return $app->redirectPath('homepage');
        } catch (\Exception $e) {
            $app->addFlash('error', _('Invalid unlock link.'));

            return $app->redirectPath('homepage');
        }

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            $app->addFlash('success', _('Account has been unlocked, you can now login.'));

            return $app->redirectPath('homepage');
        }

        $app['tokens']->removeToken($code);

        if (count($user->ACL()->get_granted_base()) > 0) {
            $mail = MailSuccessEmailConfirmationRegistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            $app->addFlash('success', _('Account has been unlocked, you can now login.'));
        } else {
            $mail = MailSuccessEmailConfirmationUnregistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            $app->addFlash('info', _('Account has been unlocked, you still have to wait for admin approval.'));
        }

        return $app->redirectPath('homepage');
    }

    public function renewPassword(PhraseaApplication $app, Request $request)
    {
        if (null === $token = $request->get('token')) {
            $app->abort(401, 'A token is required');
        }

        try {
            $app['tokens']->helloToken($token);
        } catch (\Exception $e) {
            $app->abort(401, 'A token is required');
        }

        $form = $app->form(new PhraseaRecoverPasswordForm($app));
        $form->setData(array('token' => $token));

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $service = $app['authentication.recovery_service'];

                try {
                    $service->resetPassword($token, $data['password']);
                    $app->addFlash('success', _('login::notification: Mise a jour du mot de passe avec succes'));

                    return $app->redirectPath('homepage');
                }
                catch (RecoveryException $e) {
                    $app->addFlash('error', _($e->getMessage()));
                }
            }
        }

        return $app['twig']->render('login/renew-password.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            array('form' => $form->createView())
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

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $service = $app['authentication.recovery_service'];

                try {
                    $service->requestPasswordResetToken($data['email'], true);
                }
                catch (RecoveryException $e) {
                    throw new HttpException(500, 'Unable to generate a token');
                }
                catch (InvalidArgumentException $e) {
                    $app->addFlash('error', _($e->getMessage()));
                }

                return $app->redirectPath('login_forgot_password');
            }
        }

        return $app['twig']->render('login/forgot-password.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            array('form'  => $form->createView())
        ));
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
        if (!$app['registration.enabled']) {
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

        $app->addFlash('info', _('Vous etes maintenant deconnecte. A bientot.'));

        $response = $app->redirectPath('homepage', array(
            'redirect' => $request->query->get("redirect")
        ));

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
        require_once $app['root.path'] . '/lib/classes/deprecated/inscript.api.php';

        try {
            $app['phraseanet.appbox']->get_connection();
        } catch (\Exception $e) {
            $app->addFlash('error', _('login::erreur: No available connection - Please contact sys-admin'));
        }

        $public_feeds = \Feed_Collection::load_public_feeds($app);

        $feeds = $public_feeds->get_feeds();
        array_unshift($feeds, $public_feeds->get_aggregate());

        $form = $app->form(new PhraseaAuthenticationForm($app));
        $form->setData(array(
            'redirect' => $request->query->get('redirect')
        ));

        return $app['twig']->render('login/index.html.twig', array_merge(
            self::getDefaultTemplateVariables($app),
            array(
                'feeds'             => $feeds,
                'form'              => $form->createView(),
        )));
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
        $redirector = function (array $params = array()) use ($app) {
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
            $app->abort(403, _('Phraseanet guest-access is disabled'));
        }

        $context = new Context(Context::CONTEXT_GUEST);
        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request, $context));

        $password = \random::generatePassword(24);
        $user = \User_Adapter::create($app, 'invite', $password, null, false, true);

        $inviteUsrid = \User_Adapter::get_usr_id_from_login($app, 'invite');
        $invite_user = \User_Adapter::getInstance($inviteUsrid, $app);

        $usr_base_ids = array_keys($user->ACL()->get_granted_base());
        $user->ACL()->revoke_access_from_bases($usr_base_ids);

        $invite_base_ids = array_keys($invite_user->ACL()->get_granted_base());
        $user->ACL()->apply_model($invite_user, $invite_base_ids);

        $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app, $app['browser'], $request->request->get('redirect'));
        $response->headers->setCookie(new Cookie('invite-usr-id', $user->get_id()));

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
    public function postAuthProcess(PhraseaApplication $app, \User_Adapter $user)
    {
        // ------------------------------------------------------
        // here we send validation reminds
        // we remind participants who :
        // - have not yet validated a session
        // - and where the session expiration date is in 2 days (setting)
        // - and who have not already been reminded
        //
        // compare with 4.x :
        // - almost the same code :
        //   - find all participants to all sessions
        //   - send link with original 'validate' token
        // except here (3.8), the 'validate' token has no expiration
        //
        // there is probably a bug :
        // since a validation request can be initiated WITHOUT a 'validate' token (to force the user to auth)
        // the token will not be found, so the user will never be reminded.
        //  see Push.php ... post('/validate/')
        //
        $date = new \DateTime('+' . (int) $app['phraseanet.registry']->get('GV_validation_reminder') . ' days');

        /** @var \Repositories\ValidationParticipantRepository $repo */
        $repo = $app['EM']->getRepository('Entities\ValidationParticipant');

        foreach ($repo->findNotConfirmedAndNotRemindedParticipantsByExpireDate($date) as $participant) {

            /* @var $participant \Entities\ValidationParticipant */

            $validationSession = $participant->getSession();
            $participantId = $participant->getUsrId();
            $basketId = $validationSession->getBasket()->getId();

            try {
                /** @var \random $random */
                $random = $app['tokens'];
                $token = $random->getValidationToken($participantId, $basketId);
            }
            catch (NotFoundHttpException $e) {
                continue;
            }

            $app['events-manager']->trigger('__VALIDATION_REMINDER__', array(
                'to'          => $participantId,
                'ssel_id'     => $basketId,
                'from'        => $validationSession->getInitiatorId(),
                'validate_id' => $validationSession->getId(),
                'url'         => $app->url('lightbox_validation', array('ssel_id' => $basketId, 'LOG' => $token)),
            ));

            $participant->setReminded(new \DateTime('now'));
            $app['EM']->persist($participant);
        }

        $app['EM']->flush();

        // end of validation reminds
        // -----------------------------------------------------------

        $session = $app['authentication']->openAccount($user);

        if ($user->get_locale() != $app['locale']) {
            $user->set_locale($app['locale']);
        }

        $width = $height = null;
        if ($app['request']->cookies->has('screen')) {
            $data = array_filter((explode('x', $app['request']->cookies->get('screen', ''))));
            if (count($data) === 2) {
                $width = $data[0];
                $height = $data[1];
            }
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
            $app['session']->getFlashBag()->add('error', sprintf(_('Unable to authenticate with %s'), $provider->getName()));

            return $app->redirectPath('homepage');
        }

        $userAuthProvider = $app['EM']
            ->getRepository('Entities\UsrAuthProvider')
            ->findWithProviderAndId($token->getProvider()->getId(), $token->getId());

        if (null !== $userAuthProvider) {
            $this->postAuthProcess($app, $userAuthProvider->getUser($app));

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
            $app->addFlash('error', _('Unable to retrieve provider identity'));

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
        } elseif ($app['registration.enabled']) {
            return $app->redirectPath('login_register_classic', array('providerId' => $providerId));
        }

        $app->addFlash('error', _('Your identity is not recognized.'));

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
            $app->addFlash('error', _('An unexpected error occured during authentication process, please contact an admin'));

            throw new AuthenticationException(call_user_func($redirector));
        }

        $params = array();

        if (null !== $redirect = $request->get('redirect')) {
            $params['redirect'] = ltrim($redirect, '/');
        }

        try {
            $usr_id = $app['auth.native']->getUsrId($request->request->get('login'), $request->request->get('password'), $request);
        } catch (RequireCaptchaException $e) {
            $app->requireCaptcha();
            $app->addFlash('warning', _('Please fill the captcha'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        } catch (AccountLockedException $e) {
            $app->addFlash('warning', _('login::erreur: Vous n\'avez pas confirme votre email'));
            $app->addUnlockAccountData($e->getUsrId());

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        if (null === $usr_id) {
            $app['session']->getFlashBag()->set('error', _('login::erreur: Erreur d\'authentification'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        $user = \User_Adapter::getInstance($usr_id, $app);

        $session = $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app, $app['browser'], $request->request->get('redirect'));
        $response->headers->clearCookie('invite-usr-id');

        if ($request->request->get('remember-me') == '1') {
            $nonce = \random::generatePassword(16);
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
