<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core\Configuration\Parser;

use Symfony\Component\Yaml\Yaml as SfYaml;

/**
 * Parse a configuration file in yaml format and return an array of values
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Yaml implements \Alchemy\Phrasea\Core\Configuration\Parser
{
  /**
   *
   * @Override
   */
  public function parse(\SplFileObject $file)
  {
    try
    {
      return SfYaml::parse($file->getPathname());
    }
    catch(\Exception $e)
    {
      throw new \Exception(sprintf('Failed to parse the configuration file %s', $e->getMessage()));
    }
  }
  
  /**
   *
   * @Override
   */
  public function dump(Array $conf, $level = 1)
  {
    return SfYaml::dump($conf, $level);
  }

}