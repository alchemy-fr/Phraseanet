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
 * A interface to precise some specific configuration file mechanism
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface Specification
{

  /**
   * Return the pathname of the configuration file
   *
   * @return string
   */
    public function getConfigurationPathName();

  /**
   * Return the path to the configuration file
   *
   * @return string
   */
  public function getConfigurationFilePath();

  /**
   * Return the configurationFile extension
   *
   * @return string
   */
  public function getConfigurationFileExtension();

  /**
   * Return the name of the configuration file
   *
   * @return string
   */
  public function getConfigurationFileName();


}
