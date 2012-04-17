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
class AbstractBuilderTest extends PhraseanetPHPUnitAbstract
{

  public function testConstructExceptionNameEmpty()
  {
    try
    {
      $stub = $this->getMock(
        "\Alchemy\Phrasea\Core\Service\Builder"
        , array(
        self::$core
        , ''
        , new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag()
        )
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testConstructExceptionCreate()
  {
    try
    {
      $stub = $this->getMock(
        "\\Alchemy\\Phrasea\\Core\\Service\\Builder"
        , array(
        self::$core,
        'test',
        new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(),
        )
      );
      $this->fail("should raise an exception");
    }
    catch (\Exception $e)
    {

    }
  }

}
