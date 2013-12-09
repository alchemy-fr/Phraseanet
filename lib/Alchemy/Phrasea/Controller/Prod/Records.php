<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Records implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.records'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        $controllers->match('/', 'controller.prod.records:getRecord')
            ->bind('record_details')
            ->method('GET|POST');

        $controllers->post('/delete/', 'controller.prod.records:doDeleteRecords')
            ->bind('record_delete');

        $controllers->post('/delete/what/', 'controller.prod.records:whatCanIDelete')
            ->bind('record_what_can_i_delete');

        $controllers->post('/renew-url/', 'controller.prod.records:renewUrl')
            ->bind('record_renew_url');

        return $controllers;
    }

    /**
     * Get record detailed view
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return JsonResponse
     */
    public function getRecord(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $searchEngine = null;
        $train = '';

        if ('' === $env = strtoupper($request->get('env', ''))) {
            $app->abort(400, '`env` parameter is missing');
        }

        // Use $request->get as HTTP method can be POST or GET
        if ('RESULT' == $env = strtoupper($request->get('env', ''))) {
            try {
                $options = SearchEngineOptions::hydrate($app, $request->get('options_serial'));
                $searchEngine = $app['phraseanet.SE'];
                $searchEngine->setOptions($options);
            } catch (\Exception $e) {
                $app->abort(400, 'Search-engine options are not valid or missing');
            }
        }

        $pos = (int) $request->get('pos', 0);
        $query = $request->get('query', '');
        $reloadTrain = !! $request->get('roll', false);

        $record = new \record_preview(
            $app,
            $env,
            $pos < 0 ? 0 : $pos,
            $request->get('cont', ''),
            $searchEngine,
            $query
        );

        if ($record->is_from_reg()) {
            $train = $app['twig']->render('prod/preview/reg_train.html.twig',
                ['record' => $record]
            );
        }

        if ($record->is_from_basket() && $reloadTrain) {
            $train = $app['twig']->render('prod/preview/basket_train.html.twig',
                ['record' => $record]
            );
        }

        if ($record->is_from_feed()) {
            $train = $app['twig']->render('prod/preview/feed_train.html.twig',
                ['record' => $record]
            );
        }

        return $app->json([
            "desc"          => $app['twig']->render('prod/preview/caption.html.twig', [
                'record'        => $record,
                'highlight'     => $query,
                'searchEngine'  => $searchEngine
            ]),
            "html_preview"  => $app['twig']->render('common/preview.html.twig', [
                'record'        => $record
            ]),
            "others"        => $app['twig']->render('prod/preview/appears_in.html.twig', [
                'parents'       => $record->get_grouping_parents(),
                'baskets'       => $record->get_container_baskets($app['EM'], $app['authentication']->getUser())
            ]),
            "current"       => $train,
            "history"       => $app['twig']->render('prod/preview/short_history.html.twig', [
                'record'        => $record
            ]),
            "popularity"    => $app['twig']->render('prod/preview/popularity.html.twig', [
                'record'        => $record
            ]),
            "tools"         => $app['twig']->render('prod/preview/tools.html.twig', [
                'record'        => $record
            ]),
            "pos"           => $record->get_number(),
            "title"         => $record->get_title($query, $searchEngine)
        ]);
    }

    /**
     *  Delete a record or a list of records
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function doDeleteRecords(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('del_children'), [
            'candeleterecord'
        ]);

        $basketElementsRepository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\BasketElement');
        $StoryWZRepository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\StoryWZ');

        $deleted = [];

        foreach ($records as $record) {
            try {
                $basketElements = $basketElementsRepository->findElementsByRecord($record);

                foreach ($basketElements as $element) {
                    $app['EM']->remove($element);
                    $deleted[] = $element->getRecord($app)->get_serialize_key();
                }

                $attachedStories = $StoryWZRepository->findByRecord($app, $record);

                foreach ($attachedStories as $attachedStory) {
                    $app['EM']->remove($attachedStory);
                }

                $deleted[] = $record->get_serialize_key();
                $record->delete();
            } catch (\Exception $e) {

            }
        }

        $app['EM']->flush();

        return $app->json($deleted);
    }

    /**
     *  Delete a record or a list of records
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function whatCanIDelete(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('del_children'), [
            'candeleterecord'
        ]);

        return $app['twig']->render('prod/actions/delete_records_confirm.html.twig', [
            'records'   => $records
        ]);
    }

    /**
     *  Renew url list of records
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return JsonResponse
     */
    public function renewUrl(Application $app, Request $request)
    {
        $records = RecordsRequest::fromRequest($app, $request, !!$request->request->get('renew_children_url'));

        $renewed = [];
        foreach ($records as $record) {
            $renewed[$record->get_serialize_key()] = $record->get_preview()->renew_url();
        };

        return $app->json($renewed);
    }
}
