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

use Alchemy\Phrasea\Core;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

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
                    if (null !== $request->query->get('postlog')) {

                        // if isset postlog parameter, set cookie and log out current user
                        // then post login operation like getting baskets from an invit session
                        // could be done by Session_handler authentication process

                        $app['phraseanet.appbox']->get_session()->set_postlog();

                        return $app->redirect("/login/logout/?redirect=" . $request->query->get('redirect', 'prod'));
                    }


                    if ($app['phraseanet.core']->isAuthenticated()) {

                        return $app->redirect('/' . $request->query->get('redirect', 'prod') . '/');
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
            ->before(function() use ($app) {
                    if ($app['phraseanet.core']->isAuthenticated()) {
                        return $app->redirect('/prod/');
                    }
                })
            ->bind('login_authenticate');

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
            ->bind('logout');

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
            ->bind('login_register');

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
            ->bind('submit_login_register');

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
            ->bind('login_register_confirm');

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
            ->bind('login_send_mail');

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
            ->bind('login_forgot_password');

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
            ->bind('submit_login_forgot_password');

        return $controllers;
    }

    /**
     * Send a confirmation mail after register
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function sendConfirmMail(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null === $usrId = $request->query->get('usr_id')) {
            $app->abort(400, sprintf(_('Request to send you the confirmation mail failed, please retry')));
        }

        try {
            $user = \User_Adapter::getInstance((int) $usrId, $appbox);
            $email = $user->get_email();

            if (true === \mail::mail_confirmation($email, $usrId)) {
                return $app->redirect('/login/?notice=mail-sent');
            }
        } catch (\Exception $e) {
            return $app->redirect('/login/?error=user-not-found');
        }
    }

    /**
     * Validation of email adress
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function registerConfirm(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null === $code = $request->query->get('code')) {
            return $app->redirect('/login/?redirect=/prod&error=code-not-found');
        }

        try {
            $datas = \random::helloToken($code);
        } catch (\Exception_NotFound $e) {
            return $app->redirect('/login/?redirect=/prod&error=token-not-found');
        }

        try {
            $user = \User_Adapter::getInstance((int) $datas['usr_id'], $appbox);
        } catch (\Exception $e) {
            return $app->redirect('/login/?redirect=/prod&error=user-not-found');
        }

        if ( ! $user->get_mail_locked()) {
            return $app->redirect('/login/?redirect=prod&notice=already');
        }

        \random::removeToken($code);

        if (\PHPMailer::ValidateAddress($user->get_email())) {
            if (count($user->ACL()->get_granted_base()) > 0) {
                \mail::mail_confirm_registered($user->get_email());
            }

            $user->set_mail_locked(false);
            \random::removeToken($code);

            $appboxRegister = new \appbox_register($appbox);

            $list = $appboxRegister->get_collection_awaiting_for_user($user);

            if (count($list) > 0) {
                $others = array();

                foreach ($list as $collection) {
                    $others[] = $collection->get_name();
                }

                \mail::mail_confirm_unregistered($user->get_email(), $others);

                return $app->redirect('/login/?redirect=prod&notice=confirm-ok-wait');
            }

            return $app->redirect('/login/?redirect=prod&notice=confirm-ok');
        }
    }

    /**
     * Submit the new password
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function renewPassword(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null !== $mail = $request->request->get('mail')) {
            if ( ! \PHPMailer::ValidateAddress($mail)) {
                return $app->redirect('/login/forgot-password/?error=invalidmail');
            }

            try {
                $user = \User_Adapter::getInstance(\User_Adapter::get_usr_id_from_email($mail), $appbox);
            } catch (\Exception $e) {
                return $app->redirect('/login/forgot-password/?error=noaccount');
            }

            $token = \random::getUrlToken(\random::TYPE_PASSWORD, $user->get_id(), new \DateTime('+1 day'));

            if ($token) {
                $url = sprintf('%slogin/forgot-password/?token=%s', $app['phraseanet.core']['Registry']->get('GV_ServerName'), $token);

                if (\mail::forgot_passord($mail, $user->get_login(), $url)) {
                    return $app->redirect('/login/forgot-password/?sent=ok');
                } else {
                    return $app->redirect('/login/forgot-password/?error=mailserver');
                }
            }
        }

        if ((null !== $token = $request->request->get('token'))
            && (null !== $password = $request->request->get('form_password'))
            && (null !== $passwordConfirm = $request->request->get('form_password_confirm'))) {

            if ($password !== $passwordConfirm) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-match');
            } elseif (strlen(trim($password)) < 8) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-short');
            } elseif (trim($password) !== str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $password)) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-invalid');
            }

            try {
                $datas = \random::helloToken($token);

                $user = \User_Adapter::getInstance($datas['usr_id'], $appbox);
                $user->set_password($passwordConfirm);

                \random::removeToken($token);

                return $app->redirect('/login/?notice=password-update-ok');
            } catch (\Exception_NotFound $e) {
                return $app->redirect('/login/forgot-password/?error=token');
            }
        }
    }

    /**
     * Get the fogot password form
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  Response
     */
    public function displayForgotPasswordForm(Application $app, Request $request)
    {
        $tokenize = false;
        $errorMsg = $request->query->get('error');

        if (null !== $token = $request->query->get('token')) {
            try {
                \random::helloToken($token);
                $tokenize = true;
            } catch (\Exception $e) {
                $errorMsg = 'token';
            }
        }

        if (null !== $errorMsg) {
            switch ($errorMsg) {
                case 'invalidmail':
                    $errorMsg = _('Invalid email address');
                    break;
                case 'mailserver':
                    $errorMsg = _('phraseanet::erreur: Echec du serveur mail');
                    break;
                case 'noaccount':
                    $errorMsg = _('phraseanet::erreur: Le compte n\'a pas ete trouve');
                    break;
                case 'mail':
                    $errorMsg = _('phraseanet::erreur: Echec du serveur mail');
                    break;
                case 'token':
                    $errorMsg = _('phraseanet::erreur: l\'url n\'est plus valide');
                    break;
            }
        }

        if (null !== $sentMsg = $request->query->get('sent')) {
            switch ($sentMsg) {
                case 'ok':
                    $sentMsg = _('phraseanet:: Un email vient de vous etre envoye');
                    break;
            }
        }

        if (null !== $passwordMsg = $request->query->get('pass-error')) {
            switch ($passwordMsg) {
                case 'pass-match':
                    $passwordMsg = _('forms::les mots de passe ne correspondent pas');
                    break;
                case 'pass-short':
                    $passwordMsg = _('forms::la valeur donnee est trop courte');
                    break;
                case 'pass-invalid':
                    $passwordMsg = _('forms::la valeur donnee contient des caracteres invalides');
                    break;
            }
        }

        return new Response($app['twig']->render('login/forgot-password.html.twig', array(
                    'tokenize'    => $tokenize,
                    'passwordMsg' => $passwordMsg,
                    'errorMsg'    => $errorMsg,
                    'sentMsg'     => $sentMsg
                )));
    }

    /**
     * Get the register form
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  Response
     */
    public function displayRegisterForm(Application $app, Request $request)
    {
        if (false === \login::register_enabled()) {
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

        $arrayVerif = $this->getRegisterFieldConfiguration($app['phraseanet.core']);

        return new Response($app['twig']->render('login/register.html.twig', array(
                    'inscriptions' => giveMeBases(),
                    'parms'        => $request->query->all(),
                    'needed'       => $needed,
                    'arrayVerif'   => $arrayVerif,
                    'geonames'     => new \geonames(),
                    'demandes'     => $request->query->get('demand', array()),
                    'lng' => \Session_Handler::get_locale()
                )));
    }

    /**
     * Get the register form
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function register(Application $app, Request $request)
    {
        $arrayVerif = $this->getRegisterFieldConfiguration($app['phraseanet.core']);

        $parameters = $request->request->all();

        $needed = array_diff_key($arrayVerif, $parameters);

        if (sizeof($needed) > 0 && ! (sizeof($parameters) === 1 && isset($parameters['form_login']) && $parameters['form_login'] === true)) {
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

        if (false === \PHPMailer::ValidateAddress($email = $request->request->get('form_email'))) {
            $needed['form_email'] = 'mail-invalid';
        }

        if (strlen($login = $request->request->get('form_login')) < 5) {
            $needed['form_login'] = 'login-short';
        }

        if ((sizeof($parameters) === 1 && isset($parameters['form_login']) && $parameters['form_login'] === true) && ! isset($needed['form_email'])) {
            $login = $email;
            unset($needed['form_login']);
        }

        if (\User_Adapter::get_usr_id_from_email($email)) {
            $needed['form_email'] = 'user-email-exists';
        }

        if (\User_Adapter::get_usr_id_from_login($login)) {
            $needed['form_login'] = 'usr-login-exists';
        }

        if (sizeof($demands = $request->request->get('demand', array())) === 0) {
            $needed['demandes'] = 'no-collections';
        }

        if (sizeof($needed) > 0) {
            return $app->redirect(sprintf('/register/?%s', http_build_query(array('needed' => $needed))));
        }

        require_once($app['phraseanet.core']['Registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');

        $demands = array_unique($demands);
        $inscriptions = giveMeBases();
        $inscOK = array();

        foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {

            foreach ($databox->get_collections() as $collection) {
                if ( ! in_array($collection->get_base_id(), $demands)) {
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

        try {
            $user = \User_Adapter::create($app['phraseanet.appbox'], $request->request->get('form_login'), $request->request->get("form_password"), $request->request->get("form_email"), false);

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

            if ( ! ! $app['phraseanet.core']['Registry']->get('GV_autoregister')) {

                $template_user_id = \User_Adapter::get_usr_id_from_login('autoregister');

                $template_user = \User_Adapter::getInstance($template_user_id, $app['phraseanet.appbox']);

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

                $collection = \collection::get_from_base_id($base_id);
                $appbox_register->add_request($user, $collection);
                unset($collection);
                $demandOK[$base_id] = true;
            }

            $event_mngr = \eventsmanager_broker::getInstance($app['phraseanet.appbox'], $app['phraseanet.core']);

            $params = array(
                'demand'       => $demandOK,
                'autoregister' => $autoReg,
                'usr_id'       => $user->get_id()
            );

            $event_mngr->trigger('__REGISTER_AUTOREGISTER__', $params);
            $event_mngr->trigger('__REGISTER_APPROVAL__', $params);

            $user->set_mail_locked(true);
            if (true === \mail::mail_confirmation($user->get_email(), $user->get_id())) {

                return $app->redirect('/login/?notice=mail-sent');
            }

            return $app->redirect(sprintf('/login/?usr=%d', $user->get_id()));
        } catch (\Exception $e) {
            return $app->redirect(sprintf('/login/?error=%s', _('An error occured while inscription, please retry or contact a sysadmin')));
        }
    }

    /**
     * Logout from Phraseanet
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function logout(Application $app, Request $request)
    {
        $appRedirect = $request->query->get("app");

        try {
            $session = $app['phraseanet.appbox']->get_session();

            $session->logout();
            $session->remove_cookies();
        } catch (\Exception $e) {
            return $app->redirect("/" . ($appRedirect ? $appRedirect : 'prod'));
        }

        return $app->redirect("/login/?logged_out=user" . ($appRedirect ? sprintf("&redirect=/%s", $appRedirect) : ""));
    }

    /**
     * Login into Phraseanet
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  Response
     */
    public function login(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];
        $registry = $app['phraseanet.core']['Registry'];

        require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');
        if ($registry->get('GV_captchas') && trim($registry->get('GV_captcha_private_key')) !== '' && trim($registry->get('GV_captcha_public_key')) !== '') {
            include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');
        }

        $warning = $request->query->get('error', '');

        try {
            $appbox->get_connection();
        } catch (\Exception $e) {
            $warning = 'no-connection';
        }

        if ( ! ! $registry->get('GV_maintenance')) {
            $warning = 'maintenance';
        }

        switch ($warning) {

            case 'maintenance':
                $warning = _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee');
                break;
            case 'no-connection':
                $warning = _('login::erreur: No available connection - Please contact sys-admin');
                break;
            case 'auth':
                $warning = _('login::erreur: Erreur d\'authentification');
                break;
            case 'captcha':
                $warning = _('login::erreur: Erreur de captcha');
                break;
            case 'mail-not-confirmed' :
                $warning = _('login::erreur: Vous n\'avez pas confirme votre email');
                break;
            case 'no-base' :
                $warning = _('login::erreur: Aucune base n\'est actuellment accessible');
                break;
            case 'session' :
                $warning = _('Error while authentication, please retry or contact an admin if problem persists');
                break;
        }

        if (ctype_digit($request->query->get('usr'))) {
            $warning .= '<div class="notice"><a href="/login/send-mail-confirm/?usr_id=' . $request->query->get('usr') . '" target ="_self" style="color:black;text-decoration:none;">' . _('login:: Envoyer a nouveau le mail de confirmation') . '</a></div>';
        }

        switch ($notice = $request->query->get('notice', '')) {
            case 'ok':
                $notice = _('login::register: sujet email : confirmation de votre adresse email');
                break;
            case 'already':
                $notice = _('login::notification: cette email est deja confirmee');
                break;
            case 'mail-sent':
                $notice = _('login::notification: demande de confirmation par mail envoyee');
                break;
            case 'register-ok':
                $notice = _('login::notification: votre email est desormais confirme');
                break;
            case 'register-ok-wait':
                $notice = _('Your email is now confirmed. You will be informed as soon as your pending request will be managed');
                break;
            case 'password-update-ok':
                $notice = _('login::notification: Mise a jour du mot de passe avec succes');
                break;
            case 'no-register-available':
                $notice = _('User inscriptions are disabled');
                break;
        }

        $captchaSys = '';
        if ( ! $registry->get('GV_maintenance')
            && $registry->get('GV_captchas')
            && trim($registry->get('GV_captcha_private_key')) !== ''
            && trim($registry->get('GV_captcha_public_key')) !== ''
            && $request->query->get('error') == 'captcha') {
            $captchaSys = '<div style="margin:0;float: left;width:330px;"><div id="recaptcha_image" style="float: left;margin:10px 15px 5px"></div>
                                                                <div style="text-align:center;float: left;margin:0 15px 5px;width:300px;">
                                                                <a href="javascript:Recaptcha.reload()" class="link">' . _('login::captcha: obtenir une autre captcha') . '</a>
                                                                </div>
                                                                <div style="text-align:center;float: left;width:300px;margin:0 15px 0px;">
                                                                    <span class="recaptcha_only_if_image">' . _('login::captcha: recopier les mots ci dessous') . ' : </span>
                                                                    <input name="recaptcha_response_field" id="recaptcha_response_field" value="" type="text" style="width:180px;"/>
                                                                </div>' . recaptcha_get_html($registry->get('GV_captcha_public_key')) . '</div>';
        }

        $public_feeds = \Feed_Collection::load_public_feeds($appbox);
        $feeds = array_merge(array($public_feeds->get_aggregate()), $public_feeds->get_feeds());

        return $app['twig']->render('login/index.html.twig', array(
                'module_name'    => _('Accueil'),
                'notice'         => $notice,
                'warning'        => $warning,
                'redirect'       => $request->query->get('redirect'),
                'logged_out'     => $request->query->get('logged_out'),
                'captcha_system' => $captchaSys,
                'login'          => new \login(),
                'feeds'          => $feeds,
                'display_layout' => $registry->get('GV_home_publi')
            ));
    }

    /**
     * Authenticate to phraseanet
     *
     * @param   Application     $app     A Silex application where the controller is mounted on
     * @param   Request         $request The current request
     * @return  RedirectResponse
     */
    public function authenticate(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];
        $session = $appbox->get_session();
        $registry = $app['phraseanet.core']['Registry'];

        $is_guest = false;

        if (null !== $request->request->get('nolog') && \phrasea::guest_allowed()) {
            $is_guest = true;
        }

        if (((null !== $login = $request->request->get('login')) && (null !== $pwd = $request->request->get('pwd'))) || $is_guest) {

            /**
             * @todo dispatch an event that can be used to tweak the authentication
             * (LDAP....)
             */
            // $app['dispatcher']->dispatch();

            try {
                if ($is_guest) {
                    $auth = new \Session_Authentication_Guest($appbox);
                } else {
                    $captcha = false;

                    if ($registry->get('GV_captchas')
                        && '' !== $privateKey = trim($registry->get('GV_captcha_private_key'))
                        && trim($registry->get('GV_captcha_public_key')) !== ''
                        && null !== $challenge = $request->request->get("recaptcha_challenge_field")
                        && null !== $captachResponse = $request->request->get("recaptcha_response_field")) {

                        include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');

                        $checkCaptcha = recaptcha_check_answer($privateKey, $_SERVER["REMOTE_ADDR"], $challenge, $captachResponse);

                        if ($checkCaptcha->is_valid) {
                            $captcha = true;
                        }
                    }

                    $auth = new \Session_Authentication_Native($appbox, $login, $pwd);
                    $auth->set_captcha_challenge($captcha);
                }

                $session->authenticate($auth);
            } catch (\Exception_Session_StorageClosed $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=session");
            } catch (\Exception_Session_RequireCaptcha $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=captcha");
            } catch (\Exception_Unauthorized $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=auth");
            } catch (\Exception_Session_MailLocked $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=mail-not-confirmed&usr=" . $e->get_usr_id());
            } catch (\Exception_Session_WrongToken $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=token");
            } catch (\Exception_InternalServerError $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=session");
            } catch (\Exception_ServiceUnavailable $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=maintenance");
            } catch (\Exception_Session_BadSalinity $e) {
                $date = new \DateTime('5 minutes');
                $usr_id = \User_Adapter::get_usr_id_from_login($request->request->get('login'));
                $url = '/account/forgot-password/?token=' . \random::getUrlToken(\random::TYPE_PASSWORD, $usr_id, $date) . '&salt=1';

                return $app->redirect($url);
            } catch (\Exception $e) {
                return $app->redirect("/login/?redirect=" . $request->request->get('redirect') . "&error=" . _('An error occured'));
            }

            if ($app['browser']->isMobile()) {
                return $app->redirect("/lightbox/");
            } elseif ($request->request->get('redirect')) {
                return $app->redirect($request->request->get('redirect'));
            } elseif (true !== $app['browser']->isNewGeneration()) {
                return $app->redirect('/client/');
            } else {
                return $app->redirect('/prod/');
            }
        } else {
            return $app->redirect("/login/");
        }
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
     * @param Core $core
     * @return boolean
     */
    private function getRegisterFieldConfiguration(Core $core)
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

        $registerFieldConfigurationFile = $core['Registry']->get('GV_RootPath') . 'config/register-fields.php';

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
