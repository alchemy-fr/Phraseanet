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
        $app['controller.admin.dashboard'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAdmin();
        });

        $controllers->get('/', 'controller.admin.dashboard:slash')
            ->bind('admin_dashbord');

        $controllers->post('/flush-cache/', 'controller.admin.dashboard:flush')
            ->bind('admin_dashboard_flush_cache');

        $controllers->post('/send-mail-test/', 'controller.admin.dashboard:sendMail')
            ->bind('admin_dashboard_test_mail');

        $controllers->post('/reset-admin-rights/', 'controller.admin.dashboard:resetAdminRights')
            ->bind('admin_dashboard_reset_admin_rights');

        $controllers->post('/add-admins/', 'controller.admin.dashboard:addAdmins')
            ->bind('admin_dashboard_add_admins');

        return $controllers;
    }

    /**
     * Display admin dashboard page
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
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

        $parameters = [
            'cache_flushed'                 => $request->query->get('flush_cache') === 'ok',
            'admins'                        => \User_Adapter::get_sys_admins($app),
            'email_status'                  => $emailStatus,
        ];

        return $app['twig']->render('admin/dashboard.html.twig', $parameters);
    }

    /**
     * Flush all cash services
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function flush(Application $app, Request $request)
    {
        if ($app['phraseanet.cache-service']->flushAll()) {
            return $app->redirectPath('admin_dashbord', ['flush_cache' => 'ok']);
        }

        return $app->redirectPath('admin_dashbord', ['flush_cache' => 'ko']);
    }

    /**
     * Test a mail address
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function sendMail(Application $app, Request $request)
    {
        if (null === $mail = $request->request->get('email')) {
            $app->abort(400, 'Bad request missing email parameter');
        };

        if (!\Swift_Validate::email($request->request->get('email'))) {
            $app->abort(400, 'Bad request missing email parameter');
        };

        try {
            $receiver = new Receiver(null, $mail);
        } catch (InvalidArgumentException $e) {
            return $app->redirectPath('admin_dashbord', ['email' => 'not-sent']);
        }

        $mail = MailTest::create($app, $receiver);

        $app['notification.deliverer']->deliver($mail);
        $app['swiftmailer.spooltransport']->getSpool()->flushQueue($app['swiftmailer.transport']);

        return $app->redirectPath('admin_dashbord', ['email' => 'sent']);
    }

    /**
     * Reset admin rights
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function resetAdminRights(Application $app, Request $request)
    {
        $app['manipulator.acl']->resetAdminRights(array_map(function ($id) use ($app) {
            return \User_Adapter::getInstance($id, $app);
        }, array_keys(\User_Adapter::get_sys_admins($app))));

        return $app->redirectPath('admin_dashbord');
    }

    /**
     * Grant to an user admin rights
     *
     * @param  Application      $app
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function addAdmins(Application $app, Request $request)
    {
        if (count($admins = $request->request->get('admins', [])) > 0) {

            if (!in_array($app['authentication']->getUser()->get_id(), $admins)) {
                $admins[] = $app['authentication']->getUser()->get_id();
            }

            if ($admins > 0) {
                \User_Adapter::set_sys_admins($app, array_filter($admins));
                $app['manipulator.acl']->resetAdminRights(array_map(function ($id) use ($app) {
                    return \User_Adapter::getInstance($id, $app);
                }, array_keys(\User_Adapter::get_sys_admins($app))));
            }
        }

        return $app->redirectPath('admin_dashbord');
    }
}
