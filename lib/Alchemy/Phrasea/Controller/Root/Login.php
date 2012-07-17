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
        $appbox = \appbox::get_instance($app['Core']);

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
        $appbox = \appbox::get_instance($app['Core']);

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
        $appbox = \appbox::get_instance($app['Core']);

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
                $url = sprintf('%slogin/forgot-password/?token=%s', $app['Core']['Registry']->get('GV_ServerName'), $token);

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

        return new Response($app['Core']['Twig']->render('login/forgot-password.html.twig', array(
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
        return new Response($app['Core']['Twig']->render('login/register.html.twig'));
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

        try {
            $appbox = \appbox::get_instance($app['Core']);
            $session = $appbox->get_session();

            $session->logout();
            $session->remove_cookies();
        } catch (\Exception $e) {
            return $app->redirect("/" . ($appRedirect ? $appRedirect : 'prod'));
        }

        return $app->redirect("/login/?logged_out=user" . ($appRedirect ? sprintf("&redirect=/%s", $appRedirect) : ""));
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
