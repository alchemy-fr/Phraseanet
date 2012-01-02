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
use Alchemy\Phrasea\RouteProcessor\WorkZone as RouteWorkZone,
    Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZone implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function(Application $app)
            {
              $twig = new \supertwig();

              $params = array(
                  'WorkZone' => new Helper\WorkZone($app['Core'])
                  , 'selected_type' => $app['request']->get('type')
                  , 'selected_id' => $app['request']->get('id')
                  , 'srt' => $app['request']->get('sort')
              );


              return new Response($twig->render('prod/WorkZone/WorkZone.html.twig', $params));
            });


    $controllers->post(
            '/attachStories/'
            , function(Application $app, Request $request)
            {

              
              $user = $app['Core']->getAuthenticatedUser();

              $em = $app['Core']->getEntityManager();
             /* @var $em \Doctrine\ORM\EntityManager */
             
              $StoryWZRepo = $em->getRepository('\Entities\StoryWZ');

              $alreadyFixed = $done = 0;

              foreach ( explode(';', $request->get('stories')) as $element)
              {
                $element = explode('_', $element);
                $Story = new \record_adapter($element[0], $element[1]);

                if (!$Story->is_grouping())
                  throw new \Exception('You can only attach stories');
                
                if (!$user->ACL()->has_access_to_base($Story->get_base_id()))
                  throw new \Exception_Forbidden('You do not have access to this Story');

                
                if ($StoryWZRepo->findUserStory($user, $Story))
                {
                  $alreadyFixed++;
                  continue;
                }
                
                $StoryWZ = new \Entities\StoryWZ();
                $StoryWZ->setUser($user);
                $StoryWZ->setRecord($Story);

                $em->persist($StoryWZ);
                $done++;
              }
              
              $em->flush();

              if ($alreadyFixed === 0)
              {
                if ($done <= 1)
                {
                  $message = sprintf(
                          _('%d Story attached to the WorkZone')
                          , $done
                  );
                }
                else
                {
                  $message = sprintf(
                          _('%d Stories attached to the WorkZone')
                          , $done
                  );
                }
              }
              else
              {
                if ($done <= 1)
                {
                  $message = sprintf(
                          _('%1$d Story attached to the WorkZone, %2$d already attached')
                          , $done
                          , $alreadyFixed
                  );
                }
                else
                {
                  $message = sprintf(
                          _('%1$d Story attached to the WorkZone, %2$d already attached')
                          , $done
                          , $alreadyFixed
                  );
                }
              }

              $data = array(
                  'success' => true
                  , 'message' => $message
              );

              if ($request->getRequestFormat() == 'json')
              {

                $datas = $app['Core']['Serializer']->serialize($data, 'json');

                return new Response($datas, 200, array('Content-type' => 'application/json'));
              }
              else
              {
                return new RedirectResponse('/{sbas_id}/{record_id}/');
              }
            });
            
            
    $controllers->post(
            '/detachStory/{sbas_id}/{record_id}/'
            , function(Application $app, Request $request, $sbas_id, $record_id)
            {
              $Story = new \record_adapter($sbas_id, $record_id);

              $user = $app['Core']->getAuthenticatedUser();

              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\StoryWZ');

              /* @var $repository \Repositories\StoryWZRepository */
              $StoryWZ = $repository->findUserStory($user, $Story);
              
              if (!$StoryWZ)
              {
                throw new \Exception_NotFound('Story not found');
              }
              $em->remove($StoryWZ);
              
              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => _('Story detached from the WorkZone')
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
            });


    return $controllers;
  }

}
