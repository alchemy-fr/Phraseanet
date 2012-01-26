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

    /**
     * This route is used to create a Basket
     *
     * @params name : title (mandatory)
     * @params desc : description (optionnal)
     * @params lst  : Phraseanet serialized record list (optionnal)
     *
     * @accept JSON / YAML
     *
     */
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

    /**
     * This route is used to delete a basket
     *
     * @accept JSON / HTML
     *
     */
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

    /**
     * Removes a BasketElement
     */
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

    /**
     * Update name and description of a basket
     *
     * @param name string mandatory
     * @param description string optionnal
     *
     */
    $controllers->post('/{basket_id}/update/', function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $basket->setName($request->get('name', ''));
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

    /**
     * Get the form to update the Basket attributes (name and description)
     */
    $controllers->get('/{basket_id}/update/', function(Application $app, $basket_id)
            {
              /* @var $em \Doctrine\ORM\EntityManager */
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'prod/Baskets/Update.html.twig'
                                      , array('basket' => $basket)
                              )
              );
            });


    /**
     * Get the Basket reorder form
     */
    $controllers->get(
            '/{basket_id}/reorder/'
            , function(Application $app, $basket_id)
            {
              /* @var $em \Doctrine\ORM\EntityManager */
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'prod/Baskets/Reorder.html.twig'
                                      , array('basket' => $basket)
                              )
              );
            });

    /**
     * Toggle the status of a Basket
     *
     * @param acrhive : 0|1 (mandatory)
     *
     * @returns JSON / HTML
     */
    $controllers->post('/{basket_id}/archive/', function(Application $app, Request $request, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $archive_status = !!$request->get('archive');

              $basket->setArchived($archive_status);

              $em->merge($basket);
              $em->flush();

              if($archive_status)
              {
                $message = _('Basket has been archived');
              }
              else
              {
                $message = _('Basket has been unarchived');
              }

              $data = array(
                  'success' => true
                  , 'archive' => $archive_status
                  , 'message' => $message
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

    /**
     * Add a BasketElement to a basket
     */
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




    /**
     *
     * Move Basket element from a basket to another
     *
     * @params elements Array : list of basket element id
     *
     */
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

                if (!$basket_element)
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

    /**
     * Get basket creation form
     */
    $controllers->get('/create/', function(Application $app)
            {
              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response($twig->render('prod/Baskets/Create.html.twig', array()));
            });

    /**
     * Get a basket
     */
    $controllers->get('/{basket_id}/', function(Application $app, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              $basket->setIsRead(true);

              $em->merge($basket);
              $em->flush();

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              $html = $twig->render('prod/WorkZone/Basket.html.twig', array('basket' => $basket));

              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

}
