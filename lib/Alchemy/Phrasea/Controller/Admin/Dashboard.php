<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailTest;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
                $emailStatus = $app->trans('Mail sent');
                break;
            case 'error':
                $emailStatus = $app->trans('Could not send email');
                break;
        }

        $parameters = [
            'cache_flushed'                 => $request->query->get('flush_cache') === 'ok',
            'admins'                        => $app['repo.users']->findAdmins(),
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
        $app['manipulator.acl']->resetAdminRights($app['repo.users']->findAdmins());

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
        $admins = $request->request->get('admins', []);
        if (count($admins) === 0 || !is_array($admins)) {
            $app->abort(400, '"admins" parameter must contains at least one value.');
        }
        if (!in_array($app['authentication']->getUser()->getId(), $admins)) {
            $admins[] = $app['authentication']->getUser()->getId();
        }

        $admins = array_map(function ($usrId) use ($app) {
            if (null === $user = $app['repo.users']->find($usrId)) {
                throw new RuntimeException(sprintf('Invalid usrId %s provided.', $usrId));
            }

            return $user;
        }, $admins);

        $app['manipulator.user']->promote($admins);
        $app['manipulator.acl']->resetAdminRights($admins);

        return $app->redirectPath('admin_dashbord');
    }
}
