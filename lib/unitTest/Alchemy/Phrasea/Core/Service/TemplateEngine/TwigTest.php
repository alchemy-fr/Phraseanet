<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TwigTest extends PhraseanetPHPUnitAbstract
{

  protected $options;

  public function setUp()
  {
    parent::setUp();
    $this->options = array();
  }

  public function testScope()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                    'hello', $this->options, array()
    );

    $this->assertEquals("template_engine", $doctrine->getScope());
  }

  public function testService()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                    'hello', $this->options, array()
    );

    $this->assertInstanceOf("\Twig_Environment", $doctrine->getService());
  }

  public function testServiceExcpetion()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                    'hello', $this->options, array()
    );

    $this->assertInstanceOf("\Twig_Environment", $doctrine->getService());
  }

  public function testType()
  {
    $registry = $this->getMock('RegistryInterface');
    $doctrine = new \Alchemy\Phrasea\Core\Service\TemplateEngine\Twig(
                    'hello', $this->options, array()
    );

    $this->assertEquals("twig", $doctrine->getType());
  }

}
