<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Core\Configuration;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ConfigurationTest extends \PhraseanetPHPUnitAbstract
{

  protected $stubSpec;
  protected $stubParser;
  protected $handler;

  public function setUp()
  {
    parent::setUp();
    $this->stubSpec = $this->getMock("\Alchemy\Phrasea\Core\Configuration\Application");
    $this->stubParser = $this->getMock("\Alchemy\Phrasea\Core\Configuration\Parser\Yaml");
  }

  public function testIsInstalled()
  {
    $this->stubSpec->expects($this->any())
            ->method("getConfigurationFile")
            ->will($this->returnValue(true)
    );
    $this->stubSpec->expects($this->any())
            ->method("getServiceFile")
            ->will($this->returnValue(true)
    );
    $this->stubSpec->expects($this->any())
            ->method("getConnexionFile")
            ->will($this->returnValue(true)
    );

    $handler = new Configuration\Handler($this->stubSpec, $this->stubParser);

    $configuration = new Configuration($handler);

    $this->assertTrue($configuration->isInstalled());
  }

  public function testNotInstalled()
  {
    $this->stubSpec->expects($this->any())
            ->method("getConfigurationFile")
            ->will($this->throwException(new Exception)
    );
    $this->stubSpec->expects($this->any())
            ->method("getServiceFile")
            ->will($this->returnValue(true)
    );
    $this->stubSpec->expects($this->any())
            ->method("getConnexionFile")
            ->will($this->returnValue(true)
    );

    $handler = new Configuration\Handler($this->stubSpec, $this->stubParser);

    $configuration = new Configuration($handler);

    $this->assertFalse($configuration->isInstalled());
  }

}