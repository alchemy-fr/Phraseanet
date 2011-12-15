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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    $kernel = $app['Kernel'] ;

    $controllers->post('/create/', function() use ($app)
            {
      
              $em = $app['Kernel']->getEntityManager();
              
              $Basket = new \Entities\Basket;
              $Basket->setName($app['request']->get('name'));
              $Basket->setUser($app['request']->get('desc'));
              $Basket->setDescription($app['request']->get('desc'));
              
              $em->persist($Basket);
              $em->flush();
              
              return new RedirectResponse(sprintf('/%d/', $Basket->getId()));
            });
            
    $controllers->get('/{basket_id}/', function($basket_id) use ($app)
            {
      
              $em = $app['Kernel']->getEntityManager();
              
              /* @var $entityManager \Doctrine\ORM\EntityManager */
              
              $repo = $em->getRepository('Entities\Basket');
              
              /* @todo implement ord */
              $Basket = $repo->find($basket_id);
              
              $twig = new \supertwig();
              
              $html = $twig->render('prod/basket.twig', array('basket' => $Basket));//, 'ordre' => $order));
              
              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

}
