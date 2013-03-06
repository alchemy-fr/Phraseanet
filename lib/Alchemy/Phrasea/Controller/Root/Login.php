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
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\Event\PreAuthenticate;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationRegistered;
use Alchemy\Phrasea\Notification\Mail\MailSuccessEmailConfirmationUnregistered;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Form\Login\PhraseaAuthenticationForm;
use Alchemy\Phrasea\Form\Login\PhraseaForgotPasswordForm;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Login implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            if ($request->getPathInfo() == $app->path('homepage')) {
                return;
            }
            if ($app['phraseanet.registry']->get('GV_maintenance')) {
                return $app->redirect(
                    $app->path('homepage', array(
                        'redirect' => ltrim($request->request->get('redirect'), '/'),
                        'error'    => 'maintenance'
                    ))
                );
            }
        });

        $controllers->before(function() use ($app) {
            $app['twig.form.templates'] = array('login/common/form_div_layout.html.twig');
        });

        /**
         * Login
         *
         * name         : homepage
         *
         * description  : Login from phraseanet
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('login'))
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

        /**
         * Authenticate
         *
         * name         : login_authenticate
         *
         * description  : authenticate to phraseanet
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/authenticate/', $this->call('authenticate'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authenticate');

        $controllers->match('/authenticate/guest/', $this->call('authenticateAsGuest'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })
            ->bind('login_authenticate_as_guest')
            ->method('GET|POST');

        $controllers->get('/provider/{providerId}/authenticate/', $this->call('authenticateWithProvider'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_authenticate');

        $controllers->get('/provider/{providerId}/callback/', $this->call('authenticationCallback'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_callback');

        $controllers->get('/provider/{providerId}/add-mapping/', $this->call('authenticationMapping'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_mapping');

        $controllers->get('/provider/{providerId}/bind-account/', $this->call('authenticationBindToAccount'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_bind');

        $controllers->post('/provider/{providerId}/bind-account/', $this->call('authenticationDoBindToAccount'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_authentication_provider_do_bind');



        /**
         * Logout
         *
         * name         : logout
         *
         * description  : Logout from phraseanet
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/logout/', $this->call('logout'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireAuthentication();
            })->bind('logout');

        /**
         * Register a new user
         *
         * name         : login_register
         *
         * description  : Display form to create a new user
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/register/', $this->call('displayRegisterForm'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_register');

        /**
         * Register a new user
         *
         * name         : submit_login_register
         *
         * description  : Register a new user
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/register/', $this->call('register'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('submit_login_register');

        /**
         * Register confirm
         *
         * name         : login_register_confirm
         *
         * description  : Confirm a user registration by validating his email adress
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/register-confirm/', $this->call('registerConfirm'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_register_confirm');

        /**
         * Send confirmation mail
         *
         * name         : login_send_mail
         *
         * description  : Send confirmation mail, to verify user email
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/send-mail-confirm/', $this->call('sendConfirmMail'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_send_mail');

        /**
         * Forgot password
         *
         * name         : login_forgot_password
         *
         * description  : Display form to renew password
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/forgot-password/', $this->call('displayForgotPasswordForm'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('login_forgot_password');

        /**
         * Renew password
         *
         * name         : submit_login_forgot_password
         *
         * description  : Register the new user password
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/forgot-password/', $this->call('renewPassword'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotAuthenticated();
            })->bind('submit_login_forgot_password');

        /********
         *
         *  Added some controllers because I don't know where to plug this templates..
         */

        /**
         * @todo This a route test to display cgus
         */
        $controllers->get('/cgus', function(PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/cgus.html.twig');
        })->bind('login_cgus');

        /**
         * Register classic form
         */
        $controllers->get('/register-classic', function(PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/register-classic.html.twig');
        })->bind('login_register_classic');

        /**
         * Register throught providers
         */
        $controllers->get('/register-provider', function(PhraseaApplication $app, Request $request) {
            return $app['twig']->render('login/register-provider.html.twig');
        })->bind('login_register_provider');

        return $controllers;
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
            $app->abort(400, sprintf(_('Request to send you the confirmation mail failed, please retry')));
        }

        try {
            $user = \User_Adapter::getInstance((int) $usrId, $app);
        } catch (\Exception $e) {
            return $app->redirect('/login/?error=user-not-found');
        }

        $receiver = null;
        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {

        }

        if ($receiver) {
            $expire = new \DateTime('+3 days');

            $token = $app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), $expire, $user->get_email());

            $mail = MailRequestEmailConfirmation::create($app, $receiver);
            $mail->setButtonUrl($app['phraseanet.registry']->get('GV_ServerName') . "register-confirm/?code=" . $token);
            $mail->setExpiration($expire);

            $app['notification.deliverer']->deliver($mail);
        }

        return $app->redirect('/login/?notice=mail-sent');
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
            return $app->redirect('/login/?redirect=prod&error=code-not-found');
        }

        try {
            $datas = $app['tokens']->helloToken($code);
        } catch (\Exception_NotFound $e) {
            return $app->redirect('/login/?redirect=prod&error=token-not-found');
        }

        try {
            $user = \User_Adapter::getInstance((int) $datas['usr_id'], $app);
        } catch (\Exception $e) {
            return $app->redirect('/login/?redirect=prod&error=user-not-found');
        }

        if (!$user->get_mail_locked()) {
            return $app->redirect('/login/?redirect=prod&notice=already');
        }

        $app['tokens']->removeToken($code);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            return $app->redirect('/login/?redirect=prod&notice=invalid-email');
        }

        $user->set_mail_locked(false);
        $app['tokens']->removeToken($code);

        if (count($user->ACL()->get_granted_base()) > 0) {
            $mail = MailSuccessEmailConfirmationRegistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            return $app->redirect('/login/?redirect=prod&notice=confirm-ok');
        } else {
            $mail = MailSuccessEmailConfirmationUnregistered::create($app, $receiver);
            $app['notification.deliverer']->deliver($mail);

            return $app->redirect('/login/?redirect=prod&notice=confirm-ok-wait');
        }
    }

    /**
     * Submit the new password
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function renewPassword(PhraseaApplication $app, Request $request)
    {
        if (null !== $mail = $request->request->get('mail')) {
            try {
                $user = \User_Adapter::getInstance(\User_Adapter::get_usr_id_from_email($app, $mail), $app);
            } catch (\Exception $e) {
                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('error' => 'noaccount')));
            }

            try {
                $receiver = Receiver::fromUser($user);
            } catch (InvalidArgumentException $e) {
                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('error' => 'invalidmail')));
            }

            $token = $app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), new \DateTime('+1 day'));

            if ($token) {
                $url = $app['url_generator']->generate('login_forgot_password', array('token' => $token), true);

                $mail = MailRequestEmailConfirmation::create($app, $receiver);
                $mail->setButtonUrl($url);
                $app['notification.deliverer']->deliver($mail);

                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('sent' => 'ok')));
            }
        }

        if ((null !== $token = $request->request->get('token'))
            && (null !== $password = $request->request->get('form_password'))
            && (null !== $passwordConfirm = $request->request->get('form_password_confirm'))) {

            if ($password !== $passwordConfirm) {
                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('pass-error' => 'pass-match')));
            } elseif (strlen(trim($password)) < 8) {
                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('pass-error' => 'pass-short')));
            } elseif (trim($password) !== str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $password)) {
                return $app->redirect($app['url_generator']->generate('login_forgot_password', array('pass-error' => 'pass-invalid')));
            }

            try {
                $datas = $app['tokens']->helloToken($token);

                $user = \User_Adapter::getInstance($datas['usr_id'], $app);
                $user->set_password($passwordConfirm);

                $app['tokens']->removeToken($token);

                return $app->redirect('/login/?notice=password-update-ok');
            } catch (\Exception_NotFound $e) {
                return $app->redirect($app->path('login_forgot_password', array('error' => 'token')));
            }
        }
    }

    /**
     * Get the fogot password form
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function displayForgotPasswordForm(PhraseaApplication $app, Request $request)
    {
//        $tokenize = false;
//        $errorMsg = $request->query->get('error');

//        if (null !== $token = $request->query->get('token')) {
//            try {
//                \random::helloToken($app, $token);
//                $tokenize = true;
//            } catch (\Exception $e) {
//                $errorMsg = 'token';
//            }
//        }
//
//        if (null !== $errorMsg) {
//            switch ($errorMsg) {
//                case 'invalidmail':
//                    $errorMsg = _('Invalid email address');
//                    break;
//                case 'mailserver':
//                    $errorMsg = _('phraseanet::erreur: Echec du serveur mail');
//                    break;
//                case 'noaccount':
//                    $errorMsg = _('phraseanet::erreur: Le compte n\'a pas ete trouve');
//                    break;
//                case 'mail':
//                    $errorMsg = _('phraseanet::erreur: Echec du serveur mail');
//                    break;
//                case 'token':
//                    $errorMsg = _('phraseanet::erreur: l\'url n\'est plus valide');
//                    break;
//            }
//        }
//
//        if (null !== $sentMsg = $request->query->get('sent')) {
//            switch ($sentMsg) {
//                case 'ok':
//                    $sentMsg = _('phraseanet:: Un email vient de vous etre envoye');
//                    break;
//            }
//        }
//
//        if (null !== $passwordMsg = $request->query->get('pass-error')) {
//            switch ($passwordMsg) {
//                case 'pass-match':
//                    $passwordMsg = _('forms::les mots de passe ne correspondent pas');
//                    break;
//                case 'pass-short':
//                    $passwordMsg = _('forms::la valeur donnee est trop courte');
//                    break;
//                case 'pass-invalid':
//                    $passwordMsg = _('forms::la valeur donnee contient des caracteres invalides');
//                    break;
//            }
//        }

        $form = $app->form(new PhraseaForgotPasswordForm());

        return $app['twig']->render('login/forgot-password.html.twig', array(
            'form' => $form->createView(),
            'login' => new \login
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
        $captchaSys = '';

        if ($app['phraseanet.registry']->get('GV_captchas')
            && $app['phraseanet.registry']->get('GV_captcha_private_key')
            && $app['phraseanet.registry']->get('GV_captcha_public_key')) {

            require_once __DIR__ . '/../../../../../lib/vendor/recaptcha/recaptchalib.php';

            $captchaSys = '<div style="margin:0;width:330px;">
            <div id="recaptcha_image" style="margin:10px 0px 5px"></div>
            <div style="text-align:left;margin:0 0px 5px;width:300px;">
            <a href="javascript:Recaptcha.reload()" class="link">' . _('login::captcha: obtenir une autre captcha') . '</a>
            </div>
            <div style="text-align:left;width:300px;">
                <span class="recaptcha_only_if_image">' . _('login::captcha: recopier les mots ci dessous') . ' : </span>
                <input name="recaptcha_response_field" id="recaptcha_response_field" value="" type="text" style="width:180px;"/>
            </div>' . recaptcha_get_html($app['phraseanet.registry']->get('GV_captcha_public_key')) . '</div>';
        }

        $login = new \login();
        if (false === $login->register_enabled($app)) {
            return $app->redirect('/login/?notice=no-register-available');
        }

        $needed = $request->query->get('needed', array());

        foreach ($needed as $fields => $error) {
            switch ($error) {
                case 'required-field':
                    $needed[$fields] = _('forms::ce champ est requis');
                    break;
                case 'pass-match':
                    $needed[$fields] = _('forms::les mots de passe ne correspondent pas');
                    break;
                case 'pass-short':
                    $needed[$fields] = _('forms::la valeur donnee est trop courte');
                    break;
                case 'pass-invalid':
                    $needed[$fields] = _('forms::la valeur donnee est trop courte');
                    break;
                case 'email-invalid':
                    $needed[$fields] = _('forms::l\'email semble invalide');
                    break;
                case 'login-short':
                    $needed[$fields] = _('forms::la valeur donnee est trop courte');
                    break;
                case 'login-mail-exists':
                    $needed[$fields] = _('forms::un utilisateur utilisant ce login existe deja');
                    break;
                case 'user-mail-exists':
                    $needed[$fields] = _('forms::un utilisateur utilisant cette adresse email existe deja');
                    break;
                case 'no-collections':
                    $needed[$fields] = _('You have not made any request for collections');
                    break;
            }
        }

        $arrayVerif = $this->getRegisterFieldConfiguration($app);

        return $app['twig']->render('login/register.html.twig', array(
            'inscriptions' => giveMeBases($app),
            'parms'        => $request->query->all(),
            'needed'       => $needed,
            'arrayVerif'   => $arrayVerif,
            'demandes'     => $request->query->get('demand', array()),
            'lng'            => $app['locale'],
            'captcha_system' => $captchaSys,
        ));
    }

    /**
     * Do the registration
     *
     * @param  Application      $app     A Silex application where the controller is mounted on
     * @param  Request          $request The current request
     * @return RedirectResponse
     */
    public function register(PhraseaApplication $app, Request $request)
    {
        $captchaOK = true;

        if ($app['phraseanet.registry']->get('GV_captchas')
            && $request->request->get('GV_captcha_private_key')
            && $request->request->get('GV_captcha_public_key')
            && $request->request->get("recaptcha_challenge_field")
            && $request->request->get("recaptcha_response_field")) {
            $checkCaptcha = recaptcha_check_answer(
                $app['phraseanet.registry']->get('GV_captcha_private_key'), $request->server->get('REMOTE_ADDR'), $request->request->get["recaptcha_challenge_field"], $request->request->get["recaptcha_response_field"]
            );
            $captchaOK = $checkCaptcha->is_valid;
        }

        if (!$captchaOK) {
            return $app->redirect($app['url_generator']->generate('login_register', array(
                'error' => 'captcha'
            )));
        }

        $arrayVerif = $this->getRegisterFieldConfiguration($app);

        $parameters = $request->request->all();

        $needed = array_diff_key($arrayVerif, $parameters);

        if (sizeof($needed) > 0 && !(sizeof($parameters) === 1 && isset($parameters['form_login']) && $parameters['form_login'] === true)) {
            $app->abort(400, sprintf(_('Bad request missing %s parameters'), implode(',', array_keys($needed))));
        }

        foreach ($parameters as $field => $value) {
            if (is_string($value) && isset($arrayVerif[$field]) && $arrayVerif[$field] === true) {
                if ('' === trim($value)) {
                    $needed[$field] = 'required-field';
                }
            }
        }

        if (($password = $request->request->get('form_password')) !== $request->request->get('form_password_confirm')) {
            $needed['form_password'] = $needed['form_password_confirm'] = 'pass-match';
        } elseif (strlen(trim($password)) < 5) {
            $needed['form_password'] = 'pass-short';
        } elseif (trim($password) !== str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $password)) {
            $needed['form_password'] = 'pass-invalid';
        }

        if (false === \Swift_Validate::email($email = $request->request->get('form_email'))) {
            $needed['form_email'] = 'mail-invalid';
        }

        if (strlen($login = $request->request->get('form_login')) < 5) {
            $needed['form_login'] = 'login-short';
        }

        if ((sizeof($parameters) === 1 && isset($parameters['form_login']) && $parameters['form_login'] === true) && !isset($needed['form_email'])) {
            $login = $email;
            unset($needed['form_login']);
        }

        if (\User_Adapter::get_usr_id_from_email($app, $email)) {
            $needed['form_email'] = 'user-email-exists';
        }

        if (\User_Adapter::get_usr_id_from_login($app, $login)) {
            $needed['form_login'] = 'usr-login-exists';
        }

        if (sizeof($demands = $request->request->get('demand', array())) === 0) {
            $needed['demandes'] = 'no-collections';
        }

        if (sizeof($needed) > 0) {
            return $app->redirect($app['url_generator']->generate('login_register', array(
                'needed' => $needed
            )));
        }

        require_once($app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

        $demands = array_unique($demands);
        $inscriptions = giveMeBases($app);
        $inscOK = array();

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            foreach ($databox->get_collections() as $collection) {
                if (!in_array($collection->get_base_id(), $demands)) {
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

        $user = \User_Adapter::create($app, $request->request->get('form_login'), $request->request->get("form_password"), $request->request->get("form_email"), false);

        $user->set_gender($request->request->get('form_gender'))
            ->set_firstname($request->request->get('form_firstname'))
            ->set_lastname($request->request->get('form_lastname'))
            ->set_address($request->request->get('form_address'))
            ->set_zip($request->request->get('form_zip'))
            ->set_tel($request->request->get('form_phone'))
            ->set_fax($request->request->get('form_fax'))
            ->set_job($request->request->get('form_job'))
            ->set_company($request->request->get('form_company'))
            ->set_position($request->request->get('form_activity'))
            ->set_geonameid($request->request->get('form_geonameid'));

        $demandOK = array();

        if (!!$app['phraseanet.registry']->get('GV_autoregister')) {

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

        foreach ($demands as $base_id) {
            if (false === $inscOK[$base_id] || $user->ACL()->has_access_to_base($base_id)) {
                continue;
            }

            $collection = \collection::get_from_base_id($app, $base_id);
            $appbox_register->add_request($user, $collection);
            unset($collection);
            $demandOK[$base_id] = true;
        }

        $params = array(
            'demand'       => $demandOK,
            'autoregister' => $autoReg,
            'usr_id'       => $user->get_id()
        );

        $app['events-manager']->trigger('__REGISTER_AUTOREGISTER__', $params);
        $app['events-manager']->trigger('__REGISTER_APPROVAL__', $params);

        try {
            $receiver = Receiver::fromUser($user);
        } catch (InvalidArgumentException $e) {
            return $app->redirect('/login/?notice=mail-not-sent');
        }

        $user->set_mail_locked(true);

        $expire = new \DateTime('+3 days');
        $token = $app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), $expire, $user->get_email());

        $mail = MailRequestEmailConfirmation::create($app, $receiver);
        $mail->setButtonUrl($app['phraseanet.registry']->get('GV_ServerName') . "register-confirm/?code=" . $token);
        $mail->setExpiration($expire);

        $app['notification.deliverer']->deliver($mail);

        return $app->redirect('/login/?notice=mail-sent');
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

        $app->addFlash('notice', 'Vous etes maintenant deconnecte. A bientot.');

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

//        $warning = $request->query->get('error', '');

        try {
            $app['phraseanet.appbox']->get_connection();
        } catch (\Exception $e) {
            $app->addFlash('error', _('login::erreur: No available connection - Please contact sys-admin'));
        }


        if ($app['phraseanet.registry']->get('GV_maintenance')) {
            $app->addFlash('notice', _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee'));
        }

//        switch ($warning) {
//
//            case 'maintenance':
//                $warning = _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee');
//                break;
//            case 'no-connection':
//                $warning = _('login::erreur: No available connection - Please contact sys-admin');
//                break;
//            case 'auth':
//                $warning = _('login::erreur: Erreur d\'authentification');
//                break;
//            case 'captcha':
//                $warning = _('login::erreur: Erreur de captcha');
//                break;
//            case 'account-locked' :
//                $warning = _('login::erreur: Vous n\'avez pas confirme votre email');
//                break;
//            case 'no-base' :
//                $warning = _('login::erreur: Aucune base n\'est actuellment accessible');
//                break;
//            case 'session' :
//                $warning = _('Error while authentication, please retry or contact an admin if problem persists');
//                break;
//            case 'unexpected' :
//                $warning = _('An unexpected error occured during authentication process, please contact an admin');
//                break;
//        }
//
//        if (ctype_digit($request->query->get('usr'))) {
//            $warning .= '<div class="notice">
//                <a href="/login/send-mail-confirm/?usr_id=' . $request->query->get('usr') . '" target ="_self" style="color:black;text-decoration:none;">' .
//                _('login:: Envoyer a nouveau le mail de confirmation') . '</a></div>';
//        }
//
//        switch ($notice = $request->query->get('notice', '')) {
//            case 'ok':
//                $notice = _('login::register: sujet email : confirmation de votre adresse email');
//                break;
//            case 'already':
//                $notice = _('login::notification: cette email est deja confirmee');
//                break;
//            case 'mail-sent':
//                $notice = _('login::notification: demande de confirmation par mail envoyee');
//                break;
//            case 'register-ok':
//                $notice = _('login::notification: votre email est desormais confirme');
//                break;
//            case 'register-ok-wait':
//                $notice = _('Your email is now confirmed. You will be informed as soon as your pending request will be managed');
//                break;
//            case 'password-update-ok':
//                $notice = _('login::notification: Mise a jour du mot de passe avec succes');
//                break;
//            case 'no-register-available':
//                $notice = _('User inscriptions are disabled');
//                break;
//        }

        $public_feeds = \Feed_Collection::load_public_feeds($app);

        $feeds = $public_feeds->get_feeds();
        array_unshift($feeds, $public_feeds->get_aggregate());

        $form = $app->form(new PhraseaAuthenticationForm(), array(
            'disabled' => $app['phraseanet.registry']->get('GV_maintenance')
        ));

        return $app['twig']->render('login/index.html.twig', array(
                'module_name'    => _('Accueil'),
                'redirect'       => ltrim($request->query->get('redirect'), '/'),
                'recaptcha_display' => false,
//                'logged_out'     => $request->query->get('logged_out'),
//                'captcha_system' => $captchaSys,
                'login'          => new \login(),
                'feeds'          => $feeds,
            'guest_allowed' => \phrasea::guest_allowed($app),
//                'display_layout' => $app['phraseanet.registry']->get('GV_home_publi'),
                'form'           => $form->createView(),
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
        $app['dispatcher']->dispatch(PhraseaEvents::PRE_AUTHENTICATE, new PreAuthenticate($request));

        $form = $app->form(new PhraseaAuthenticationForm());
        $form->bind($request);

        if (!$form->isValid()) {
            $app->addFlash('error', _('An unexpected error occured during authentication process, please contact an admin'));

            return $app->redirect($app->path('homepage'));
        }

        $params = array();

        if (null !== $redirect = $request->get('redirect')) {
            $params['redirect'] = ltrim($redirect, '/');
        }

        try {
            $usr_id = $app['auth.native']->isValid($request->request->get('login'), $request->request->get('password'), $request);
        } catch (RequireCaptchaException $e) {
            $params = array_merge($params, array('error' => 'captcha'));

            return $app->redirect($app->path('homepage', $params));
        } catch (AccountLockedException $e) {
            $params = array_merge($params, array(
                'error' => 'account-locked',
                'usr_id' => $e->getUsrId()
            ));

            return $app->redirect($app->path('homepage', $params));
        }

        if (!$usr_id) {
            $app['session']->getFlashBag()->set('error', _('login::erreur: Erreur d\'authentification'));

            return $app->redirect($app->path('homepage', $params));
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

        $event = new PostAuthenticate($request, $response);
        $app['dispatcher']->dispatch(PhraseaEvents::POST_AUTHENTICATE, $event);

        $response = $event->getResponse();

        return $response;
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
        $provider = $app['authentication.providers']->get($providerId);

        return $provider->authenticate($request->query->all());
    }

    public function authenticationCallback(PhraseaApplication $app, Request $request, $providerId)
    {
        try {
            $provider = $app['authentication.providers']->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }

        // triggers what's necessary
        try {
            $provider->onCallback($app, $request);
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
            $app['authentication']->openAccount($userAuthProvider->getUser());
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
        try {
            $provider = $app['authentication.providers']->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }

        $token = $provider->getToken();

        return $app['twig']->render('login/providers/mapping.html.twig', array(
            'token'      => $token,
            'suggestion' => $app['authentication.suggestion-finder']->find($token),
        ));
    }

    public function authenticationBindToAccount(PhraseaApplication $app, Request $request, $providerId)
    {
        try {
            $provider = $app['authentication.providers']->get($providerId);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException('The requested provider does not exist');
        }

        return $app['twig']->render('login/providers/bind.html.twig', array(
            'token'      => $provider->getToken()
        ));
    }

    public function authenticationDoBindToAccount(PhraseaApplication $app, Request $request, $providerId)
    {
        if (!$app['authentication.phrasea']->verify($request->query->get('username'), $request->query->get('password'))) {
//            $app
        }
//        try {
//            $provider = $app['authentication.providers']->get($providerId);
//        } catch (InvalidArgumentException $e) {
//            throw new NotFoundHttpException('The requested provider does not exist');
//        }
//
//        return $app['twig']->render('login/providers/bind.html.twig', array(
//            'token'      => $provider->getToken()
//        ));
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

    /**
     * Get required fields configuration
     *
     * @param  Application $app
     * @return boolean
     */
    private function getRegisterFieldConfiguration(PhraseaApplication $app)
    {
        /**
         * @todo enhance this shit
         */
        $arrayVerif = array(
            "form_login"            => true,
            "form_password"         => true,
            "form_password_confirm" => true,
            "form_gender"           => true,
            "form_lastname"         => true,
            "form_firstname"        => true,
            "form_email"            => true,
            "form_job"              => true,
            "form_company"          => true,
            "form_activity"         => true,
            "form_phone"            => true,
            "form_fax"              => true,
            "form_address"          => true,
            "form_zip"              => true,
            "form_geonameid"        => true,
            "demand"                => true
        );

        $registerFieldConfigurationFile = $app['phraseanet.registry']->get('GV_RootPath') . 'config/register-fields.php';

        if (is_file($registerFieldConfigurationFile)) {
            include $registerFieldConfigurationFile;
        }

        //Override mandatory fields
        $arrayVerif['form_login'] = true;
        $arrayVerif['form_password'] = true;
        $arrayVerif['form_password_confirm'] = true;
        $arrayVerif['demand'] = true;
        $arrayVerif['form_email'] = true;

        return $arrayVerif;
    }
}
