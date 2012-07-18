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
use Symfony\Component\HttpFoundation\Request;
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



        $controllers->get('/',  $this->call('login'))
            ->before(function() use ($app) {
                return $app['phraseanet.core']['Firewall']->requireNotAuthenticated($app);
            });



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
        $controllers->get('/register/', $this->call('register'))
            ->bind('login_register');

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
         * name         : post_login_forgot_password
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
            ->bind('post_login_forgot_password');

        return $controllers;
    }

    /**
     * Send a confirmation mail after register
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendConfirmMail(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null === $usrId = $request->get('usr_id')) {
            $app->abort(400, sprintf(_('Request to send you the confirmation mail failed, please retry')));
        }

        try {
            $user = \User_Adapter::getInstance((int) $usrId, $appbox);
            $email = $user->get_email();

            if (true === \mail::mail_confirmation($email, $usrId)) {
                return $app->redirect('/login/?confirm=mail-sent');
            }
        } catch (\Exception $e) {
            return $app->redirect('/login/?error=user-not-found');
        }
    }

    /**
     * Validation of email adress
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerConfirm(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null === $code = $request->get('code')) {
            return $app->redirect('/login/?redirect=/prod&error=code-not-found');
        }

        try {
            $datas = \random::helloToken($parm['code']);
        } catch (\Exception_NotFound $e) {
            return $app->redirect('/login/?redirect=/prod&error=token-not-found');
        }

        try {
            $user = \User_Adapter::getInstance((int) $datas['usr_id'], $appbox);
        } catch (\Exception $e) {
            return $app->redirect('/login/?redirect=/prod&error=user-not-found');
        }

        if ( ! $user->get_mail_locked()) {
            return $app->redirect('/login?redirect=prod&confirm=already');
        }

        $user->set_mail_locked(false);
        \random::removeToken($code);

        if (\PHPMailer::ValidateAddress($user->get_email())) {
            if (count($user->ACL()->get_granted_base()) > 0) {
                \mail::mail_confirm_registered($user->get_email());
            }

            $user->set_mail_locked(false);
            \random::removeToken($code);

            if (\PHPMailer::ValidateAddress($user->get_email())) {

                $appboxRegister = new \appbox_register($appbox);

                $list = $appboxRegister->get_collection_awaiting_for_user($user);

                $others = array();

                foreach ($list as $collection) {
                    $others[] .= $collection->get_name();
                }

                \mail::mail_confirm_unregistered($user->get_email(), $others);
            }
        }

        return $app->redirect('/login?redirect=/prod&confirm=ok');
    }

    /**
     * Submit the new password
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     * @return Response
     */
    public function renewPassword(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];

        if (null !== $mail = trim($request->get('mail'))) {
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

            return $app->redirect('/login/forgot-password/?error=noaccount');
        }

        if ((null !== $token = $request->get('token'))
            && (null !== $password = $request->get('form_password'))
            && (null !== $passwordConfirm = $request->get('form_password_confirm'))) {

            if ($password !== $passwordConfirm) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-match');
            } elseif (strlen(trim($password)) < 5) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-short');
            } elseif (trim($password) != str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $password)) {

                return $app->redirect('/login/forgot-password/?pass-error=pass-invalid');
            }

            try {
                $datas = \random::helloToken($token);

                $user = \User_Adapter::getInstance($datas['usr_id'], $appbox);
                $user->set_password($passwordConfirm);

                \random::removeToken($token);

                return $app->redirect('/login/?confirm=password-update-ok');
            } catch (\Exception_NotFound $e) {

            }
        }
    }

    /**
     * Get the fogot password form
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     * @return Response
     */
    public function displayForgotPasswordForm(Application $app, Request $request)
    {
        $tokenize = false;
        $errorMsg = $request->get('error');

        if (null !== $token = $request->get('token')) {
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

        if (null !== $sentMsg = $request->get('sent')) {
            switch ($sentMsg) {
                case 'ok':
                    $sentMsg = _('phraseanet:: Un email vient de vous etre envoye');
                    break;
            }
        }

        if (null !== $passwordMsg = $request->get('pass-error')) {
            switch ($sentMsg) {
                case 'pass-match':
                    $sentMsg = _('forms::les mots de passe ne correspondent pas');
                    break;
                case 'pass-short':
                    $sentMsg = _('forms::la valeur donnee est trop courte');
                    break;
                case 'pass-invalid':
                    $sentMsg = _('forms::la valeur donnee contient des caracteres invalides');
                    break;
            }
        }

        return new Response($app['phraseanet.core']['Twig']->render('login/forgot-password.html.twig', array(
                    'tokenize'    => $tokenize,
                    'passwordMsg' => $passwordMsg,
                    'errorMsg'    => $errorMsg,
                    'sentMsg'     => $sentMsg
                )));
    }

    /**
     * Get the register form
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function displayRegisterForm(Application $app, Request $request)
    {
        return new Response($app['phraseanet.core']['Twig']->render('login/register.html.twig'));
    }

    /**
     * Logout from Phraseanet
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logout(Application $app, Request $request)
    {
        $appRedirect = $request->get("app");

//        try {
            $session = $app['phraseanet.appbox']->get_session();

            $session->logout();
            $session->remove_cookies();
//        } catch (\Exception $e) {
//            return $app->redirect("/" . ($appRedirect ? $appRedirect : 'prod'));
//        }

        return $app->redirect("/login/?logged_out=user" . ($appRedirect ? sprintf("&redirect=/%s", $appRedirect) : ""));
    }


    public function login(Application $app, Request $request)
    {
        $appbox = $app['phraseanet.appbox'];
        $session = $appbox->get_session();
        $registry = $appbox->get_registry();

        require_once($registry->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');
        if ($registry->get('GV_captchas') && trim($registry->get('GV_captcha_private_key')) !== '' && trim($registry->get('GV_captcha_public_key')) !== '') {
            include($registry->get('GV_RootPath') . 'lib/vendor/recaptcha/recaptchalib.php');
        }

        if ($request->get('postlog')) {
            $session->set_postlog(true);

            return $app->redirect("/login/index.php?redirect=" . $request->get('redirect'));
        }

        if ( ! $session->isset_postlog() && $session->is_authenticated() && $request->get('error') != 'no-connection') {
            return $app->redirect($request->get('redirect', '/prod/'));
        }

        $noconn = false;
        try {
            $conn = $appbox->get_connection();
        } catch (Exception $e) {
            $noconn = true;
        }

        $client = \Browser::getInstance();

        $warning = $notice = '';
        $linkMailConfirm = false;

        if (ctype_digit($request->get('usr'))) {
            $linkMailConfirm = true;
            $errorWarning .= '<div class="notice"><a href="/login/sendmail-confirm.php?usr_id=' . $request->get('usr') . '" target ="_self" style="color:black;text-decoration:none;">' . _('login:: Envoyer a nouveau le mail de confirmation') . '</a></div>';
        }

        switch (true) {
            case $registry->get('GV_maintenance'):
            case $request->get('error') === 'maintenance':
                $warning = _('login::erreur: maintenance en cours, merci de nous excuser pour la gene occasionee');
                break;
            case $noconn:
            case $request->get('error') === 'no-connection':
                $warning = _('login::erreur: No available connection - Please contact sys-admin');
                break;
            case $request->get('error') === 'auth':
                $warning = _('login::erreur: Erreur d\'authentification');
                break;
            case $request->get('error') === 'captcha':
                $warning = _('login::erreur: Erreur de captcha');
                break;
            case $request->get('error') === 'mailNotConfirm' :
                $warning = _('login::erreur: Vous n\'avez pas confirme votre email');
                break;
            case $request->get('error') === 'no-base' :
                $warning = _('login::erreur: Aucune base n\'est actuellment accessible');
                break;
        }
        switch ($request->get('notice')) {
            case 'ok':
                $notice = _('login::register: sujet email : confirmation de votre adresse email') . '</div>';
                break;
            case 'already':
                $notice = _('login::notification: cette email est deja confirmee') . '</div>';
                break;
            case 'mail-sent':
                $notice = _('login::notification: demande de confirmation par mail envoyee') . '</div>';
                break;
            case 'register-ok':
                $notice = _('login::notification: votre email est desormais confirme') . '</div>';
                break;
            case 'register-ok-wait':
                $notice = _('Your email is now confirmed. You will be informed as soon as your pending request will be managed');
                break;
            case 'password-update-ok':
                $notice = _('login::notification: Mise a jour du mot de passe avec succes');
                break;
        }

        $captchaSys = '';
        if ( ! $registry->get('GV_maintenance')
            && $registry->get('GV_captchas')
            && trim($registry->get('GV_captcha_private_key')) !== ''
            && trim($registry->get('GV_captcha_public_key')) !== ''
            && $request->get('error') == 'captcha') {
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

        //$twig = new supertwig(array('Escaper' => false));
        $core = \bootstrap::getCore();
        $twig = $core->getTwig();

        return $twig->render('login/index.twig', array(
            'module_name'    => _('Accueil'),
            'notice'         => $notice,
            'warning'        => $warning,
            'redirect'       => $request->get('redirect'),
            'logged_out'     => $request->get('logged_out'),
            'captcha_system' => $captchaSys,
            'login'          => new \login(),
            'feeds'          => $feeds,
            'display_layout' => $registry->get('GV_home_publi')
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
