<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ServiceAbstractTest extends PhraseanetPHPUnitAbstract
{

  /**
   *
   * @var \Alchemy\Phrasea\Core\Service\ServiceAbstract
   */
  protected $object;

  public function setUp()
  {
    parent::setUp();
    $stub = $this->getMockForAbstractClass(
            "\Alchemy\Phrasea\Core\Service\ServiceAbstract"
            , array(
         self::$core,
        'abstract'
        , array('option' => 'my_options')
            )
    );

    $this->object = $stub;
  }

  public function testGetName()
  {
    $this->assertEquals("abstract", $this->object->getName());
  }

  public function testGetOptions()
  {
    $this->assertTrue(is_array($this->object->getOptions()));
    $this->assertEquals(array('option' => 'my_options'), $this->object->getOptions());
  }

}
