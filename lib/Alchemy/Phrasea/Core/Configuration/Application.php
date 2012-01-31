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
class Application implements Specification
{
  const DEFAULT_ENV = 'prod';
  const KEYWORD_ENV = 'environment';

  /**
   *
   * {@inheritdoc}
   */
  public function getConfigurationFilePath()
  {
    return realpath(__DIR__ . '/../../../../../config');
  }

  /**
   *
   * {@inheritdoc}
   */
  public function getConfigurationPathName()
  {
    $path = sprintf(
            '%s/%s.%s'
            , $this->getConfigurationFilePath()
            , $this->getConfigurationFileName()
            , $this->getConfigurationFileExtension()
    );

    return $path;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function getConfigurationFileName()
  {
    return 'config';
  }

  /**
   *
   * {@inheritdoc}
   */
  public function getConfigurationFileExtension()
  {
    return 'yml';
  }

  /**
   * Return the selected environnment from configuration file
   *
   * @return string
   */
  public function getSelectedEnv(Array $config)
  {
    if (!isset($config[self::KEYWORD_ENV]))
    {
      return self::DEFAULT_ENV;
    }

    return $config[self::KEYWORD_ENV];
  }

  /**
   * Return the main configuration file
   *
   * @return \SplFileObject
   */
  public function getConfigurationFile()
  {
    return new \SplFileObject($this->getConfigurationPathName());
  }

  /**
   * Return the main configuration file
   *
   * @return \SplFileObject
   */
  public function getServiceFile()
  {
    return new \SplFileObject(realpath(__DIR__ . '/../../../../../config/services.yml'));
  }

  /**
   * Return the main configuration file
   *
   * @return \SplFileObject
   */
  public function getConnexionFile()
  {
    return new \SplFileObject(realpath(__DIR__ . '/../../../../../config/connexions.yml'));
  }

}
