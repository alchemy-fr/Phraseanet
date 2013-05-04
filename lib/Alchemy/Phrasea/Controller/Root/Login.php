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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Authentication\Exception\NotAuthenticatedException;
use Alchemy\Phrasea\Authentication\Exception\AuthenticationException;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\FormProcessingException;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordUpdate;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationWithMappingForm;
use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRecoverPasswordForm;
use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;
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
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['login.controller'] = $this;

        $controllers->before(function(Request $request) use ($app) {
            if ($request->getPathInfo() == $app->path('homepage')) {
                return;
            }
            if ($app['phraseanet.registry']->get('GV_maintenance')) {
                $app->addFlash('warning', _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee'));

                return $app->redirect($app->path('homepage', array(
                    'redirect' => ltrim($request->get('redirect'), '/'),
                )));
            }
        });

        // Displays the homepage
        $controllers->get('/', 'login.controller:login')
            ->before(function(Request $request) use ($app) {
                    $app['firewall']->requireNotAuthenticated();

                    if (null !== $request->query->get('postlog')) {

                        // if isset postlog parameter, set cookie and log out current user
                        // then post login operation like getting baskets from an invit session
                        // could be done by Session_handler authentication process

                        $response = new RedirectResponse("/login/logout/?redirect=" . ltrim($request->query->get('redirect', 'prod'), '/'));
                        $response->headers->setCookie(new Cookie('postlog', 1));

                        return $response;
                    }
                })
            ->bind('homepage');

        // Authentication end point
        $controllers->post('/authenticate/', 'login.controller:authenticate')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })
            ->bind('login_authenticate');

        // Guest access end point
        $controllers->match('/authenticate/guest/', 'login.controller:authenticateAsGuest')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })
            ->bind('login_authenticate_as_guest')
            ->method('GET|POST');

        // Authenticate with an AuthProvider
        $controllers->get('/provider/{providerId}/authenticate/', 'login.controller:authenticateWithProvider')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })
            ->bind('login_authentication_provider_authenticate');

        // AuthProviders callbacks
        $controllers->get('/provider/{providerId}/callback/', 'login.controller:authenticationCallback')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_callback');

        // Displays a form to add a mapping from an AuthProvider identity to a Phraseanet user that has been found
        $controllers->get('/provider/{providerId}/add-mapping/', 'login.controller:authenticationMapping')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_mapping');

        // Submit the mapping from an AuthProvider identity to a Phraseanet user that has been found
        $controllers->post('/provider/{providerId}/add-mapping/', 'login.controller:authenticationDoMapToAccount')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_do_mapping');

        // Displays a form to bind an AuthProvider identity to an account or create one
        $controllers->get('/provider/{providerId}/bind-account/', 'login.controller:authenticationBindToAccount')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_bind');

        // Submits the form to bind an AuthProvider identity to an account or create one
        $controllers->post('/provider/{providerId}/bind-account/', 'login.controller:authenticationDoBindToAccount')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_do_bind');

        // Logout end point
        $controllers->get('/logout/', 'login.controller:logout')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireAuthentication();
            })->bind('logout');

        // Registration end point ; redirects to classic registration or AuthProvider registration
        $controllers->get('/register/', 'login.controller:displayRegisterForm')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_register');

        // Classic registration end point
        $controllers->match('/register-classic', 'login.controller:doRegistration')
            ->bind('login_register_classic');

        // Provide a JSON serialization of registration fields configuration
        $controllers->get('/registration-fields', function(PhraseaApplication $app, Request $request) {
            return $app->json($app['registration.fields']);
        })->bind('login_registration_fields');

        // Registers with AuthProviders
        $controllers->get('/register-provider', function(PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/register-provider.html.twig');
        })->bind('login_register_provider');

        // Unlocks an email address that is currently locked
        $controllers->get('/register-confirm/', 'login.controller:registerConfirm')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_register_confirm');

        // Displays a form to send an account unlock email again
        $controllers->get('/send-mail-confirm/', 'login.controller:sendConfirmMail')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_send_mail');

        // Forgot password end point
        $controllers->match('/forgot-password/', 'login.controller:forgotPassword')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_forgot_password');

        // Renew password end point
        $controllers->match('/renew-password/', 'login.controller:renewPassword')
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_renew_password');

        // Displays Terms of use
        $controllers->get('/cgus', function(PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/cgus.html.twig');
        })->bind('login_cgus');

        return $controllers;
    }

    public function doRegistration(PhraseaApplication $app, Request $request)
    {
        $form = $app->form(new PhraseaRegisterForm(
            $app, $app['registration.optional-fields'], $app['registration.fields']
        ));

        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            $data = $form->getData();

            try {
                if ($form->isValid()) {
                    if ($data['password'] !== $data['passwordConfirm']) {
                        throw new FormProcessingException(_('Password mismatch.'));
                    }

                    $captcha = $app['recaptcha']->bind($request);

                    if ($app['phraseanet.registry']->get('GV_captchas') && !$captcha->isValid()) {
                        throw new FormProcessingException(_('Invalid captcha answer.'));
                    }

                    require_once($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

                    $selected = isset($data['collections']) ? $data['collections'] : null;
                    $inscriptions = giveMeBases($app);
                    $inscOK = array();

                    foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

                        foreach ($databox->get_collections() as $collection) {
                            if (null !== $selected && !in_array($collection->get_base_id(), $selected)) {
                                continue;
                            }

                            $sbas_id = $databox->get_sbas_id();

                            if (isset($inscriptions[$sbas_id])
                                && $inscriptions[$sbas_id]['inscript'] === true
                                && (isset($inscriptions[$sbas_id]['Colls'][$collection->get_coll_id()])
                                || isset($inscriptions[$sbas_id]['CollsCGU'][$collection->get_coll_id()]))) {
                                $inscOK[$collection->get_base_id()] = true;
                            } else {
                                $inscOK[$collection->get_base_id()] = false;
                            }
                        }
                    }

                    if (!isset($data['login'])) {
                        $data['login'] = $data['email'];
                    }

                    $user = \User_Adapter::create($app, $data['login'], $data['password'], $data['email'], false);

                    foreach (array(
                        'gender'    => 'set_gender',
                        'firstname' => 'set_firstname',
                        'lastname'  => 'set_lastname',
                        'address'   => 'set_address',
                        'zipcode'   => 'set_zip',
                        'tel'       => 'set_tel',
                        'fax'       => 'set_fax',
                        'job'       => 'set_job',
                        'company'   => 'set_company',
                        'position'  => 'set_position',
                        'geonameid' => 'set_geonameid',
                    ) as $property => $method) {
                        if (isset($data[$property])) {
                            call_user_func(array($user, $method), $data[$property]);
                        }
                    }

                    $demandOK = array();

                    if ($app['phraseanet.registry']->get('GV_autoregister')) {

                        $template_user_id = \User_Adapter::get_usr_id_from_login($app, 'autoregister');

                        $template_user = \User_Adapter::getInstance($template_user_id, $app);

                        $base_ids = array();

                        foreach (array_keys($inscOK) as $base_id) {
                            $base_ids[] = $base_id;
                        }
                        $user->ACL()->apply_model($template_user, $base_ids);
                    }

                    $autoReg = $user->ACL()->get_granted_base();

                    $appbox_register = new \appbox_register($app['phraseanet.appbox']);

                    foreach ($selected as $base_id) {
                        if (false === $inscOK[$base_id] || $user->ACL()->has_access_to_base($base_id)) {
                            continue;
                        }

                        $collection = \collection::get_from_base_id($app, $base_id);
                        $appbox_register->add_request($user, $collection);
                        $demandOK[$base_id] = true;
                    }

                    $params = array(
                        'demand'       => $demandOK,
                        'autoregister' => $autoReg,
                        'usr_id'       => $user->get_id()
                    );

                    $app['events-manager']->trigger('__REGISTER_AUTOREGISTER__', $params);
                    $app['events-manager']->trigger('__REGISTER_APPROVAL__', $params);

                    $user->set_mail_locked(true);

                    try {
                        $this->sendAccountUnlockEmail($app, $user);
                        $app->addFlash('info', _('login::notification: demande de confirmation par mail envoyee'));
                    } catch (InvalidArgumentException $e) {
                        // todo, log this failure
                        $app->addFlash('error', _('Unable to send your account unlock email.'));
                    }

                    return $app->redirect($app->path('homepage'));
                }
            } catch (FormProcessingException $e) {
                $app->addFlash('error', $e->getMessage());
            }
        }

        return $app['twig']->render('login/register-classic.html.twig', array(
            'form' => $form->createView(),
            'home_title' => $app['phraseanet.registry']->get('GV_homeTitle'),
            'login' => new \login(),
            'recaptcha_display' => $app['phraseanet.registry']->get('GV_captchas'),
        ));
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

            return $app->redirect($app->path('homepage'));
        }

        try {
            $this->sendAccountUnlockEmail($app, $user);
            $app->addFlash('success', _('login::notification: demande de confirmation par mail envoyee'));
        } catch (InvalidArgumentException $e) {
            // todo, log this failure
            $app->addFlash('error', _('Unable to send your account unlock email.'));
        }

        return $app->redirect($app->path('homepage'));
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

            return $app->redirect($app->path('homepage'));
        }

        try {
            $datas = $app['tokens']->helloToken($code);
        } catch (\Exception_NotFound $e) {
            $app->addFlash('error', _('Invalid unlock link.'));

            return $app->redirect($app->path('homepage'));
        }

        try {
            $user = \User_Adapter::getInstance((int) $datas['usr_id'], $app);
        } catch (\Exception $e) {
            $app->addFlash('error', _('Invalid unlock link.'));

            return $app->redirect($app->path('homepage'));
        }

        if (!$user->get_mail_locked()) {
            $app->addFlash('info', _('Account is already unlocked, you can login.'));

            return $app->redirect($app->path('homepage'));
        }

        $app['tokens']->removeToken($code);
        $user->set_mail_locked(false);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            $app->addFlash('success', _('Account has been unlocked, you can now login.'));

            return $app->redirect($app->path('homepage'));
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

        return $app->redirect($app->path('homepage'));
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
            try {
                if ($form->isValid()) {
                    $data = $form->getData();

                    $password = $data['password'];
                    $passwordConfirm = $data['passwordConfirm'];

                    if ($password !== $passwordConfirm) {
                        throw new FormProcessingException(_('forms::les mots de passe ne correspondent pas'));
                    }

                    $datas = $app['tokens']->helloToken($token);

                    $user = \User_Adapter::getInstance($datas['usr_id'], $app);
                    $user->set_password($passwordConfirm);

                    $app['tokens']->removeToken($token);

                    $app->addFlash('success', _('login::notification: Mise a jour du mot de passe avec succes'));

                    return $app->redirect($app->path('homepage'));
                }
            } catch (FormProcessingException $e) {
                $app->addFlash('error', $e->getMessage());
            }
        }

        return $app['twig']->render('login/renew-password.html.twig', array(
            'login'       => new \login(),
            'form'        => $form->createView(),
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

                    try {
                        $user = \User_Adapter::getInstance(\User_Adapter::get_usr_id_from_email($app, $data['email']), $app);
                    } catch (\Exception $e) {
                        throw new FormProcessingException(_('phraseanet::erreur: Le compte n\'a pas ete trouve'));
                    }

                    try {
                        $receiver = Receiver::fromUser($user);
                    } catch (InvalidArgumentException $e) {
                        throw new FormProcessingException(_('Invalid email address'));
                    }

                    $token = $app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), new \DateTime('+1 day'));

                    if (!$token) {
                        return $app->abort(500, 'Unable to generate a token');
                    }

                    $url = $app->url('login_renew_password', array('token' => $token), true);

                    $mail = MailRequestPasswordUpdate::create($app, $receiver);
                    $mail->setButtonUrl($url);

                    $app['notification.deliverer']->deliver($mail);
                    $app->addFlash('info', _('phraseanet:: Un email vient de vous etre envoye'));

                    return $app->redirect($app->path('login_forgot_password'));
                }
            }
        } catch (FormProcessingException $e) {
            $app->addFlash('error', $e->getMessage());
        }

        return $app['twig']->render('login/forgot-password.html.twig', array(
            'login'       => new \login(),
            'form'        => $form->createView(),
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
        if (0 < count($app['authentication.providers'])) {
            //neutron a verifier
            return $app['twig']->render('login/register.html.twig', array(
                'login' => new \login(),
            ));
        } else {
            return $app->redirect($app->path('login_register_classic'));
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

        $app->addFlash('info', 'Vous etes maintenant deconnecte. A bientot.');

        $response = new RedirectResponse($app->path('root', array(
            'redirect' => $request->query->get("redirect")
        )));

        $response->headers->removeCookie('persistent');
        $response->headers->removeCookie('last_act');
        $response->headers->removeCookie('postlog');

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
        require_once($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

        try {
            $app['phraseanet.appbox']->get_connection();
        } catch (\Exception $e) {
            $app->addFlash('error', _('login::erreur: No available connection - Please contact sys-admin'));
        }

        $public_feeds = \Feed_Collection::load_public_feeds($app);

        $feeds = $public_feeds->get_feeds();
        array_unshift($feeds, $public_feeds->get_aggregate());

        $form = $app->form(new PhraseaAuthenticationForm(), null, array(
            'disabled' => $app['phraseanet.registry']->get('GV_maintenance')
        ));

        return $app['twig']->render('login/index.html.twig', array(
            'module_name'       => _('Accueil'),
            'redirect'          => ltrim($request->query->get('redirect'), '/'),
            'recaptcha_display' => $app->isCaptchaRequired(),
            'unlock_usr_id'     => $app->getUnlockLink(),
            'login'             => new \login(),
            'feeds'             => $feeds,
            'guest_allowed'     => \phrasea::guest_allowed($app),
            'form'              => $form->createView(),
        ));
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
        $form = $app->form(new PhraseaAuthenticationForm());
        $redirector = function (array $params = array()) use ($app) {
            return $app->redirect($app->path('homepage', $params));
        };

        try {
            return $this->doAuthentication($app, $request, $form, $redirector);
        } catch (AuthenticationException $e) {
            return $e->getResponse();
        }
    }

    public function authenticateAsGuest(PhraseaApplication $app, Request $request)
    {
        if (!\phrasea::guest_allowed($app)) {
            $app->abort(403, _('Phraseanet guest-access is disabled'));
        }

        $password = \random::generatePassword(24);
        $user = \User_Adapter::create($app, 'invite', $password, null, false, true);

        $inviteUsrid = \User_Adapter::get_usr_id_from_login($app, 'invite');
        $invite_user = \User_Adapter::getInstance($inviteUsrid, $app);

        $usr_base_ids = array_keys($user->ACL()->get_granted_base());
        $user->ACL()->revoke_access_from_bases($usr_base_ids);

        $invite_base_ids = array_keys($invite_user->ACL()->get_granted_base());
        $user->ACL()->apply_model($invite_user, $invite_base_ids);

        $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app['browser'], $request->request->get('redirect'));
        $response->headers->setCookie(new Cookie('invite-usr-id', $user->get_id()));

        return $response;
    }

    private function generateAuthResponse(\Browser $browser, $redirect)
    {
        if ($browser->isMobile()) {
            $response = new RedirectResponse("/lightbox/");
        } elseif ($redirect) {
            $response = new RedirectResponse('/' . ltrim($redirect,'/'));
        } elseif (true !== $browser->isNewGeneration()) {
            $response = new RedirectResponse('/client/');
        } else {
            $response = new RedirectResponse('/prod/');
        }

        $response->headers->removeCookie('postlog');
        $response->headers->removeCookie('last_act');

        return $response;
    }

    // move this in an event
    private function postAuthProcess(PhraseaApplication $app, \User_Adapter $user)
    {
        $date = new \DateTime('+' . (int) $app['phraseanet.registry']->get('GV_validation_reminder') . ' days');

        foreach ($app['EM']
            ->getRepository('\Entities\ValidationParticipant')
            ->findNotConfirmedAndNotRemindedParticipantsByExpireDate($date) as $participant) {

            /* @var $participant \Entities\ValidationParticipant */

            $validationSession = $participant->getSession();
            $participantId = $participant->getUsrId();
            $basketId = $validationSession->getBasket()->getId();

            try {
                $token = $this->app['tokens']->getValidationToken($participantId, $basketId);
            } catch (\Exception_NotFound $e) {
                continue;
            }

            $app['events-manager']->trigger('__VALIDATION_REMINDER__', array(
                'to'          => $participantId,
                'ssel_id'     => $basketId,
                'from'        => $validationSession->getInitiatorId(),
                'validate_id' => $validationSession->getId(),
                'url'         => $app['phraseanet.registry']->get('GV_ServerName') . 'lightbox/validate/' . $basketId . '/?LOG=' . $token
            ));

            $participant->setReminded(new \DateTime('now'));
            $app['EM']->persist($participant);
        }

        $app['EM']->flush();

        $session = $app['authentication']->openAccount($user);

        if ($user->get_locale() != $app['locale']) {
            $user->set_locale($app['locale']);
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
        } catch (NotAuthenticatedException $e) {
            $app['session']->getFlashBag()->add('error', sprintf(_('Unable to authenticate with %s'), $provider->getName()));

            return $app->redirect('homepage');
        }

        $token = $provider->getToken();

        // Let's find a match
        $userAuthProvider = $app['EM']
            ->getRepository('Entities\UsrAuthProvider')
            ->findOneBy(array(
                'provider'   => $token->getProvider()->getId(),
                'distant_id' => $token->getId(),
            ));

        if ($userAuthProvider) {
            $app['authentication']->openAccount($userAuthProvider->getUser($app));
            $target = $request->query->get('redirect');

            if (!$target) {
                $target = $app->path('prod');
            }

            return $app->redirect($target);
        }

        if ($app['authentication.suggestion-finder']->find($token)) {
            return $app->redirect($app['url_generator']->generate('login_authentication_provider_mapping', array(
                'providerId' => $providerId,
                'id'         => $token->getId(),
            )));
        } else {
            return $app->redirect($app['url_generator']->generate('login_authentication_provider_bind', array(
                'providerId' => $providerId,
                'id'         => $token->getId(),
            )));
        }
    }

    public function authenticationMapping(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        $token = $provider->getToken();

        $suggestion = $app['authentication.suggestion-finder']->find($token);

        $form = $app->form(new PhraseaAuthenticationWithMappingForm(), array(
            'login' => $suggestion->get_login(),
        ));

        return $app['twig']->render('login/providers/mapping.html.twig', array(
            'provider'   => $provider,
            'recaptcha_display' => $app->isCaptchaRequired(),
            'login'      => new \login(),
            'form'       => $form->createView(),
            'token'      => $token,
            'suggestion' => $suggestion,
        ));
    }

    public function authenticationBindToAccount(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        $token = $provider->getToken();

        $form = $app->form(new PhraseaAuthenticationForm(), array(
        ));

        return $app['twig']->render('login/providers/bind.html.twig', array(
            'login'      => new \login(),
            'recaptcha_display' => $app->isCaptchaRequired(),
            'provider'   => $provider,
            'form'       => $form->createView(),
            'token'      => $provider->getToken(),
        ));
    }

    private function findProvider(PhraseaApplication $app, $providerId)
    {
        try {
            return $app['authentication.providers']->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }
    }

    public function authenticationDoMapToAccount(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        $form = $app->form(new PhraseaAuthenticationWithMappingForm());

        $redirector = function (array $params = array()) use ($app, $providerId) {
            $params = array_merge($params, array(
                'providerId' => $providerId,
            ));

            return $app->redirect($app->path('login_authentication_provider_mapping', $params));
        };

        try {
            $response = $this->doAuthentication($app, $request, $form, $redirector);
        } catch (AuthenticationException $e) {
            return $e->getResponse();
        }

        $usrAuthProvider = new \Entities\UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUsrId($app['authentication']->getUser()->get_id());

        $app['EM']->persist($usrAuthProvider);
        $app['EM']->flush();

        return $response;
    }

    public function authenticationDoBindToAccount(PhraseaApplication $app, Request $request, $providerId)
    {
        $provider = $this->findProvider($app, $providerId);

        $form = $app->form(new PhraseaAuthenticationForm());

        $redirector = function (array $params = array()) use ($app, $providerId) {
            $params = array_merge($params, array(
                'providerId' => $providerId,
            ));

            return $app->redirect($app->path('login_authentication_provider_mapping', $params));
        };

        try {
            $response = $this->doAuthentication($app, $request, $form, $redirector);
        } catch (AuthenticationException $e) {
            return $e->getResponse();
        }

        $usrAuthProvider = new \Entities\UsrAuthProvider();
        $usrAuthProvider->setDistantId($provider->getToken()->getId());
        $usrAuthProvider->setProvider($provider->getId());
        $usrAuthProvider->setUsrId($app['authentication']->getUser()->get_id());

        $app['EM']->persist($usrAuthProvider);
        $app['EM']->flush();

        return $response;
    }

    private function doAuthentication(PhraseaApplication $app, Request $request, FormInterface $form, $redirector)
    {
        if (!is_callable($redirector)) {
            throw new InvalidArgumentException('Redirector should be callable');
        }

        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request));

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
            $usr_id = $app['auth.native']->isValid($request->request->get('login'), $request->request->get('password'), $request);
        } catch (RequireCaptchaException $e) {
            $app->requireCaptcha();
            $app->addFlash('warning', _('Please fill the captcha'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        } catch (AccountLockedException $e) {
            $app->addFlash('warning', _('login::erreur: Vous n\'avez pas confirme votre email'));
            $app->addUnlockLink($e->getUsrId());

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        if (!$usr_id) {
            $app['session']->getFlashBag()->set('error', _('login::erreur: Erreur d\'authentification'));

            throw new AuthenticationException(call_user_func($redirector, $params));
        }

        $user = \User_Adapter::getInstance($usr_id, $app);

        $session = $this->postAuthProcess($app, $user);

        $response = $this->generateAuthResponse($app['browser'], $request->request->get('redirect'));
        $response->headers->setCookie(new Cookie('invite-usr-id', $user->get_id()));

        $user->ACL()->inject_rights();

        if ($request->cookies->has('postlog') && $request->cookies->get('postlog') == '1') {
            if (!$user->is_guest() && $request->cookies->has('invite-usr_id')) {
                if ($user->get_id() != $inviteUsrId = $request->cookies->get('invite-usr_id')) {

                    $repo = $app['EM']->getRepository('Entities\Basket');
                    $baskets = $repo->findBy(array('usr_id' => $inviteUsrId));

                    foreach ($baskets as $basket) {
                        $basket->setUsrId($user->get_id());
                        $app['EM']->persist($basket);
                    }
                }
            }
        }

        if ($request->request->get('remember-me') == '1') {
            $nonce = \random::generatePassword(16);
            $string = $app['browser']->getBrowser() . '_' . $app['browser']->getPlatform();

            $token = $app['auth.password-encoder']->encodePassword($string, $nonce);

            $session->setToken($token)
                ->setNonce($nonce);
            $cookie = new Cookie('persistent', $token);
            $response->headers->setCookie($cookie);
        }

        $event = new PostAuthenticate($request, $response, $user);
        $app['dispatcher']->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        $response = $event->getResponse();

        return $response;
    }
}
