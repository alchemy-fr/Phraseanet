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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
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
   * The file path of the main configuration file
   * @var string 
   */
  protected $filePathName;
  
  /**
   * The environnment name
   * @var string 
   */
  protected $environnement;

  /**
   * The finale configuration values as an array
   * @var ParameterBag\ParameterBag
   */
  protected $configuration;


  /**
   * Class that take care of configuration process
   * @var Configuration\Handler 
   */
  private $configurationHandler;

  /**
   * 
   * @param type $envName the name of the loaded environnement
   */
  public function __construct($envName, Configuration\Handler $handler)
  {
    $this->environnement = $envName;
    $this->configurationHandler = $handler;
    $this->installed = false;

    try
    {
      $handler->getSpecification()->getMainConfigurationFile();

      $this->installed = true;
    }
    catch (\Exception $e)
    {
      
    }
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
    $doctrine = $this->getConfiguration()->get('doctrine', array()); //get doctrine scope

    if (count($doctrine) > 0)
    {
      $doctrine["debug"] = $this->isDebug(); //set debug

      if (!!$doctrine["log"]['enable'])
      {
        $logger = $doctrine["log"]["type"];

        if (!in_array($doctrine["log"]["type"], $this->getAvailableDoctrineLogger()))
        {
          throw new \Exception(sprintf('Unknow logger %s', $logger));
        }

        $doctrineLogger = $this->getConfiguration()->get($logger); //set logger

        $doctrine["logger"] = $doctrineLogger;
      }
    }

    return $doctrine;
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
   * Check if current environnement should display errors
   * Default to false
   * @return boolean 
   */
  public function displayErrors()
  {
    $phraseanet = $this->getPhraseanet();
    return isset($phraseanet["display_errors"]) ? !!$phraseanet["display_errors"] : false;
  }

  /**
   * Return the phraseanet scope configurations values
   * 
   * @return Array|null 
   */
  public function getPhraseanet()
  {
    return $this->getConfiguration()->get('phraseanet', array());
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
   * Return the configuration
   * 
   * @return ParameterBag\ParameterBag
   */
  public function getConfiguration()
  {
    if (null === $this->configuration)
    {
      $this->configuration = new Configuration\Parameter();
      
      if($this->installed)
      {
        $configuration = $this->configurationHandler->handle($this->getEnvironnement());
        $this->configuration = new Configuration\Parameter($configuration);
      }
    }
    
    return $this->configuration;
  }

  /**
   * Return Available logger
   * 
   * @return Array 
   */
  public function getAvailableDoctrineLogger()
  {
    return array('echo', 'monolog');
  }


}