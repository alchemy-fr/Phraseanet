<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Alchemy\Phrasea\Helper\WorkZone as WorkzoneHelper;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkZone implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.workzone'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers
            // Silex\Route::convert is not used as this should be done prior the before middleware
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access']);;

        $controllers->get('/', 'controller.prod.workzone:displayWorkzone')
            ->bind('prod_workzone_show');

        $controllers->get('/Browse/', 'controller.prod.workzone:browse')
            ->bind('prod_workzone_browse');

        $controllers->get('/Browse/Search/', 'controller.prod.workzone:browserSearch')
            ->bind('prod_workzone_search');

        $controllers->get('/Browse/Basket/{basket}/', 'controller.prod.workzone:browseBasket')
            ->bind('prod_workzone_basket')
            ->assert('basket', '\d+');

        $controllers->post('/attachStories/', 'controller.prod.workzone:attachStories');

        $controllers->post('/detachStory/{sbas_id}/{record_id}/', 'controller.prod.workzone:detachStory')
            ->bind('prod_workzone_detach_story')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }

    public function displayWorkzone(Application $app)
    {
        $params = [
            'WorkZone'      => new WorkzoneHelper($app, $app['request'])
            , 'selected_type' => $app['request']->query->get('type')
            , 'selected_id'   => $app['request']->query->get('id')
            , 'srt'           => $app['request']->query->get('sort')
        ];

        return $app['twig']->render('prod/WorkZone/WorkZone.html.twig', $params);
    }

    public function browse(Application $app)
    {
        return $app['twig']->render('prod/WorkZone/Browser/Browser.html.twig');
    }

    public function browserSearch(Application $app)
    {
        $request = $app['request'];

        $BasketRepo = $app['repo.baskets'];

        $Page = (int) $request->query->get('Page', 0);

        $PerPage = 10;
        $offsetStart = max(($Page - 1) * $PerPage, 0);

        $Baskets = $BasketRepo->findWorkzoneBasket(
            $app['authentication']->getUser()
            , $request->query->get('Query')
            , $request->query->get('Year')
            , $request->query->get('Type')
            , $offsetStart
            , $PerPage
        );

        $page = floor($offsetStart / $PerPage) + 1;
        $maxPage = floor(count($Baskets) / $PerPage) + 1;

        $params = [
            'Baskets' => $Baskets
            , 'Page'    => $page
            , 'MaxPage' => $maxPage
            , 'Total'   => count($Baskets)
            , 'Query'   => $request->query->get('Query')
            , 'Year'    => $request->query->get('Year')
            , 'Type'    => $request->query->get('Type')
        ];

        return $app['twig']->render('prod/WorkZone/Browser/Results.html.twig', $params);
    }

    public function browseBasket(Application $app, Request $request, Basket $basket)
    {
        return $app['twig']->render('prod/WorkZone/Browser/Basket.html.twig', ['Basket' => $basket]);
    }

    public function attachStories(Application $app, Request $request)
    {
        if (!$request->request->get('stories')) {
            throw new BadRequestHttpException('Missing parameters stories');
        }

        $StoryWZRepo = $app['repo.story-wz'];

        $alreadyFixed = $done = 0;

        $stories = $request->request->get('stories', []);

        foreach ($stories as $element) {
            $element = explode('_', $element);
            $Story = new \record_adapter($app, $element[0], $element[1]);

            if (!$Story->is_grouping()) {
                throw new \Exception('You can only attach stories');
            }

            if (!$app['acl']->get($app['authentication']->getUser())->has_access_to_base($Story->get_base_id())) {
                throw new AccessDeniedHttpException('You do not have access to this Story');
            }

            if ($StoryWZRepo->findUserStory($app, $app['authentication']->getUser(), $Story)) {
                $alreadyFixed++;
                continue;
            }

            $StoryWZ = new StoryWZ();
            $StoryWZ->setUser($app['authentication']->getUser());
            $StoryWZ->setRecord($Story);

            $app['EM']->persist($StoryWZ);
            $done++;
        }

        $app['EM']->flush();

        if ($alreadyFixed === 0) {
            if ($done <= 1) {
                $message = $app->trans('%quantity% Story attached to the WorkZone', ['%quantity%' => $done]);
            } else {
                $message = $app->trans('%quantity% Stories attached to the WorkZone', ['%quantity%' => $done]);
            }
        } else {
            if ($done <= 1) {
                $message = $app->trans('%quantity% Story attached to the WorkZone, %quantity_already% already attached', ['%quantity%' => $done, '%quantity_already%' => $alreadyFixed]);
            } else {
                $message = $app->trans('%quantity% Stories attached to the WorkZone, %quantity_already% already attached', ['%quantity%' => $done, '%quantity_already%' => $alreadyFixed]);
            }
        }

        if ($request->getRequestFormat() == 'json') {
            return $app->json([
                'success' => true
                , 'message' => $message
            ]);
        }

        return $app->redirectPath('prod_workzone_show');
    }

    public function detachStory(Application $app, Request $request, $sbas_id, $record_id)
    {
        $Story = new \record_adapter($app, $sbas_id, $record_id);

        $repository = $app['repo.story-wz'];

        $StoryWZ = $repository->findUserStory($app, $app['authentication']->getUser(), $Story);

        if (!$StoryWZ) {
            throw new NotFoundHttpException('Story not found');
        }

        $app['EM']->remove($StoryWZ);
        $app['EM']->flush();

        if ($request->getRequestFormat() == 'json') {
            return $app->json([
                'success' => true
                , 'message' => $app->trans('Story detached from the WorkZone')
            ]);
        }

        return $app->redirectPath('prod_workzone_show');
    }
}
