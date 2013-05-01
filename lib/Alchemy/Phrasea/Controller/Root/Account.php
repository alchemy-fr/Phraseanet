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
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailUpdate;
use Alchemy\Phrasea\Form\Login\PhraseaRenewPasswordForm;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Account implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
            $app['firewall']->requireAuthentication();
            $app['twig.form.templates'] = array('login/common/form_div_layout.html.twig');
        });

        /**
         * Get a new account
         *
         * name         : get_account
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('displayAccount'))
            ->bind('account');

        /**
         * Update account route
         *
         * description  : update your account informations
         *
         * method       : POST
         *
         * parameters   :
         *  'form_gender'
         *  'form_lastname'
         *  'form_firstname'
         *  'form_job'
         *  'form_lastname'
         *  'form_company'
         *  'form_function'
         *  'form_activity'
         *  'form_phone'
         *  'form_fax'
         *  'form_address'
         *  'form_zip_code'
         *  'form_geoname_id'
         *  'form_dest_ftp'
         *  'form_default_data_ftp'
         *  'form_prefix_ftp_folder'
         *  'form_notice'
         *  'form_bases'
         *  'form_mail_notifications'
         *  'form_request_notifications'
         *  'form_demand'
         *  'form_notifications'
         *  'form_active_ftp'
         *  'form_address_ftp'
         *  'form_login_ftp'
         *  'form_password_ftp'
         *  'form_pass_if_ftp'
         *  'form_retry_ftp'
         *
         *
         * return       : HTML Response
         */
        $controllers->post('/', $this->call('updateAccount'))
            ->bind('submit_update_account');

        /**
         * Get reset email
         *
         * name         : account_reset_email
         *
         * description  : Display reset email form
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/reset-email/', $this->call('displayResetEmailForm'))
            ->bind('account_reset_email');

        /**
         * Give account access
         *
         * name         : account_access
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/access/', $this->call('accountAccess'))
            ->bind('account_access');

        /**
         * Give authorized applications that can access user informations
         *
         * name         : reset_email
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/reset-email/', $this->call('resetEmail'))
            ->bind('reset_email');

        /**
         * Display the form to renew a password
         *
         * name         : reset_password
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->match('/reset-password/', $this->call('resetPassword'))
            ->bind('reset_password');

        /**
         * Give account open sessions
         *
         * name         : account_sessions
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/security/sessions/', $this->call('accountSessionsAccess'))
            ->bind('account_sessions');

        /**
         * Give authorized applications that can access user informations
         *
         * name         : account_auth_apps
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/security/applications/', $this->call('accountAuthorizedApps'))
            ->bind('account_auth_apps');

        /**
         * Grant access to an authorized app
         *
         * name         : grant_app_access
         *
         * description  : Display form to create a new account
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/security/application/{application_id}/grant/', $this->call('grantAccess'))
            ->assert('application_id', '\d+')
            ->bind('grant_app_access');

        return $controllers;
    }

    /**
     * Reset Password
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function resetPassword(Application $app, Request $request)
    {
        $form = $app->form(new PhraseaRenewPasswordForm());

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $password = $data['password'];
                $passwordConfirm = $data['passwordConfirm'];

                $user = $app['authentication']->getUser();

                if ($password !== $passwordConfirm) {
                    $app->addFlash('error', _('forms::les mots de passe ne correspondent pas'));
                } elseif (strlen(trim($password)) < 5) {
                    $app->addFlash('error', _('forms::la valeur donnee est trop courte'));
                } elseif (trim($password) != str_replace(array("\r\n", "\n", "\r", "\t", " "), "_", $password)) {
                    $app->addFlash('error', _('forms::la valeur donnee contient des caracteres invalides'));
                } elseif ($app['auth.password-encoder']->isPasswordValid($user->get_password(), $data['oldPassword'], $user->get_nonce())) {
                    $user->set_password($passwordConfirm);
                    $app->addFlash('success', _('login::notification: Mise a jour du mot de passe avec succes'));
                    return $app->redirect($app->path('account'));
                } else {
                    $app->addFlash('error', _('Invalid password provided'));
                }
            }
        }

        return $app['twig']->render('account/change-password.html.twig', array(
            'form' => $form->createView(),
            'login' => new \login(),
        ));
    }

    /**
     * Reset Email
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function resetEmail(PhraseaApplication $app, Request $request)
    {
        if (null === ($password = $request->request->get('form_password'))
            || null === ($email = $request->request->get('form_email'))
            || null === ($emailConfirm = $request->request->get('form_email_confirm'))) {

            $app->abort(400, _('Could not perform request, please contact an administrator.'));
        }

        $user = $app['authentication']->getUser();

        if (!$app['auth.password-encoder']->isPasswordValid($user->get_password(), $password, $user->get_nonce())) {
            $app->addFlash('error', _('admin::compte-utilisateur:ftp: Le mot de passe est errone'));

            return $app->redirect($app->path('account_reset_email'));
        }

        if (!\Swift_Validate::email($email)) {
            $app->addFlash('error', _('forms::l\'email semble invalide'));

            return $app->redirect($app->path('account_reset_email'));
        }

        if ($email !== $emailConfirm) {
            $app->addFlash('error', _('forms::les emails ne correspondent pas'));

            return $app->redirect($app->path('account_reset_email'));
        }

        $date = new \DateTime('1 day');
        $token = $app['tokens']->getUrlToken(\random::TYPE_EMAIL, $app['authentication']->getUser()->get_id(), $date, $app['authentication']->getUser()->get_email());
        $url = $app['phraseanet.registry']->get('GV_ServerName') . 'account/reset-email/?token=' . $token;

        try {
            $receiver = Receiver::fromUser($app['authentication']->getUser());
        } catch (InvalidArgumentException $e) {
            $app->addFlash('error', _('phraseanet::erreur: echec du serveur de mail'));

            return $app->redirect($app->path('account_reset_email'));
        }

        $mail = MailRequestEmailUpdate::create($app, $receiver, null);
        $mail->setButtonUrl($url);
        $mail->setExpiration($date);

        $app['notification.deliverer']->deliver($mail);

        $app->addFlash('info', _('admin::compte-utilisateur un email de confirmation vient de vous etre envoye. Veuillez suivre les instructions contenue pour continuer'));

        return $app->redirect($app->path('account'));
    }

    /**
     * Display reset email form
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function displayResetEmailForm(Application $app, Request $request)
    {
        if (null !== $token = $request->query->get('token')) {
            try {
                $datas = $app['tokens']->helloToken($token);
                $user = \User_Adapter::getInstance((int) $datas['usr_id'], $app);
                $user->set_email($datas['datas']);
                $app['tokens']->removeToken($token);

                $app->addFlash('success', _('admin::compte-utilisateur: L\'email a correctement ete mis a jour'));

                return $app->redirect($app->path('account'));
            } catch (\Exception $e) {
                $app->addFlash('error', _('admin::compte-utilisateur: erreur lors de la mise a jour'));

                return $app->redirect($app->path('account'));
            }
        }

        return $app['twig']->render('account/reset-email.html.twig');
    }

    /**
     * Display authorized applications that can access user informations
     *
     * @param Application $app            A Silex application where the controller is mounted on
     * @param Request     $request        The current request
     * @param Integer     $application_id The application id
     *
     * @return JsonResponse
     */
    public function grantAccess(Application $app, Request $request, $application_id)
    {
        if (!$request->isXmlHttpRequest() || !array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $error = false;

        try {
            $account = \API_OAuth2_Account::load_with_user(
                    $app
                    , new \API_OAuth2_Application($app, $application_id)
                    , $app['authentication']->getUser()
            );

            $account->set_revoked((bool) $request->query->get('revoke'), false);
        } catch (\Exception_NotFound $e) {
            $error = true;
        }

        return $app->json(array('success' => !$error));
    }

    /**
     * Display account base access
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function accountAccess(Application $app, Request $request)
    {
        require_once $app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php';

        return $app['twig']->render('account/access.html.twig', array(
            'inscriptions' => giveMeBases($app, $app['authentication']->getUser()->get_id())
        ));
    }

    /**
     * Display authorized applications that can access user informations
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function accountAuthorizedApps(Application $app, Request $request)
    {
        return $app['twig']->render('account/authorized_apps.html.twig', array(
            "applications" => \API_OAuth2_Application::load_app_by_user($app, $app['authentication']->getUser()),
        ));
    }

    /**
     * Display account session accesss
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function accountSessionsAccess(Application $app, Request $request)
    {
        $dql = 'SELECT s FROM Entities\Session s
            WHERE s.usr_id = :usr_id
            ORDER BY s.created DESC';

        $query = $app['EM']->createQuery($dql);
        $query->setParameters(array('usr_id'  => $app['session']->get('usr_id')));
        $sessions = $query->getResult();

        return $app['twig']->render('account/sessions.html.twig', array('sessions' => $sessions));
    }

    /**
     * Display account form
     *
     * @param  Application $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function displayAccount(Application $app, Request $request)
    {
        return $app['twig']->render('account/account.html.twig', array(
            'user'          => $app['authentication']->getUser(),
            'evt_mngr'      => $app['events-manager'],
            'notifications' => $app['events-manager']->list_notifications_available($app['authentication']->getUser()->get_id()),
        ));
    }

    /**
     * Update account informations
     *
     * @param  PhraseaApplication $app     A Silex application where the controller is mounted on
     * @param  Request     $request The current request
     * @return Response
     */
    public function updateAccount(PhraseaApplication $app, Request $request)
    {
        $demands = (array) $request->request->get('demand', array());

        if (0 !== count($demands)) {
            $register = new \appbox_register($app['phraseanet.appbox']);

            foreach ($demands as $baseId) {
                try {
                    $register->add_request($app['authentication']->getUser(), \collection::get_from_base_id($app, $baseId));
                    $app->addFlash('success', _('login::notification: Vos demandes ont ete prises en compte'));
                } catch (\Exception $e) {

                }
            }
        }

        $accountFields = array(
            'form_gender',
            'form_firstname',
            'form_lastname',
            'form_address',
            'form_zip',
            'form_phone',
            'form_fax',
            'form_function',
            'form_company',
            'form_activity',
            'form_geonameid',
            'form_addrFTP',
            'form_loginFTP',
            'form_pwdFTP',
            'form_destFTP',
            'form_prefixFTPfolder',
            'form_retryFTP'
        );

        if (0 === count(array_diff($accountFields, array_keys($request->request->all())))) {
            $defaultDatas = 0;

            if ($datas = (array) $request->request->get("form_defaultdataFTP", array())) {
                if (in_array('document', $datas)) {
                    $defaultDatas += 4;
                }

                if (in_array('preview', $datas)) {
                    $defaultDatas += 2;
                }

                if (in_array('caption', $datas)) {
                    $defaultDatas += 1;
                }
            }

            try {
                $app['phraseanet.appbox']->get_connection()->beginTransaction();

                $app['authentication']->getUser()->set_gender($request->request->get("form_gender"))
                    ->set_firstname($request->request->get("form_firstname"))
                    ->set_lastname($request->request->get("form_lastname"))
                    ->set_address($request->request->get("form_address"))
                    ->set_zip($request->request->get("form_zip"))
                    ->set_tel($request->request->get("form_phone"))
                    ->set_fax($request->request->get("form_fax"))
                    ->set_job($request->request->get("form_activity"))
                    ->set_company($request->request->get("form_company"))
                    ->set_position($request->request->get("form_function"))
                    ->set_geonameid($request->request->get("form_geonameid"))
                    ->set_mail_notifications((bool) $request->request->get("mail_notifications"))
                    ->set_activeftp($request->request->get("form_activeFTP"))
                    ->set_ftp_address($request->request->get("form_addrFTP"))
                    ->set_ftp_login($request->request->get("form_loginFTP"))
                    ->set_ftp_password($request->request->get("form_pwdFTP"))
                    ->set_ftp_passif($request->request->get("form_passifFTP"))
                    ->set_ftp_dir($request->request->get("form_destFTP"))
                    ->set_ftp_dir_prefix($request->request->get("form_prefixFTPfolder"))
                    ->set_defaultftpdatas($defaultDatas);

                $app->addFlash('success', _('login::notification: Changements enregistres'));
                $app['phraseanet.appbox']->get_connection()->commit();
            } catch (Exception $e) {
                $app->addFlash('error', _('forms::erreurs lors de l\'enregistrement des modifications'));
                $app['phraseanet.appbox']->get_connection()->rollBack();
            }
        }

        $requestedNotifications = (array) $request->request->get('notifications', array());

        foreach ($app['events-manager']->list_notifications_available($app['authentication']->getUser()->get_id()) as $notifications) {
            foreach ($notifications as $notification) {
                $notifId = $notification['id'];
                $notifName = sprintf('notification_%d', $notifId);

                if (isset($requestedNotifications[$notifId])) {
                    $app['authentication']->getUser()->setPrefs($notifName, '1');
                } else {
                    $app['authentication']->getUser()->setPrefs($notifName, '0');
                }
            }
        }

        return $app->redirect($app->path('account'));
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
