<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailTest;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAdmin();
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
        $controllers->get('/', $this->call('slash'))
            ->bind('admin_dashbord');

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
        $controllers->post('/flush-cache/', $this->call('flush'))
            ->bind('admin_dashboard_flush_cache');

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
        $controllers->post('/send-mail-test/', $this->call('sendMail'))
            ->bind('admin_dashboard_test_mail');

        /**
         * Reset admin rights
         *
         * name         : admin_dashboard_reset_admin_rights
         *
         * description  : Reset admin rights
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : Redirect Response
         */
        $controllers->post('/reset-admin-rights/', $this->call('resetAdminRights'))
            ->bind('admin_dashboard_reset_admin_rights');

        /**
         * Add admins
         *
         * name         : admin_dashboard_new
         *
         * description  : Add new admin_dashboard_add_admins
         *
         * method       : POST
         *
         * parameters   : admins An array of user id admins
         *
         * return       : Redirect Response
         */
        $controllers->post('/add-admins/', $this->call('addAdmins'))
            ->bind('admin_dashboard_add_admins');

        return $controllers;
    }

    /**
     * Display admin dashboard page
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  Response
     */
    public function slash(Application $app, Request $request)
    {
        switch ($emailStatus = $request->query->get('email')) {
            case 'sent';
                $emailStatus = _('Mail sent');
                break;
            case 'error':
                $emailStatus = _('Could not send email');
                break;
        }

        $parameters = array(
            'cache_flushed'                 => $request->query->get('flush_cache') === 'ok',
            'admins'                        => \User_Adapter::get_sys_admins($app),
            'email_status'                  => $emailStatus,
            'search_engine_status'          => $app['phraseanet.SE']->getStatus(),
            'php_version_constraints'       => \setup::check_php_version(),
            'writability_constraints'       => \setup::check_writability($app['phraseanet.registry']),
            'binaries_constraints'          => \setup::check_binaries($app['phraseanet.registry']),
            'php_extension_constraints'     => \setup::check_php_extension(),
            'cache_constraints'             => \setup::check_cache_server(),
            'phrasea_constraints'           => \setup::check_phrasea(),
            'cache_opcode_constraints'      => \setup::check_cache_opcode(),
            'php_configuration_constraints' => \setup::check_php_configuration(),
        );

        return $app['twig']->render('admin/dashboard.html.twig', $parameters);
    }

    /**
     * Flush all cash services
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  RedirectResponse
     */
    public function flush(Application $app, Request $request)
    {
        if ($app['phraseanet.cache-service']->flushAll()) {

            return $app->redirect('/admin/dashboard/?flush_cache=ok');
        }

        return $app->redirect('/admin/dashboard/?flush_cache=ko');
    }

    /**
     * Test a mail address
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  RedirectResponse
     */
    public function sendMail(Application $app, Request $request)
    {
        if (null === $mail = $request->request->get('email')) {
            $app->abort(400, 'Bad request missing email parameter');
        };

        if (\Swift_Validate::email($request->request->get('email'))) {
            $app->abort(400, 'Bad request missing email parameter');
        };

        try {
            $receiver = new Receiver(null, $mail);
        } catch (InvalidArgumentException $e) {
            return $app->redirect('/admin/dashboard/?email=not-sent');
        }

        $mail = MailTest::create($app, $receiver);

        $app['notification.deliverer']->deliver($mail);
        $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);

        return $app->redirect('/admin/dashboard/?email=sent');
    }

    /**
     * Reset admin rights
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  RedirectResponse
     */
    public function resetAdminRights(Application $app, Request $request)
    {
        \User_Adapter::reset_sys_admins_rights($app);

        return $app->redirect('/admin/dashboard/');
    }

    /**
     * Grant to an user admin rights
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  RedirectResponse
     */
    public function addAdmins(Application $app, Request $request)
    {
        if (count($admins = $request->request->get('admins', array())) > 0) {

            if (!in_array($app['phraseanet.user']->get_id(), $admins)) {
                $admins[] = $app['phraseanet.user']->get_id();
            }

            if ($admins > 0) {
                \User_Adapter::set_sys_admins($app, array_filter($admins));
                \User_Adapter::reset_sys_admins_rights($app);
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
