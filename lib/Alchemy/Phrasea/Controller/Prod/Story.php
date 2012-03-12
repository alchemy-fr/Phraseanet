<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\RouteProcessor\Basket as BasketRoute,
    Alchemy\Phrasea\Helper,
    Alchemy\Phrasea\Controller\Exception as ControllerException;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Story implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();


    $controllers->get('/create/', function(Application $app)
            {
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response($twig->render('prod/Story/Create.html.twig', array()));
            });

    $controllers->post('/', function(Application $app, Request $request)
            {
              /* @var $request \Symfony\Component\HttpFoundation\Request */
              $em = $app['Core']->getEntityManager();

              $user = $app['Core']->getAuthenticatedUser();

              $collection = \collection::get_from_base_id($request->get('base_id'));


              if (!$user->ACL()->has_right_on_base($collection->get_base_id(), 'canaddrecord'))
              {
                throw new \Exception_Forbidden('You can not create a story on this collection');
              }


              $system_file = new \system_file(
                                      $app['Core']->getRegistry()
                                      ->get('GV_RootPath') . 'www/skins/icons/substitution/regroup_doc.png'
              );

              $Story = \record_adapter::create($collection, $system_file, false, true);

              foreach (explode(';', $request->get('lst')) as $sbas_rec)
              {
                $sbas_rec = explode('_', $sbas_rec);

                if (count($sbas_rec) !== 2)
                {
                  continue;
                }

                $record = new \record_adapter($sbas_rec[0], $sbas_rec[1]);

                if (!$user->ACL()->has_access_to_base($record->get_base_id())
                        && !$user->ACL()->has_hd_grant($record)
                        && !$user->ACL()->has_preview_grant($record))
                {
                  continue;
                }

                if ($Story->hasChild($record))
                  continue;

                $Story->appendChild($record);
              }

              $metadatas = array();

              foreach ($collection->get_databox()->get_meta_structure() as $meta)
              {
                if ($meta->get_thumbtitle())
                {
                  $value = $request->get('name');
                }
                else
                {
                  continue;
                }

                $metadatas[] = array(
                    'meta_struct_id' => $meta->get_id()
                    , 'meta_id' => null
                    , 'value' => $value
                );

                break;
              }

              $Story->set_metadatas($metadatas)->rebuild_subdefs();

              $StoryWZ = new \Entities\StoryWZ();
              $StoryWZ->setUser($user);
              $StoryWZ->setRecord($Story);

              $em->persist($StoryWZ);

              $em->flush();

              if ($request->getRequestFormat() == 'json')
              {
                $data = array(
                    'success' => true
                    , 'message' => _('Story created')
                    , 'WorkZone' => $StoryWZ->getId()
                    , 'story' => array(
                        'sbas_id' => $Story->get_sbas_id(),
                        'record_id' => $Story->get_record_id(),
                    )
                );

                $datas = $app['Core']['Serializer']->serialize($data, 'json');

                return new Response($datas, 200, array('Content-type' => 'application/json'));
              }
              else
              {
                return new RedirectResponse(sprintf('/%d/', $StoryWZ->getId()));
              }
            });



    $controllers->get('/{sbas_id}/{record_id}/', function(Application $app, $sbas_id, $record_id)
            {
              $Story = new \record_adapter($sbas_id, $record_id);

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              $html = $twig->render('prod/WorkZone/Story.html.twig', array('Story' => $Story));

              return new Response($html);
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post(
            '/{sbas_id}/{record_id}/addElements/'
            , function(Application $app, Request $request, $sbas_id, $record_id)
            {
              $Story = new \record_adapter($sbas_id, $record_id);

              $user = $app['Core']->getAuthenticatedUser();

              if (!$user->ACL()->has_right_on_base($Story->get_base_id(), 'canmodifrecord'))
                throw new \Exception_Forbidden('You can not add document to this Story');

              /* @var $user \User_Adapter */

              $n = 0;

              foreach (explode(';', $request->get('lst')) as $sbas_rec)
              {
                $sbas_rec = explode('_', $sbas_rec);

                if (count($sbas_rec) !== 2)
                  continue;

                $record = new \record_adapter($sbas_rec[0], $sbas_rec[1]);

                if (!$user->ACL()->has_access_to_base($record->get_base_id())
                        && !$user->ACL()->has_hd_grant($record)
                        && !$user->ACL()->has_preview_grant($record))
                {
                  continue;
                }

                if ($Story->hasChild($record))
                  continue;

                $Story->appendChild($record);

                $n++;
              }

              $data = array(
                  'success' => true
                  , 'message' => sprintf(_('%d records added'), $n)
              );

              if ($request->getRequestFormat() == 'json')
              {

                $datas = $app['Core']['Serializer']->serialize($data, 'json');

                return new Response($datas, 200, array('Content-type' => 'application/json'));
              }
              else
              {
                return new RedirectResponse('/');
              }
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');

    $controllers->post(
                    '/{sbas_id}/{record_id}/delete/{child_sbas_id}/{child_record_id}/'
                    , function(Application $app, Request $request, $sbas_id, $record_id, $child_sbas_id, $child_record_id)
                    {
                      $Story = new \record_adapter($sbas_id, $record_id);

                      $record = new \record_adapter($child_sbas_id, $child_record_id);

                      $user = $app['Core']->getAuthenticatedUser();

                      if (!$user->ACL()->has_right_on_base($Story->get_base_id(), 'canmodifrecord'))
                        throw new \Exception_Forbidden('You can not add document to this Story');

                      /* @var $user \User_Adapter */

                      $Story->removeChild($record);

                      $data = array(
                          'success' => true
                          , 'message' => _('Record removed from story')
                      );

                      if ($request->getRequestFormat() == 'json')
                      {
                        $datas = $app['Core']['Serializer']->serialize($data, 'json');

                        return new Response($datas, 200, array('Content-type' => 'application/json'));
                      }
                      else
                      {
                        return new RedirectResponse('/');
                      }
                    })
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->assert('child_sbas_id', '\d+')
            ->assert('child_record_id', '\d+');


    /**
     * Get the Basket reorder form
     */
    $controllers->get(
                    '/{sbas_id}/{record_id}/reorder/'
                    , function(Application $app, $sbas_id, $record_id)
                    {
                      /* @var $em \Doctrine\ORM\EntityManager */
                      $em = $app['Core']->getEntityManager();

                      $story = new \record_adapter($sbas_id, $record_id);

                      if (!$story->is_grouping())
                      {
                        throw new \Exception('This is not a story');
                      }

                      /* @var $twig \Twig_Environment */
                      $twig = $app['Core']->getTwig();

                      return new Response(
                                      $twig->render(
                                              'prod/Story/Reorder.html.twig'
                                              , array('story' => $story)
                                      )
                      );
                    })
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');


    $controllers->post(
                    '/{sbas_id}/{record_id}/reorder/'
                    , function(Application $app, $sbas_id, $record_id)
                    {
                      $ret = array('success' => false, 'message' => _('An error occured'));
                      try
                      {
                        $user = $app['Core']->getAuthenticatedUser();
                        /* @var $user \User_Adapter */

                        $story = new \record_adapter($sbas_id, $record_id);

                        if (!$story->is_grouping())
                        {
                          throw new \Exception('This is not a story');
                        }

                        if (!$user->ACL()->has_right_on_base($story->get_base_id(), 'canmodifrecord'))
                        {
                          throw new ControllerException(_('You can not edit this story'));
                        }

                        $sql = 'UPDATE regroup SET ord = :ord
                      WHERE rid_parent = :parent_id AND rid_child = :children_id';
                        $stmt = $story->get_databox()->get_connection()->prepare($sql);

                        foreach ($app['request']->get('element') as $record_id => $ord)
                        {
                          $params = array(
                              ':ord' => $ord,
                              ':parent_id' => $story->get_record_id(),
                              ':children_id' => $record_id
                          );
                          $stmt->execute($params);
                        }

                        $stmt->closeCursor();

                        $ret = array('success' => true, 'message' => _('Story updated'));
                      }
                      catch (ControllerException $e)
                      {
                        $ret = array('success' => false, 'message' => $e->getMessage());
                      }
                      catch (\Exception $e)
                      {

                      }

                      $Serializer = $app['Core']['Serializer'];

                      return new Response($Serializer->serialize($ret, 'json'), 200, array('Content-type' => 'application/json'));
                    })
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');


    return $controllers;
  }

}
