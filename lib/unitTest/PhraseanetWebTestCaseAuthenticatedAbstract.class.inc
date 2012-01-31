<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class PhraseanetWebTestCaseAuthenticatedAbstract extends PhraseanetPHPUnitAuthenticatedAbstract
{

  public function createClient(array $options = array(), array $server = array())
  {
    return new Client($this->app);
  }

  public function setUp()
  {
    parent::setUp();
    $this->app = $this->createApplication();
  }

}
