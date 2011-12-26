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
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class PhraseaConfiguration implements ConfigurationSpecification
{

  /**
   *
   * @Override
   */
  public function getNonExtendablePath()
  {
    return array('doctrine', 'dbal');
  }

  /**
   *
   * @Override
   */
  public function getConfFileFromEnvName($name)
  {
    return new \SplFileObject(sprintf("/%s/config_%s.%s"
                            , $this->getConfigurationFilePath()
                            , $name
                            , $this->getConfFileExtension())
    );
  }

  /**
   *
   * @Override
   */
  public function getConfigurationFilePath()
  {
    return __DIR__ . '/../../../../../config';
  }

  /**
   *
   * @Override
   */
  public function getConfFileExtension()
  {
    return 'yml';
  }

  /**
   *
   * @Override
   */
  public function isExtended(Array $env)
  {
    return isset($env[self::EXTENDED_KEYWORD]);
  }

  /**
   *
   * @Override
   */
  public function getExtendedEnvName(Array $env)
  {
    return $this->isExtended($env) ? $env[self::EXTENDED_KEYWORD] : null;
  }

}
