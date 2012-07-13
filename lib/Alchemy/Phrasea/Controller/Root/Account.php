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
use Symfony\Component\HttpFoundation\JsonResponse;

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
                $app['Core']['Firewall']->requireAuthentication($app);
            });

        /**
         * New account route
         *
         * name         : display_account
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
            ->bind('display_account');

        /**
         * Create account route
         *
         * name         : update_account
         *
         * description  : update your account informations
         *
         * method       : POST
         *
         * parameters   :
         *  'gender'
         *  'lastname'
         *  'firstname'
         *  'job'
         *  'lastname'
         *  'company'
         *  'function'
         *  'activity'
         *  'phone'
         *  'fax'
         *  'address'
         *  'zip_code'
         *  'geoname_id'
         *  'dest_ftp'
         *  'default_data_ftp'
         *  'prefix_ftp_folder'
         *  'notice'
         *  'bases'
         *  'mail_notifications'
         *  'request_notifications'
         *  'demand'
         *  'notifications'
         *  'active_ftp'
         *  'address_ftp'
         *  'login_ftp'
         *  'password_ftp'
         *  'pass_if_ftp'
         *  'retry_ftp'
         *
         *
         * return       : HTML Response
         */
        $controllers->post('/', $this->call('updateAccount'))
            ->bind('update_account');

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
     * Display authorized applications that can access user informations
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function grantAccess(Application $app, Request $request, $application_id)
    {
        if ( ! $request->isXmlHttpRequest() || ! array_key_exists($request->getMimeType('json'), array_flip($request->getAcceptableContentTypes()))) {
            $app->abort(400, _('Bad request format, only JSON is allowed'));
        }

        $appbox = \appbox::get_instance($app['Core']);
        $error = false;

        try {
            $account = \API_OAuth2_Account::load_with_user(
                    $appbox
                    , new \API_OAuth2_Application($appbox, $application_id)
                    , $app['Core']->getAuthenticatedUser()
            );
        } catch (\Exception_NotFound $e) {
            $error = true;
        }

        $account->set_revoked((bool) $request->get('revoke'), false);

        return new JsonResponse(array('success' => ! $error));
    }

    /**
     * Display authorized applications that can access user informations
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function accountAuthorizedApps(Application $app, Request $request)
    {
        $user = $app['Core']->getAuthenticatedUser();

        return $app['Core']['Twig']->render('account/authorized_apps.html.twig', array(
                "apps" => \API_OAuth2_Application::load_app_by_user(\appbox::get_instance($app['Core']), $user),
                'user' => $user
            ));
    }

    /**
     * Display account session accesss
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function accountSessionsAccess(Application $app, Request $request)
    {
        return new Response($app['Core']['Twig']->render('account/sessions.html.twig'));
    }

    /**
     * Display account base access
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function accountAccess(Application $app, Request $request)
    {
        require_once $app['Core']['Registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php';

        $user = $app['Core']->getAuthenticatedUser();

        return new Response($app['Core']['Twig']->render('account/access.html.twig', array(
                    'inscriptions' => giveMeBases($user->get_id())
                )));
    }

    /**
     * Display account form
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function displayAccount(Application $app, Request $request)
    {
        $appbox = \appbox::get_instance($app['Core']);
        $user = $app['Core']->getAuthenticatedUser();
        $evtMngr = \eventsmanager_broker::getInstance($appbox, $app['Core']);

        switch ($notice = $request->get('notice', '')) {
            case 'password-update-ok':
                $notice = _('login::notification: Mise a jour du mot de passe avec succes');
                break;
            case 'account-update-ok':
                $notice = _('login::notification: Changements enregistres');
                break;
            case 'account-update-bad':
                $notice = _('forms::erreurs lors de l\'enregistrement des modifications');
                break;
            case 'demand-ok':
                $notice = _('login::notification: Vos demandes ont ete prises en compte');
                break;
        }

        return new Response($app['Core']['Twig']->render('account/account.html.twig', array(
                    'geonames'      => new \geonames(),
                    'user'          => $user,
                    'notice'        => $notice,
                    'evt_mngr'      => $evtMngr,
                    'notifications' => $evtMngr->list_notifications_available($user->get_id()),
                )));
    }

    /**
     * Update account informations
     *
     * @param Application $app     A Silex application where the controller is mounted on
     * @param Request     $request The current request
     *
     * @return Response
     */
    public function updateAccount(Application $app, Request $request)
    {
        $appbox = \appbox::get_instance($app['Core']);
        $user = $app['Core']->getAuthenticatedUser();
        $evtMngr = \eventsmanager_broker::getInstance($appbox, $app['Core']);
        $notice = 'account-update-bad';

        $demands = (array) $request->get('demand', array());

        if (0 === count($demands)) {
            $register = new \appbox_register($appbox);

            foreach ($demands as $baseId) {
                try {
                    $register->add_request($user, \collection::get_from_base_id($baseId));
                    $notice = 'demand-ok';
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
            'form_prefixFTPfolder'
        );

        if (0 === count(array_diff($accountFields, array_keys($request->request->all())))) {
            $defaultDatas = 0;

            if ($datas = (array) $request->get("form_defaultdataFTP", array())) {
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
                $appbox->get_connection()->beginTransaction();

                $user->set_gender($request->get("form_gender"))
                    ->set_firstname($request->get("form_firstname"))
                    ->set_lastname($request->get("form_lastname"))
                    ->set_address($request->get("form_address"))
                    ->set_zip($request->get("form_zip"))
                    ->set_tel($request->get("form_phone"))
                    ->set_fax($request->get("form_fax"))
                    ->set_job($request->get("form_activity"))
                    ->set_company($request->get("form_company"))
                    ->set_position($request->get("form_function"))
                    ->set_geonameid($request->get("form_geonameid"))
                    ->set_mail_notifications((bool) $request->get("mail_notifications"))
                    ->set_activeftp($request->get("form_activeFTP"))
                    ->set_ftp_address($request->get("form_addrFTP"))
                    ->set_ftp_login($request->get("form_loginFTP"))
                    ->set_ftp_password($request->get("form_pwdFTP"))
                    ->set_ftp_passif($request->get("form_passifFTP"))
                    ->set_ftp_dir($request->get("form_destFTP"))
                    ->set_ftp_dir_prefix($request->get("form_prefixFTPfolder"))
                    ->set_defaultftpdatas($defaultDatas);

                $appbox->get_connection()->commit();

                $notice = 'account-update-ok';
            } catch (Exception $e) {
                $appbox->get_connection()->rollBack();
            }
        }

        $requestedNotifications = (array) $request->get('notifications', array());

        foreach ($evtMngr->list_notifications_available($user->get_id()) as $notifications) {
            foreach ($notifications as $notification) {
                $notifId = (int) $notification['id'];
                $notifName = sprintf('notification_%d', $notifId);

                if (isset($requestedNotifications[$notifId])) {
                    $user->setPrefs($notifName, '1');
                } else {
                    $user->setPrefs($notifName, '0');
                }
            }
        }

        return $app->redirect(sprintf('/account/?notice=%s', $notice), 201);
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
