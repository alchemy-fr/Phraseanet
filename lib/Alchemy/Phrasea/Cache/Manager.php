<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Core\Configuration\Parser as FileParser;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Manager
{

  /**
   *
   * @var \SplFileObject 
   */
  protected $cacheFile;

  /**
   *
   * @var \Alchemy\Phrasea\Core\Configuration\Parser 
   */
  protected $parser;

  /**
   *
   * @var array 
   */
  protected $registry = array();

  public function __construct(\SplFileObject $file, FileParser $parser)
  {
    $this->cacheFile = $file;
    $this->parser = $parser;

    $this->registry = $parser->parse($file);
  }

  public function exists($name)
  {
    return isset($this->registry[$name]);
  }

  public function get($name)
  {
    return $this->exists($name) ?
            $this->registry[$name] : null;
  }

  public function hasChange($name, $driver)
  {
    return $this->exists($name) ?
            $this->registry[$name] === $driver : false;
  }

  public function save($name, $driver)
  {
    $this->registry[$name] = $driver;

    $datas = $this->parser->dump($this->registry);

    file_put_contents($this->cacheFile->getPathname(), $datas);
  }

}

