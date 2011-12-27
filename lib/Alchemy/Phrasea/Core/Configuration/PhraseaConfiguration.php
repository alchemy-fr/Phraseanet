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
 * Precise some informations about phraseanet configuration mechanism
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class PhraseaConfiguration implements ConfigurationSpecification
{

  /**
   *
   * {@inheritdoc}
   */
  public function getNonExtendablePath()
  {
    return array(
        array('doctrine', 'dbal')
    );
  }

  /**
   *
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getConfigurationFilePath()
  {
    return __DIR__ . '/../../../../../config';
  }

  /**
   *
   * {@inheritdoc}
   */
  public function getConfFileExtension()
  {
    return 'yml';
  }

  /**
   *
   * {@inheritdoc}
   */
  public function isExtended(Array $env)
  {
    return isset($env[self::EXTENDED_KEYWORD]);
  }

  /**
   *
   * {@inheritdoc}
   */
  public function getExtendedEnvName(Array $env)
  {
    return $this->isExtended($env) ? $env[self::EXTENDED_KEYWORD] : null;
  }

}
