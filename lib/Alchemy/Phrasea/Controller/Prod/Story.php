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

use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Story implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.story'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/create/', function (Application $app) {
            return $app['twig']->render('prod/Story/Create.html.twig', []);
        })->bind('prod_stories_create');

        $controllers->post('/', function (Application $app, Request $request) {
            /* @var $request \Symfony\Component\HttpFoundation\Request */
            $collection = \collection::get_from_base_id($app, $request->request->get('base_id'));

            if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($collection->get_base_id(), 'canaddrecord')) {
                throw new AccessDeniedHttpException('You can not create a story on this collection');
            }

            $Story = \record_adapter::createStory($app, $collection);

            $records = RecordsRequest::fromRequest($app, $request, true);

            foreach ($records as $record) {
                if ($Story->hasChild($record)) {
                    continue;
                }

                $Story->appendChild($record);
            }

            $metadatas = [];

            foreach ($collection->get_databox()->get_meta_structure() as $meta) {
                if ($meta->get_thumbtitle()) {
                    $value = $request->request->get('name');
                } else {
                    continue;
                }

                $metadatas[] = [
                    'meta_struct_id' => $meta->get_id()
                    , 'meta_id'        => null
                    , 'value'          => $value
                ];

                break;
            }

            $Story->set_metadatas($metadatas)->rebuild_subdefs();

            $StoryWZ = new StoryWZ();
            $StoryWZ->setUser($app['authentication']->getUser());
            $StoryWZ->setRecord($Story);

            $app['EM']->persist($StoryWZ);

            $app['EM']->flush();

            if ($request->getRequestFormat() == 'json') {
                $data = [
                    'success'  => true
                    , 'message'  => $app->trans('Story created')
                    , 'WorkZone' => $StoryWZ->getId()
                    , 'story'    => [
                        'sbas_id'   => $Story->get_sbas_id(),
                        'record_id' => $Story->get_record_id(),
                    ]
                ];

                return $app->json($data);
            } else {
                return $app->redirectPath('prod_stories_story', [
                    'sbas_id' => $StoryWZ->getSbasId(),
                    'record_id' => $StoryWZ->getRecordId(),
                ]);
            }
        })->bind('prod_stories_do_create');

        $controllers->get('/{sbas_id}/{record_id}/', function (Application $app, $sbas_id, $record_id) {
            $Story = new \record_adapter($app, $sbas_id, $record_id);

            $html = $app['twig']->render('prod/WorkZone/Story.html.twig', ['Story' => $Story]);

            return new Response($html);
        })
            ->bind('prod_stories_story')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/addElements/', function (Application $app, Request $request, $sbas_id, $record_id) {
            $Story = new \record_adapter($app, $sbas_id, $record_id);

            if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($Story->get_base_id(), 'canmodifrecord'))
                throw new AccessDeniedHttpException('You can not add document to this Story');

            $n = 0;

            $records = RecordsRequest::fromRequest($app, $request, true);

            foreach ($records as $record) {
                if ($Story->hasChild($record)) {
                    continue;
                }

                $Story->appendChild($record);
                $n++;
            }

            $data = [
                'success' => true
                , 'message' => $app->trans('%quantity% records added', ['%quantity%' => $n])
            ];

            if ($request->getRequestFormat() == 'json') {
                return $app->json($data);
            } else {
                return $app->redirectPath('prod_stories_story', ['sbas_id' => $sbas_id,'record_id' => $record_id]);
            }
        })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/delete/{child_sbas_id}/{child_record_id}/', function (Application $app, Request $request, $sbas_id, $record_id, $child_sbas_id, $child_record_id) {
            $Story = new \record_adapter($app, $sbas_id, $record_id);

            $record = new \record_adapter($app, $child_sbas_id, $child_record_id);

            if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($Story->get_base_id(), 'canmodifrecord'))
                throw new AccessDeniedHttpException('You can not add document to this Story');

            $Story->removeChild($record);

            $data = [
                'success' => true
                , 'message' => $app->trans('Record removed from story')
            ];

            if ($request->getRequestFormat() == 'json') {
                return $app->json($data);
            } else {
                return $app->redirectPath('prod_stories_story', ['sbas_id' => $sbas_id,'record_id' => $record_id]);
            }
        })
            ->bind('prod_stories_story_remove_element')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->assert('child_sbas_id', '\d+')
            ->assert('child_record_id', '\d+');

        /**
         * Get the Basket reorder form
         */
        $controllers->get('/{sbas_id}/{record_id}/reorder/', function (Application $app, $sbas_id, $record_id) {
            $story = new \record_adapter($app, $sbas_id, $record_id);

            if (!$story->is_grouping()) {
                throw new \Exception('This is not a story');
            }

            return new Response(
                    $app['twig']->render(
                        'prod/Story/Reorder.html.twig'
                        , ['story' => $story]
                    )
            );
        })
            ->bind('prod_stories_story_reorder')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->post('/{sbas_id}/{record_id}/reorder/', function (Application $app, $sbas_id, $record_id) {
            $ret = ['success' => false, 'message' => $app->trans('An error occured')];
            try {

                $story = new \record_adapter($app, $sbas_id, $record_id);

                if (!$story->is_grouping()) {
                    throw new \Exception('This is not a story');
                }

                if (!$app['acl']->get($app['authentication']->getUser())->has_right_on_base($story->get_base_id(), 'canmodifrecord')) {
                    throw new ControllerException($app->trans('You can not edit this story'));
                }

                $sql = 'UPDATE regroup SET ord = :ord
              WHERE rid_parent = :parent_id AND rid_child = :children_id';
                $stmt = $story->get_databox()->get_connection()->prepare($sql);

                foreach ($app['request']->request->get('element') as $record_id => $ord) {
                    $params = [
                        ':ord'         => $ord,
                        ':parent_id'   => $story->get_record_id(),
                        ':children_id' => $record_id
                    ];
                    $stmt->execute($params);
                }

                $stmt->closeCursor();

                $ret = ['success' => true, 'message' => $app->trans('Story updated')];
            } catch (ControllerException $e) {
                $ret = ['success' => false, 'message' => $e->getMessage()];
            } catch (\Exception $e) {

            }

            return $app->json($ret);
        })
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
