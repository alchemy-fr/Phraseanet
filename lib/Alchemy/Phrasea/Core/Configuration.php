<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

/**
 * Handle configuration file mechanism of phraseanet
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Configuration
{
  const MAIN_ENV_NAME = "main";
  
  /**
   * The environnment name
   * @var string 
   */
  protected $environnement;
  
  /**
   * The configuration
   * @var Array 
   */
  protected $configuration;
  
  /**
   * Tell if appli is currently installed
   * @var boolean
   */
  protected $installed;

  /**
   * 
   * @param type $envName the name of the loaded environnement
   */
  public function __construct($envName)
  {
    //check whether the main configuration file is present on disk
    try
    {
      $specifications = new Configuration\PhraseaConfiguration();
      
      $parser = new Configuration\Parser\Yaml();
      
      $specifications->getConfFileFromEnvName(self::MAIN_ENV_NAME);
      
      $this->installed = true;
      
      $this->environnement = $envName;
    
      $confHandler = new Configuration\EnvironnementHandler($specifications, $parser);
    
      $this->configuration = $confHandler->handle($envName);
    }
    catch(\Exception $e)
    {
      $this->installed = false;
    }
    
  }

  /**
   * Return the current used environnement
   * 
   * @return string 
   */
  public function getEnvironnement()
  {
    return $this->environnement;
  }

  /**
   * Return the DBAL Doctrine configuration
   * 
   * @return Array 
   */
  public function getDbalConf()
  {
    return (array) $this->configuration['doctrine']['dbal'] ?: null;
  }

  /**
   * Tell if the application is installed
   * 
   * @return boolean 
   */
  public function isInstalled()
  {
    return $this->installed;
  }

  public function get($key)
  {
    return isset($this->configuration[$key]) ? $this->configuration[$key]: null;
  }
  
  /**
   * Return the configuration
   * @return Array|null
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }


}