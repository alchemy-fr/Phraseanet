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
   * Return the current environnement
   * @var string
   */
  private $environment;

  /**
   * 
   * @param type $envName the name of the loaded environnement
   */
  public function __construct(Configuration\Handler $handler)
  {
    $this->configurationHandler = $handler;
    $this->installed = false;

    try
    {
      $handler->getSpecification()->getConfigurationFile();

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
    return $this->environment;
  }

  /**
   * Return the current used environnement
   * 
   * @return string 
   */
  public function setEnvironnement($environement = null)
  {
    $this->environment = $environement;
  }

  /**
   * Return the DBAL Doctrine configuration
   * 
   * @return ParameterBag 
   */
  public function getDoctrine()
  {
    $doctrine = $this->getConfiguration()->get('doctrine', array()); //get doctrine scope

    if (count($doctrine) > 0)
    {
      $doctrine["debug"] = $this->isDebug(); //set debug

      if (!!$doctrine["log"]['enable'])
      {
        $logger = isset($doctrine["log"]["type"]) ? $doctrine["log"]["type"] : 'monolog';

        if (!in_array($doctrine["log"]["type"], $this->getAvailableDoctrineLogger()))
        {
          throw new \Exception(sprintf('Unknow logger %s', $logger));
        }

        $doctrineLogger = $this->getConfiguration()->get($logger); //set logger

        $doctrine["logger"] = $doctrineLogger;
      }
    }

    return new ParameterBag($doctrine);
  }

  /**
   * Check if current environnement is on debug mode
   * Default to false
   * @return boolean 
   */
  public function isDebug()
  {
    $phraseanet = $this->getPhraseanet();

    try
    {
      $debug = !!$phraseanet->get('debug');
    }
    catch (\Exception $e)
    {
      $debug = false;
    }
    return $debug;
  }

  /**
   * Check if phrasea is currently maintained
   * Default to false
   * @return boolean 
   */
  public function isMaintained()
  {
    $phraseanet = $this->getPhraseanet();

    try
    {
      $maintained = !!$phraseanet->get('maintenance');
    }
    catch (\Exception $e)
    {
      $maintained = false;
    }
    return $maintained;
  }

  /**
   * Check if current environnement should display errors
   * Default to false
   * @return boolean 
   */
  public function displayErrors()
  {
    $phraseanet = $this->getPhraseanet();

    try
    {
      $displayErrors = !!$phraseanet->get('display_errors');
    }
    catch (\Exception $e)
    {
      $displayErrors = false;
    }
    return $displayErrors;
  }

  /**
   * Return the phraseanet scope configurations values
   * 
   * @return ParameterBag
   */
  public function getPhraseanet()
  {
    $phraseanetConf = $this->getConfiguration()->get('phraseanet', array());
    return new ParameterBag($phraseanetConf);
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

      if ($this->installed)
      {
        $configuration = $this->configurationHandler->handle($this->environment);
        $this->environment = $this->configurationHandler->getSelectedEnvironnment();
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

  /**
   * Return the connexion parameters as configuration parameter object
   * 
   * @return ParameterBag
   */
  public function getConnexion()
  {
    return new ParameterBag($this->getPhraseanet()->get('database'));
  }

  /**
   * Return a the configuration file as an SplFileObject
   * 
   * @return \SplFileObject
   */
  public function getFile()
  {
    return $this->configurationHandler->getSpecification()->getConfigurationFile();
  }

  /**
   * Return the full configuration file as an Array
   * 
   * @return Array 
   */
  public function all()
  {
    $allConf = $this->configurationHandler->getParser()->parse($this->getFile());
    return $allConf;
  }

  public function setAllDatabaseConnexion(Array $connexion)
  {
    $arrayConf = $this->all();

    foreach ($arrayConf as $key => $value)
    {
      if (is_array($value) && array_key_exists('phraseanet', $value))
      {
        foreach ($arrayConf[$key]['phraseanet'] as $kee => $value)
        {
          if ($kee === 'database')
          {
            $arrayConf[$key]['phraseanet']['database'] = $connexion;
          }
        }
      }
    }

    $connexion["driver"] = 'pdo_mysql';
    $connexion['charset'] = 'UTF8';

    foreach ($arrayConf as $key => $value)
    {
      if (is_array($value) && array_key_exists('phraseanet', $value))
      {
        foreach ($arrayConf[$key]['doctrine'] as $kee => $value)
        {
          if ($kee === 'dbal')
          {
            $arrayConf[$key]['doctrine']['dbal'] = $connexion;
          }
        }
      }
    }

    $this->write($arrayConf, FILE_APPEND, true);
  }

  /**
   * Write datas in config file
   * 
   * @param array $data
   * @param type $flag
   * @return Configuration 
   */
  public function write(Array $data, $flag = 0, $delete = false)
  {
    if ($delete)
    {
      $this->delete();
    }

    $yaml = $this->configurationHandler->getParser()->dump($data, 5);

    $filePathName = $this->configurationHandler
            ->getSpecification()
            ->getConfigurationPathName();

    if (!file_put_contents($filePathName, $yaml, $flag) !== false)
    {
      $filePath = $this->configurationHandler
              ->getSpecification()
              ->getConfigurationFilePath();
      throw new \Exception(sprintf(_('Impossible d\'ecrire dans le dossier %s'), $filePath));
    }

    return $this;
  }

  /**
   * Delete configuration file
   * @return Configuration 
   */
  public function delete()
  {
    try
    {
      $filePathName = $this->configurationHandler
              ->getSpecification()
              ->getConfigurationPathName();
      unlink($filePathName);
    }
    catch (\Exception $e)
    {
      
    }

    return $this;
  }

}