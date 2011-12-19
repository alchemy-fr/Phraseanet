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
    $basket_controller = $this;

    $controllers = new ControllerCollection();

    $controllers->post('/', function(Application $app)
            {
              $request = $app['request'];

              /* @var $request \Symfony\Component\HttpFoundation\Request */

              $em = $app['Core']->getEntityManager();

              $Basket = new \Entities\Basket();

              $Basket->setName($request->get('name', ''));
              $Basket->setOwner($app['Core']->getAuthenticatedUser());
              $Basket->setDescription($request->get('desc'));

              $em->persist($Basket);
              $em->flush();

              if ($request->getRequestFormat() == 'json')
              {
                $data = array('basket' => array('id' => $Basket->getId()));

                $datas = $app['Core']['Serializer']->serialize($data, 'json');

                return new Response($datas, 200, array('Content-type' => 'application/json'));
              }
              else
              {
                return new RedirectResponse(sprintf('/%d/', $Basket->getId()));
              }
            });

    $controllers->post('/{basket_id}/delete/', function(Application $app, $basket_id) use ($basket_controller)
            {
              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

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



    $controllers->post('/{basket_id}/update/', function(Application $app, $basket_id) use ($basket_controller)
            {
              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

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


    $controllers->get('/{basket_id}/update/', function(Application $app, $basket_id) use ($basket_controller)
            {
              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

              $twig = new \supertwig();

              return new Response(
                              $twig->render(
                                      'prod/Baskets/Update.html.twig'
                                      , array('basket' => $basket)
                              )
              );
            });


    $controllers->post('/{basket_id}/archive/', function(Application $app, $basket_id) use ($basket_controller)
            {
              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

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

    $controllers->post('/{basket_id}/addElements/', function(Application $app, $basket_id) use ($basket_controller)
            {

              $request = $app['request'];

              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

              $user = $app['Core']->getAuthenticatedUser();
              /* @var $user \User_Adapter */

              foreach (explode(';', $request->get('lst')) as $sbas_rec)
              {
                $sbas_rec = explode('_', $sbas_rec);

                if (count($sbas_rec) !== 2)
                  continue;

                try
                {
                  $record = new \record_adapter($sbas_rec[0], $sbas_rec[1]);

                  if (!$user->ACL()->has_access_to_base($record->get_base_id())
                          && !$user->ACL()->has_hd_grant($record)
                          && !$user->ACL()->has_preview_grant($record))
                  {
                    continue;
                  }

                  $basket_element = new \Entities\BasketElement();
                  $basket_element->setRecord($record);

                  $em->persist($basket_element);

                  $basket->addBasketElement($basket_element);
                }
                catch (\Exception_NotFound $e)
                {
                  
                }
              }

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

    $controllers->get('/create/', function(Application $app)
            {
              $twig = new \supertwig();

              return new Response($twig->render('prod/Baskets/Create.html.twig', array()));
            });

    $controllers->get('/{basket_id}/', function(Application $app, $basket_id) use ($basket_controller)
            {
              $em = $app['Core']->getEntityManager();
      
              $basket = $basket_controller->getUserBasket($app['Core'], $basket_id);

              $basket->setIsRead(true);

              $em->merge($basket);
              $em->flush();

              $twig = new \supertwig();
              
              $html = $twig->render('prod/basket.twig', array('basket' => $basket));

              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

  /**
   *
   * @param \Alchemy\Phrasea\Core $core
   * @param int $basket_id
   * @return \Entities\Basket 
   */
  public function getUserBasket(\Alchemy\Phrasea\Core $core, $basket_id)
  {
    $em = $core->getEntityManager();

    /* @var $em \Doctrine\ORM\EntityManager */
    $repository = $em->getRepository('Entities\Basket');

    $basket = $repository->find($basket_id);

    /* @var $basket Entities\Basket */
    if (null === $basket)
    {
      throw new \Exception_NotFound(_('Basket is not found'));
    }

    if ($basket->getowner()->get_id() != $core->getAuthenticatedUser()->get_id())
    {
      throw new \Exception_Forbidden(_('You have not access to this basket'));
    }

    return $basket;
  }

}
