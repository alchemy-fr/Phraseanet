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

interface ConfigurationSpecification
{
  /**
   * Keywords to detect extended file
   */
  const EXTENDED_KEYWORD = 'extends';
  
  /**
   * Return an array of paths that CAN'T be extended by ONLY one or more of their value
   * but must be fully replaced with new values
   * 
   * example:
   * array(array('PATH', 'TO', 'MY', 'SCOPE1', array('SCOPE2')
   * 
   * So $extendedConf['PATH']['TO']['MY']['SCOPE'] will fully replace
   *    $mainConf['PATH']['TO']['MY']['SCOPE'];
   *  
   * @return Array
   */
  public function getNonExtendablePath();
  
  /**
   * Return the configuration file from an environnment name
   * 
   * @return \SplFileObject
   */
  public function getConfFileFromEnvName($name);
  
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
  public function getConfFileExtension();
  
   /**
   * Check wether the environnement $env extends another one
   * 
   * @param type $env
   * @return boolean 
   */
  public function isExtended(Array $env);
  
  /**
   * Return the extends environnement name null if extends nothing
   * 
   * @param Array an environnement
   * @return mixed|null
   */
  public function getExtendedEnvName(Array $env);
  
}