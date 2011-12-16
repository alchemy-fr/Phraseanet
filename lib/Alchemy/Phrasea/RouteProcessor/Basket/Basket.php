<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\RouteProcessor\Basket;

use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\RouteProcessor,
    Alchemy\Phrasea\RequestHandler;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Basket extends RouteProcessor\RouteAbstract
{

  public function __construct(RequestHandler\RequestHandlerAbstract $request)
  {
    parent::__construct($request);
  }

  public function getAllowedMethods()
  {
    return array('POST', 'GET');
  }

  protected function post()
  {
    $identifier = $this->getRequest()->get('basket_id');

    if (null === $identifier)
    {
      throw new Http\BadRequest();
    }

    $em = $this->getEntityManager();

    $repository = $em->getRepository('Entities\Baskets');
  }

  protected function get()
  {
    $identifier = $this->getRequest()->get('basket_id');

    if (null === $identifier)
    {
      throw new Http\BadRequest();
    }

    $em = $this->getEntityManager();

    $repository = $em->getRepository('Entities\Baskets');

    /* @var $basket Entities\Basket */
    $basket = $repository->find($identifier);

    $html = '';
//    $template = $this->getTemplateEngine();
//    
//    $html = $template->render('prod/basket.twig', array('basket' => $basket));

    return new Response($html, self::GET_OK, array('content-type' => 'text/html'));
  }

}
