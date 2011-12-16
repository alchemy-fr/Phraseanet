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

use Alchemy\Phrasea\RouteProcessor;
use Alchemy\Phrasea\RequestHandler;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Root extends RouteProcessor\RouteAbstract
{

  public function __construct(RequestHandler\RequestHandlerAbstract $request)
  {
    parent::__construct($request);
  }
  
  public function getAllowedMethods()
  {
    return array('POST');
  }

  protected function post()
  {
    $em = $this->getEntityManager();

    $Basket = new \Entities\Basket();
    $Basket->setName($this->getRequest()->get('name'));
    $Basket->setUser($this->getKernel()->getAuthenticatedUser());
    $Basket->setDescription($this->getRequest()->get('desc'));

    $em->persist($Basket);
    $em->flush();

    return new RedirectResponse(sprintf('/%d/', $Basket->getId()));
  }

}
