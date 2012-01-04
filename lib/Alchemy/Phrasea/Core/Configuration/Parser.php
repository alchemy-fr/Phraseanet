<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

/**
 * A interface to parse configuration file
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Parser
{
  /**
   * Parse the configuration file $file  to an array
   * 
   * @param \SplFileObject $file the file to parse
   * @return Array
   */
  public function parse(\SplFileObject $file);
  
  /**
   * Dump into string from array
   * 
   * @param $conf
   * @return string
   */
  public function dump(Array $conf, $level);
}