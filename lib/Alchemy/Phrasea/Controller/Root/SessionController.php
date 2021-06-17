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
use Alchemy\Phrasea\Model\Repositories\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SessionController extends Controller
{
    use EntityManagerAware;

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
     * @return SessionRepository
     */
    private function getSessionRepository()
    {
        return $this->app['repo.sessions'];
    }
}
