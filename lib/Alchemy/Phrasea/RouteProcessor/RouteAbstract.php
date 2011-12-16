<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\RouteProcessor;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Helper\Helper;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class RouteAbstract
{
  const POST_OK = 201;
  const GET_OK = 200;
  const DELETE_OK = 204;
  const PUT_OK = 200;
  const OPTIONS_OK = 204;
  const HEAD_OK = 200;

  /**
   *
   * @var Phrasea\Kernel 
   */
  protected $requestHelper;

  /**
   * The response being rendered
   * @var Response
   */
  protected $response;

  /**
   * Return allowed methods for current controller
   */
  abstract public function getAllowedMethods();

  /**
   * Constructor for a Statme Thread Module
   * @param PDO $connection
   */
  public function __construct(Helper $requestHelper)
  {
    $this->requestHelper = $requestHelper;
  }

  /**
   *
   * @return Helper 
   */
  public function getRequestHelper()
  {
    return $this->requestHelper;
  }

  /**
   * Getter
   * @return Phrasea\Kernel
   */
  public function getKernel()
  {
    return $this->requestHelper->getKernel();
  }

  /**
   * Getter
   * @return Request
   */
  public function getRequest()
  {
    return $this->getKernel()->getRequest();
  }
  
  /**
   * Getter
   * @return EntityManager
   */
  public function getEntityManager()
  {
    return $this->getKernel()->getEntityManager();
  }

  /**
   * Getter
   * @return \registryInterface
   */
  public function getRegistry()
  {
    return $this->getKernel()->getRegistry();
  }

  /**
   * Getter
   * @return Response 
   */
  public function getResponse()
  {
    if (null === $this->response)
    {
      $this->process();
    }

    return $this->response;
  }

  /**
   * 
   * @return ControllerProcessorAbstract
   */
  public function process()
  {
    $response = null;

    switch (strtoupper($this->getRequest()->getMethod()))
    {
      case 'POST' :
        $response = $this->post();
        break;
      case 'PUT' :
        $response = $this->put();
        break;
      case 'DELETE' :
        $response = $this->delete();
        break;
      case 'GET' :
        $response = $this->get();
        break;
      case 'OPTIONS' :
        $response = $this->options();
        break;
      case 'HEAD' :
        $response = $this->head();
        break;
      default :
        throw new Http\NotImplemented();
        break;
    }

    $this->response = $response;

    return $this;
  }

  /**
   * Handle post action
   */
  protected function post()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

  /**
   * Handle delete action
   */
  protected function delete()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

  /**
   * Handle get action
   */
  protected function get()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

  /**
   * Handle put action
   */
  protected function put()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

  /**
   * Handle options action
   */
  protected function options()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

  /**
   * Handle head action
   */
  protected function head()
  {
    throw new Http\MethodNotAllowed($this->getAllowedMethods());
  }

}