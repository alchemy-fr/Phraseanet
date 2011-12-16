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
              $em = $app['Core']->getEntityManager();

              $Basket = new \Entities\Basket();
              $Basket->setName($app['request']->get('name'));
              $Basket->setUser($app['Core']->getAuthenticatedUser());
              $Basket->setDescription($app['request']->get('desc'));

              $em->persist($Basket);
              $em->flush();

              return new RedirectResponse(sprintf('/%d/', $Basket->getId()));
            });

    $controllers->get('/create/', function(Application $app)
            {
              $twig = new \supertwig();

              return new Response($twig->render('prod/Baskets/Create.html.twig', array()));
            });


    $controllers->get('/{basket_id}/', function($basket_id) use ($app)
            {

              $identifier = $app['request']->get('basket_id');

              if (null === $identifier)
              {
                throw new \Exception_BadRequest('No basket_id');
              }

              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('Entities\Baskets');

              /* @var $basket Entities\Basket */
              $basket = $repository->find($identifier);

              $twig = new \supertwig();

              $html = $twig->render('prod/basket.twig', array('basket' => $basket));

              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

}
