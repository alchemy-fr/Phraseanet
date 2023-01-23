<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\SessionModule;
use Alchemy\Phrasea\Model\Repositories\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SessionController extends Controller
{
    use EntityManagerAware;

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception       in case "new \DateTime()" fails ?
     */
    public function updateSession(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $this->app->abort(400);
        }

        $ret = [
            'status'  => 'unknown',
            'message' => '',
        ];

        $authenticator = $this->getAuthenticator();
        if ($authenticator->isAuthenticated()) {
            $usr_id = $authenticator->getUser()->getId();
            if ($usr_id != $request->request->get('usr')) { // I logged with another user
                $ret['status'] = 'disconnected';

                return $this->app->json($ret);
            }
        }
        else {
            $ret['status'] = 'disconnected';

            return $this->app->json($ret);
        }

        try {
            $this->getApplicationBox()->get_connection();
        }
        catch (\Exception $e) {
            return $this->app->json($ret);
        }

        if (1 > $moduleId = (int) $request->request->get('module')) {
            $ret['message'] = 'Missing or Invalid `module` parameter';

            return $this->app->json($ret);
        }

        /** @var Session $session */
        $now = new \DateTime();
        $session = $this->getSessionRepository()->find($this->getSession()->get('session_id'));
        $session->setUpdated($now);

        $manager = $this->getEntityManager();
        if (!$session->hasModuleId($moduleId)) {
            $module = new SessionModule();
            $module->setModuleId($moduleId);
            $module->setSession($session);
            $manager->persist($module);
        }
        else {
            $manager->persist($session->getModuleById($moduleId)->setUpdated($now));
        }

        $manager->persist($session);
        $manager->flush();

        $ret['status'] = 'ok';

        if (in_array($this->getSession()->get('phraseanet.message'), ['1', null])) {
            $conf = $this->getConf();
            if ($conf->get(['main', 'maintenance'])) {
                $ret['message'] .= $this->app->trans('The application is going down for maintenance, please logout.');
            }

            if ($conf->get(['registry', 'maintenance', 'enabled'])) {
                $ret['message'] .= strip_tags($conf->get(['registry', 'maintenance', 'message']));
            }
        }

        return $this->app->json($ret);
    }

    /**
     * Deletes identified session
     *
     * @param Request $request
     * @param integer $id
     * @return JsonResponse|RedirectResponse
     */
    public function deleteSession(Request $request, $id)
    {
        $session = $this->getSessionRepository()->find($id);

        if (null === $session) {
            $this->app->abort(404, 'Unknown session');
        }

        if (null === $session->getUser()) {
            $this->app->abort(403, 'Unauthorized');
        }

        if ($session->getUser()->getId() !== $this->getAuthenticatedUser()->getId()) {
            $this->app->abort(403, 'Unauthorized');
        }

        $manager = $this->getEntityManager();
        $manager->remove($session);
        $manager->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->app->json([
                'success' => true,
                'session_id' => $id
            ]);
        }

        return $this->app->redirectPath('account_sessions');
    }

    /**
     * @return Session
     */
    private function getSession()
    {
        return $this->app['session'];
    }

    /**
     * @return SessionRepository
     */
    private function getSessionRepository()
    {
        return $this->app['repo.sessions'];
    }
}
