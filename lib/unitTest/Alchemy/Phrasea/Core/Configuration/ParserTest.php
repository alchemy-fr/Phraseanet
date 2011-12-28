<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Core as PhraseaCore;
use Alchemy\Phrasea\Core\Configuration\Parser\Yaml;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class parserTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  public function setUp()
  {
    parent::setUp();
  }

  public function tearDown()
  {
    parent::tearDown();
  }
  
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
    catch(Exception $e)
    {
      
    }
    
    
  }
}