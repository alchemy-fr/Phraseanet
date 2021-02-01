<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\Helper\WorkZone as WorkzoneHelper;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\StoryWZRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkzoneController extends Controller
{
    use EntityManagerAware;

    public function displayWorkzone(Request $request)
    {
        $sort = $request->query->get('sort');
        // if there is no sort, check user setting
        if ( $sort === '') {
            $sort = $this->getSettings()->getUserSetting($this->getAuthenticatedUser(), 'workzone_order');
            $sort = ($sort != null) ? $sort : '';
        }

        return $this->render('prod/WorkZone/WorkZone.html.twig', [
            'WorkZone'      => new WorkzoneHelper($this->app, $request),
            'selected_type' => $request->query->get('type'),
            'selected_id'   => $request->query->get('id'),
            'srt'           => $sort,
        ]);
    }

    public function browse()
    {
        return $this->render('prod/WorkZone/Browser/Browser.html.twig');
    }

    public function browserSearch(Request $request)
    {
        $basketRepo = $this->getBasketRepository();

        $page = (int) $request->query->get('Page', 0);

        $perPage = 10;
        $offsetStart = max(($page - 1) * $perPage, 0);

        $baskets = $basketRepo->findWorkzoneBasket(
            $this->getAuthenticatedUser(),
            $request->query->get('Query'),
            $request->query->get('Year'),
            $request->query->get('Type'),
            $offsetStart,
            $perPage
        );

        $page = floor($offsetStart / $perPage) + 1;
        $maxPage = floor(count($baskets) / $perPage) + 1;

        return $this->render('prod/WorkZone/Browser/Results.html.twig', [
            'Baskets' => $baskets,
            'Page'    => $page,
            'MaxPage' => $maxPage,
            'Total'   => count($baskets),
            'Query'   => $request->query->get('Query'),
            'Year'    => $request->query->get('Year'),
            'Type'    => $request->query->get('Type'),
        ]);
    }

    public function browseBasket(Basket $basket)
    {
        return $this->render('prod/WorkZone/Browser/Basket.html.twig', ['Basket' => $basket]);
    }

    public function attachStories(Request $request)
    {
        if (!$request->request->get('stories')) {
            throw new BadRequestHttpException('Missing parameters stories');
        }

        $storyWZRepo = $this->getStoryWZRepository();

        $alreadyFixed = $done = 0;

        $stories = $request->request->get('stories', []);

        $user = $this->getAuthenticatedUser();
        $acl = $this->getAclForUser($user);
        $manager = $this->getEntityManager();
        foreach ($stories as $element) {
            $element = explode('_', $element);
            $story = new \record_adapter($this->app, $element[0], $element[1]);

            if (!$story->isStory()) {
                throw new \Exception('You can only attach stories');
            }

            if (!$acl->has_access_to_base($story->getBaseId())) {
                throw new AccessDeniedHttpException('You do not have access to this Story');
            }

            if ($storyWZRepo->findUserStory($this->app, $user, $story)) {
                $alreadyFixed++;
                continue;
            }

            $storyWZ = new StoryWZ();
            $storyWZ->setUser($user);
            $storyWZ->setRecord($story);

            $manager->persist($storyWZ);
            $done++;
        }

        $manager->flush();

        if ($alreadyFixed === 0) {
            if ($done <= 1) {
                $message = $this->app->trans('%quantity% Story attached to the WorkZone', ['%quantity%' => $done]);
            } else {
                $message = $this->app->trans('%quantity% Stories attached to the WorkZone', ['%quantity%' => $done]);
            }
        } else {
            if ($done <= 1) {
                $message = $this->app->trans('%quantity% Story attached to the WorkZone, %quantity_already% already attached', ['%quantity%' => $done, '%quantity_already%' => $alreadyFixed]);
            } else {
                $message = $this->app->trans('%quantity% Stories attached to the WorkZone, %quantity_already% already attached', ['%quantity%' => $done, '%quantity_already%' => $alreadyFixed]);
            }
        }

        if ($request->getRequestFormat() == 'json') {
            return $this->app->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    public function detachStory(Request $request, $sbas_id, $record_id)
    {
        $story = new \record_adapter($this->app, $sbas_id, $record_id);

        $repository = $this->getStoryWZRepository();

        $storyWZ = $repository->findUserStory($this->app, $this->getAuthenticatedUser(), $story);

        if (!$storyWZ) {
            throw new NotFoundHttpException('Story not found');
        }

        $manager = $this->getEntityManager();
        $manager->remove($storyWZ);
        $manager->flush();

        if ($request->getRequestFormat() == 'json') {
            return $this->app->json([
                'success' => true,
                'message' => $this->app->trans('Story detached from the WorkZone'),
            ]);
        }

        return $this->app->redirectPath('prod_workzone_show');
    }

    /**
     * @return BasketRepository
     */
    private function getBasketRepository()
    {
        return $this->app['repo.baskets'];
    }

    /**
     * @return StoryWZRepository
     */
    private function getStoryWZRepository()
    {
        return $this->app['repo.story-wz'];
    }

    /**
     * @return DisplaySettingService
     */
    private function getSettings()
    {
        return $this->app['settings'];
    }
}
