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

use Alchemy\Phrasea\Core as PhraseaCore;
use Alchemy\Phrasea\Core\Configuration\Parser\Yaml;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class parserTest extends \PhraseanetPHPUnitAbstract
{

  public function testParser()
  {
    $parser = new Yaml();

    $filename = $fileName = __DIR__ . '/confTestFiles/good.yml';

    $file = new SplFileObject($filename);

    $result = $parser->parse($file);

    $this->assertTrue(is_array($result));

    $filename = $fileName = __DIR__ . '/confTestFiles/test.json';

    $file = new SplFileObject($filename);

    try
    {
      $result = $parser->parse($file);
      $this->fail('An exception shoud have been raised');
    }
    catch (Exception $e)
    {
      
    }
  }

  public function testDumper()
  {
    $parser = new Yaml();

    $test = array("hello" => "you");

    try
    {
      $result = $parser->dump($test);
      $this->assertTrue(is_string($result));
      $this->assertRegexp("#hello: you#", $result);
    }
    catch (Exception $e)
    {
      
    }
  }

}