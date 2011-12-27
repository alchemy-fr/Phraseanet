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
    Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Basket implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->post('/', function(Application $app)
            {
              $request = $app['request'];

              /* @var $request \Symfony\Component\HttpFoundation\Request */

              $em = $app['Core']->getEntityManager();

              $user = $app['Core']->getAuthenticatedUser();

              $Basket = new \Entities\Basket();

              $Basket->setName($request->get('name', ''));
              $Basket->setOwner($app['Core']->getAuthenticatedUser());
              $Basket->setDescription($request->get('desc'));

              $em->persist($Basket);

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

                if ($Basket->hasRecord($record))
                  continue;

                $basket_element = new \Entities\BasketElement();
                $basket_element->setRecord($record);
                $basket_element->setBasket($Basket);

                $em->persist($basket_element);

                $Basket->addBasketElement($basket_element);

                $n++;
              }

              $em->flush();

              if ($request->getRequestFormat() == 'json')
              {
                $data = array(
                    'success' => true
                    , 'message' => _('Basket created')
                    , 'basket' => array(
                        'id' => $Basket->getId()
                    )
                );

                $datas = $app['Core']['Serializer']->serialize($data, 'json');

                return new Response($datas, 200, array('Content-type' => 'application/json'));
              }
              else
              {
                return new RedirectResponse(sprintf('/%d/', $Basket->getId()));
              }
            });

    $controllers->post('/{basket_id}/delete/', function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $em->remove($basket);
              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => _('Basket has been deleted')
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

    $controllers->post(
            '/{basket_id}/delete/{basket_element_id}/'
            , function(Application $app, Request $request, $basket_id, $basket_element_id)
            {
              /* @var $em \Doctrine\ORM\EntityManager */
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              foreach ($basket->getElements() as $basket_element)
              {
                /* @var $basket_element \Entities\BasketElement */
                if ($basket_element->getId() == $basket_element_id)
                {
                  $em->remove($basket_element);
                }
              }

              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => _('Record removed from basket')
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



    $controllers->post('/{basket_id}/update/', function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $basket->setName($request->get('name'));
              $basket->setDescription($request->get('description'));

              $em->merge($basket);
              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => _('Basket has been updated')
                  , 'basket' => array('id' => $basket->getId())
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


    $controllers->get('/{basket_id}/update/', function(Application $app, $basket_id)
            {
              /* @var $em \Doctrine\ORM\EntityManager */
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $twig = new \supertwig();

              return new Response(
                              $twig->render(
                                      'prod/Baskets/Update.html.twig'
                                      , array('basket' => $basket)
                              )
              );
            });


    $controllers->get(
            '/{basket_id}/reorder/'
            , function(Application $app, $basket_id)
            {
              /* @var $em \Doctrine\ORM\EntityManager */
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $twig = new \supertwig();

              return new Response(
                              $twig->render(
                                      'prod/Baskets/Reorder.html.twig'
                                      , array('basket' => $basket)
                              )
              );
            });


    $controllers->post('/{basket_id}/archive/', function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $basket->setArchived(!!$request->get('archive'));

              $em->merge($basket);
              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => _('Basket has been deleted')
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

    $controllers->post(
            '/{basket_id}/addElements/'
            , function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $user = $app['Core']->getAuthenticatedUser();
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

                if ($basket->hasRecord($record))
                  continue;

                $basket_element = new \Entities\BasketElement();
                $basket_element->setRecord($record);
                $basket_element->setBasket($basket);

                $em->persist($basket_element);

                $basket->addBasketElement($basket_element);

                $n++;
              }

              $em->merge($basket);
              $em->flush();

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
            });
            
            
            
    $controllers->post(
            '/{basket_id}/stealElements/'
            , function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              /* @var $em \Doctrine\ORM\EntityManager */
              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $user = $app['Core']->getAuthenticatedUser();
              /* @var $user \User_Adapter */

              $n = 0;

              foreach ($request->get('elements') as $bask_element_id)
              {
                $basket_element = $em->getRepository('\Entities\BasketElement')
                      ->findUserElement($bask_element_id, $user);
                
                if(!$basket_element)
                {
                  continue;
                }
                
                $basket_element->setBasket($basket);
                
                $em->merge($basket_element);
                
                $n++;
              }

              $em->merge($basket);
              $em->flush();

              $data = array(
                  'success' => true
                  , 'message' => sprintf(_('%d records moved'), $n)
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

    $controllers->get('/create/', function(Application $app)
            {
              $twig = new \supertwig();

              return new Response($twig->render('prod/Baskets/Create.html.twig', array()));
            });

    $controllers->get('/{basket_id}/', function(Application $app, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $basket->setIsRead(true);

              $em->merge($basket);
              $em->flush();

              $twig = new \supertwig();

              $html = $twig->render('prod/WorkZone/Basket.html.twig', array('basket' => $basket));

              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

}
