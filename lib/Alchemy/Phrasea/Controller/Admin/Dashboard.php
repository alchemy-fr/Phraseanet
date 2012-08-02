<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Dashboard implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function() use ($app) {
                return $app['phraseanet.core']['Firewall']->requireAdmin($app);
            });

        /**
         * Get admin dashboard
         *
         * name         : admin_dashbord
         *
         * description  : Display admin dashboard
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('slash'))->bind('admin_dashbord');

        /**
         * Reset cache
         *
         * name         : admin_dashboard_flush_cache
         *
         * description  : Reset all cache
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/flush-cache/', $this->call('flush'))->bind('admin_dashboard_flush_cache');

        /**
         * Test send mail
         *
         * name         : admin_dashboard_test_mail
         *
         * description  : Test send mail
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/send-mail-test/', $this->call('sendMail'))->bind('admin_dashboard_test_mail');

        /**
         * Reset admin rights
         *
         * name         : admin_dashboard_reset_rights
         *
         * description  : Reset admin rights
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/reset-admin-rights/', $this->call('resetAdminRights'))->bind('admin_dashboard_reset_rights');

        /**
         * add admins
         *
         * name         : admin_dashboard_new
         *
         * description  : Add new admins
         *
         * method       : POST
         *
         * parameters   : admins An array of user id admins
         *
         * return       : Redirect Response
         */
        $controllers->post('/new/', $this->call('addAdmins'))->bind('admin_dashboard_new');

        return $controllers;
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function slash(Application $app, Request $request)
    {
        if ((false !== $cacheFlushed = $request->get('flush_cache', false)) && $cacheFlushed === 'ok') {
            $cacheFlushed = true;
        }

        switch ($emailStatus = $request->get('email')) {
            case 'sent';
                $emailStatus = _('Mail sent');
                break;
            case 'error':
                $emailStatus = _('Could not send email');
                break;
        }

        try {
            $engine = new \searchEngine_adapter($app['phraseanet.core']['Registry']);
            $searchEngineStatus = $engine->get_status();
        } catch (\Exception $e) {
            $searchEngineStatus = null;
        }

        $parameters = array(
            'cache_flushed'                 => $cacheFlushed,
            'admins'                        => \User_Adapter::get_sys_admins(),
            'email_status'                  => $emailStatus,
            'search_engine_status'          => $searchEngineStatus,
            'php_version_constraints'       => \setup::check_php_version(),
            'writability_constraints'       => \setup::check_writability($app['phraseanet.core']['Registry']),
            'binaries_constraints'          => \setup::check_binaries($app['phraseanet.core']['Registry']),
            'php_extension_constraints'     => \setup::check_php_extension(),
            'cache_constraints'             => \setup::check_cache_server(),
            'phrasea_constraints'           => \setup::check_phrasea(),
            'cache_opcode_constraints'      => \setup::check_cache_opcode(),
            'php_configuration_constraints' => \setup::check_php_configuration(),
        );

        return new Response($app['phraseanet.core']['Twig']->render('admin/dashboard.html.twig', $parameters));
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function flush(Application $app, Request $request)
    {
        if ($app['phraseanet.core']['CacheService']->flushAll()) {

            return $app->redirect('/login/dashboard/?flush_cache=ok');
        }

        return $app->redirect('/login/dashboard/?flush_cache=ko');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sendMail(Application $app, Request $request)
    {
        if (null === $mail = $request->get('email')) {
            $app->abort(400, 'Bad request missing email parameter');
        };

        if (\mail::mail_test($mail)) {

            return $app->redirect('/login/dashboard/?email=sent');
        }

        return $app->redirect('/login/dashboard/?email=error');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resetAdminRights(Application $app, Request $request)
    {
        \User_Adapter::reset_sys_admins_rights();

        return $app->redirect('/login/dashboard/');
    }

    /**
     *
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addAdmins(Application $app, Request $request)
    {
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        if (count($admins = array_filter($request->get('admins', array()))) > 0) {

            if ( ! in_array($user->get_id(), $admins)) {
                $admins[] = $user->get_id();
            }

            if ($admins > 0) {
                \User_Adapter::set_sys_admins($admins);
                \User_Adapter::reset_sys_admins_rights();
            }
        }

        return $app->redirect('/admin/dashboard/');
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
