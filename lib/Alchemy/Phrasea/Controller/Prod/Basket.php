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
    Alchemy\Phrasea\RequestHandler;

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

    $controllers->match('/', function(Application $app)
            {
              $requestHandler = new RequestHandler\Basket($app["kernel"]);
              $processor = new BasketRoute\Root($requestHandler);

              return $processor->getResponse();
            });


    $controllers->get('/{basket_id}/', function($basket_id) use ($app)
            {
              $em = $app['Kernel']->getEntityManager();

              /* @var $entityManager \Doctrine\ORM\EntityManager */

              $repo = $em->getRepository('Entities\Basket');

              /* @todo implement ord */
              $Basket = $repo->find($basket_id);

              $twig = new \supertwig();

              $html = $twig->render('prod/basket.twig', array('basket' => $Basket)); //, 'ordre' => $order));

              return new Response($html);
            })->assert('basket_id', '\d+');

    return $controllers;
  }

}
