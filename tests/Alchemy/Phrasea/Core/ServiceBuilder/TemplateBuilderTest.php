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
class TemplateBuilderTest extends PhraseanetPHPUnitAbstract
{

  public function testCreateException()
  {
    $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
        array("type"    => "unknow", "options" => array())
    );

    try
    {
      $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
      $this->fail("An exception should be raised");
    }
    catch (\Exception $e)
    {

    }
  }

  public function testCreate()
  {
    $configuration = new Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(
        array(
          "type"    => "TemplateEngine\\Twig"
          , "options" => array(
            'debug'            => 'true'
            , 'charset'          => 'UTF-8'
            , 'strict_variables' => 'true'
            , 'autoescape'       => 'true'
            , 'optimizer'        => 'true'
        ))
    );

    $service = Alchemy\Phrasea\Core\Service\Builder::create(self::$core, $configuration);
    $this->assertInstanceOf("\Alchemy\Phrasea\Core\Service\ServiceAbstract", $service);
  }

}
