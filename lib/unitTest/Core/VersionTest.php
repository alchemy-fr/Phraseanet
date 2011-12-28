<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Core\Version;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class VersionTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration\Application 
   */
  public function setUp()
  {
    parent::setUp();
  }

  public function tearDown()
  {
    parent::tearDown();
  }
 
  public function testGetNumber()
  {
    $this->assertTrue(is_string(Version::getName()));
  }
  
  public function testGetName()
  {
    $this->assertTrue(is_string(Version::getNumber()));
  }
}