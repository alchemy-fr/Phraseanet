<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Cache\Cache;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Manipulator\ACLManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Notification\Mail\MailTest;
use Alchemy\Phrasea\Notification\Receiver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    use NotifierAware;

    /**
     * Display admin dashboard page
     *
     * @param  Request $request
     * @return string
     */
    public function slash(Request $request)
    {
        switch ($emailStatus = $request->query->get('email')) {
            case 'sent';
                $emailStatus = $this->app->trans('Mail sent');
                break;
            case 'error':
                $emailStatus = $this->app->trans('Could not send email');
                break;
        }

        return $this->render('admin/dashboard.html.twig', [
            'cache_flushed' => $request->query->get('flush_cache') === 'ok',
            'admins'        => $this->getUserRepository()->findAdmins(),
            'email_status'  => $emailStatus,
        ]);
    }

    /**
     * Flush all cache services
     *
     * @return RedirectResponse
     */
    public function flush()
    {
        /** @var Cache $cache */
        $cache = $this->app['phraseanet.cache-service'];
        $flushOK = $cache->flushAll() ? 'ok' : 'ko';

        return $this->app->redirectPath('admin_dashboard', ['flush_cache' => $flushOK]);
    }

    /**
     * Test a mail address
     *
     * @param  Request $request
     * @return RedirectResponse
     */
    public function sendMail(Request $request)
    {
        if (null === $mail = $request->request->get('email')) {
            $this->app->abort(400, 'Bad request missing email parameter');
        };

        if (!\Swift_Validate::email($mail)) {
            $this->app->abort(400, 'Bad request missing email parameter');
        };

        try {
            $receiver = new Receiver(null, $mail);
        } catch (InvalidArgumentException $e) {
            return $this->app->redirectPath('admin_dashboard', ['email' => 'not-sent']);
        }

        $mail = MailTest::create($this->app, $receiver);

        $this->deliver($mail);

        /** @var \Swift_SpoolTransport $spoolTransport */
        $spoolTransport = $this->app['swiftmailer.spooltransport'];
        /** @var \Swift_Transport $transport */
        $transport = $this->app['swiftmailer.transport'];
        $spoolTransport->getSpool()->flushQueue($transport);

        return $this->app->redirectPath('admin_dashboard', ['email' => 'sent']);
    }

    /**
     * Reset admin rights
     *
     * @return RedirectResponse
     */
    public function resetAdminRights()
    {
        /** @var ACLManipulator $aclManipulator */
        $aclManipulator = $this->app['manipulator.acl'];
        $aclManipulator->resetAdminRights($this->getUserRepository()->findAdmins());

        return $this->app->redirectPath('admin_dashboard');
    }

    /**
     * Grant to an user admin rights
     *
     * @param  Request          $request
     * @return RedirectResponse
     */
    public function addAdmins(Request $request)
    {
        $admins = $request->request->get('admins', []);

        // Remove empty values
        $admins = array_filter($admins);

        if (!is_array($admins) || count($admins) === 0) {
            $this->app->abort(400, '"admins" parameter must contains at least one value.');
        }
        /** @var Authenticator $authenticator */
        $authenticator = $this->app->getAuthenticator();
        if (!in_array($authenticator->getUser()->getId(), $admins)) {
            $admins[] = $authenticator->getUser()->getId();
        }

        $userRepository = $this->getUserRepository();

        $demotedAdmins = [];

        foreach ($userRepository->findAdmins() as $admin) {
            if (!in_array($admin->getId(), $admins)) {
                $demotedAdmins[$admin->getId()] = $admin;
            }
        }

        $userRepository->findBy(['id' => $admins]);
        $admins = array_map(function ($usrId) use ($userRepository) {
            if (null === $user = $userRepository->find($usrId)) {
                throw new RuntimeException(sprintf('Invalid usrId %s provided.', $usrId));
            }

            return $user;
        }, $admins);

        /** @var UserManipulator $userManipulator */
        $userManipulator = $this->app['manipulator.user'];

        $userManipulator->demote($demotedAdmins);
        $userManipulator->promote($admins);

        /** @var ACLManipulator $aclManipulator */
        $aclManipulator = $this->app['manipulator.acl'];
        $aclManipulator->resetAdminRights($admins);

        return $this->app->redirectPath('admin_dashboard');
    }

    /**
     * @return UserRepository
     */
    public function getUserRepository()
    {
        return $this->app['repo.users'];
    }
}
