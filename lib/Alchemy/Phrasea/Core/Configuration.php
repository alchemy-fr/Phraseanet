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

use Alchemy\Phrasea\Core\Configuration\Application;
use Alchemy\Phrasea\Core\Configuration\Parser as ConfigurationParser;

/**
 * Handle configuration file mechanism of phraseanet
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Configuration
{

  /**
   * The environnment name
   * @var string 
   */
  protected $environnement;

  /**
   * The finale configuration values as an array
   * @var Array 
   */
  protected $configuration = array();

  /**
   * Tell if appli is currently installed
   * @var boolean
   */
  protected $installed = false;

  /**
   * Class that take care of configuration process
   * @var Configuration\Handler 
   */
  private $configurationHandler;

  /**
   * Class that take care of configuration specification 
   * like filepath, extends keywords etc ..
   * @var Configuration\Specification
   */
  private $configurationSpecification;

  /**
   * Class that take care of parsing configuration file 
   * @var Configuration\Parser
   */
  private $configurationParser;

  /**
   * 
   * @param type $envName the name of the loaded environnement
   */
  public function __construct($envName)
  {
    $this->init($envName);
  }

  /**
   * Getter
   * @return Configuration\Handler
   */
  public function getConfigurationHandler()
  {
    return $this->configurationHandler;
  }

  /**
   * Setter
   * @param Configuration\Handler $configurationHandler 
   */
  public function setConfigurationHandler(Configuration\Handler $configurationHandler)
  {
    $this->configurationHandler = $configurationHandler;
  }

  /**
   * Getter
   * @return Configuration\Specification 
   */
  public function getConfigurationSpecification()
  {
    return $this->configurationSpecification;
  }

  /**
   * Setter
   * @param Configuration\Specification $configurationSpecification 
   */
  public function setConfigurationSpecification(Configuration\Specification $configurationSpecification)
  {
    $this->configurationSpecification = $configurationSpecification;
  }

  /**
   * Getter
   * @return Configuration\Parser
   */
  public function getConfigurationParser()
  {
    return $this->configurationParser;
  }

  /**
   * Setter
   * @param type $configurationParser 
   */
  public function setConfigurationParser($configurationParser)
  {
    $this->configurationParser = $configurationParser;
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
  public function getDoctrine()
  {
    $doctrine = $this->get('doctrine'); //get doctrine scope

    if (null !== $doctrine)
    {
      $doctrine["debug"] = $this->isDebug(); //set debug

      if (!!$doctrine["log"]['enable'])
      {
        $logger = $doctrine["log"]["type"];

        if (!in_array($doctrine["log"]["type"], $this->getAvailableLogger()))
        {
          throw new \Exception(sprintf('Unknow logger %s', $logger));
        }

        $doctrineLogger = $this->get($logger); //set logger

        $doctrine["logger"] = $doctrineLogger;
      }
    }

    return null === $doctrine ? array() : $doctrine;
  }

  /**
   * Check if current environnement is on debug mode
   * Default to false
   * @return boolean 
   */
  public function isDebug()
  {
    $phraseanet = $this->getPhraseanet();
    return isset($phraseanet["debug"]) ? !!$phraseanet["debug"] : false;
  }

  /**
   * Return the phraseanet scope configurations values
   * 
   * @return Array|null 
   */
  public function getPhraseanet()
  {
    $phraseanet = $this->get('phraseanet');
    return null === $phraseanet ? array() : $phraseanet;
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

  /**
   * Check if key exist in final configuration if yes it returns the value else
   * it returns null
   * 
   * @param type $key
   * @return Array|null
   */
  private function get($key)
  {
    return isset($this->configuration[$key]) ? $this->configuration[$key] : null;
  }

  /**
   * Return the configuration
   * 
   * @return Array|null
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  /**
   * Return Available logger
   * 
   * @return Array 
   */
  private function getAvailableLogger()
  {
    return array('echo', 'monolog');
  }

  /**
   * Return configurationFilePAth
   * @return string
   */
  public function getConfigurationFilePath()
  {
    return __DIR__ . '/../../../../config';
  }

  /**
   * Return configurationFileName
   * @return string
   */
  public function getConfigurationFileName()
  {
    return 'config.yml';
  }

  /**
   * Init object
   * Called in constructor
   */
  private function init($envName)
  {
    $filePath = $this->getConfigurationFilePath();
    $fileName = $this->getConfigurationFileName();

    try
    {
      new \SplFileObject(sprintf("%s/%s", $filePath, $fileName));

      $this->installed = true;
    }
    catch (\Exception $e)
    {
      
    }

    $this->environnement = $envName;

    if ($this->installed)
    {
      $confHandler = new Configuration\Handler(new Application(), new ConfigurationParser\Yaml());
      $this->configuration = $confHandler->handle($envName);
    }
  }

}