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
                    array("type" => "unknow", "options" => array())
    );

    try
    {
      $service = Alchemy\Phrasea\Core\ServiceBuilder\Orm::create("test", $configuration);
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
                    array("type" => "doctrine", "options" => array(
                            "debug" => false
                            , "log" => "sql_logger"
                            , "dbal" => "main_connexion"
                            , "orm" => array(
                                "cache" => array(
                                    "metadata" => "array_cache"
                                    , "query" => "array_cache"
                                    , "result" => "array_cache"
                                )
                            )
                        )
                    )
    );

    $service = Alchemy\Phrasea\Core\ServiceBuilder\Orm::create("test", $configuration, array("registry" => $registry));
    $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
  }

}