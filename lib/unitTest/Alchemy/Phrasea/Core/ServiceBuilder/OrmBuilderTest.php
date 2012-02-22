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
class OrmBuilderTest extends PhraseanetPHPUnitAbstract
{

  public function testCreateException()
  {
    $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
        array("type"    => "unknow", "options" => array())
    );

    try
    {
      $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, "test", $configuration);
      $this->fail("An exception should be raised");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testCreate()
  {
    $registry = $this->getMock("\RegistryInterface");

    $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
        array("type"    => "Orm\\Doctrine", "options" => array(
            "debug" => false
            , "log"   => array('service'=>"Log\\query_logger")
            , "dbal"  => "main_connexion"
            , "cache" => array(
              "metadata" => "Cache\\array_cache"
              , "query"    => "Cache\\array_cache"
              , "result"   => "Cache\\array_cache"
            )
          )
        )
    );

    $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, "test", $configuration);
    $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
  }

}
