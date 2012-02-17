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
  public function __construct(Configuration\Handler $handler, $environment = null)
  {
    $this->configurationHandler = $handler;
    $this->installed = false;
    $this->environment = $environment;

    try
    {

      //if one of this files are missing consider phraseanet not installed
      $handler->getSpecification()->getConfigurationFile();
      $handler->getSpecification()->getServiceFile();
      $handler->getSpecification()->getConnexionFile();

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
    if (null === $this->environment && $this->isInstalled())
    {
      $this->refresh();
    }

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
    $this->refresh();
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
  public function isDisplayingErrors()
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
    $phraseanetConf = $this->getConfiguration()->get('phraseanet');

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

    if ($this->installed && null === $this->configuration)
    {
      $configuration = $this->configurationHandler->handle($this->environment);
      $this->environment = $this->configurationHandler->getSelectedEnvironnment();
      $this->configuration = new ParameterBag($configuration);
    }
    elseif(!$this->installed)
    {
      $configuration = array();
      $this->configuration = new ParameterBag($configuration);
    }

    return $this->configuration;
  }

  /**
   * Return the connexion parameters as configuration parameter object
   *
   * @return ParameterBag
   */
  public function getConnexion($name = 'main_connexion')
  {
    $connexions = $this->getConnexions();

    try
    {
      $conn = $connexions->get($name);
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf('Unknow connexion name %s declared in %s'
                      , $name
                      , $this->configurationHandler
                              ->getSpecification()
                              ->getConnexionFile()
                              ->getFileName()
              )
      );
    }

    return new Parameterbag($conn);
  }

  /**
   * Return all connexions defined in connexions.yml
   * @return ParameterBag
   */
  public function getConnexions()
  {
    return new ParameterBag($this->configurationHandler->getParser()->parse(
                            $this->configurationHandler->getSpecification()->getConnexionFile()
                    )
    );
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

  /**
   * Return all services defined in services.yml
   * @return ParameterBag
   */
  public function getServices()
  {
    return new ParameterBag($this->configurationHandler->getParser()->parse(
                            $this->getServiceFile()
                    )
    );
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

    if (false === file_put_contents($filePathName, $yaml, $flag))
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
    $deleted = false;

    try
    {
      $filePathName = $this
              ->configurationHandler
              ->getSpecification()
              ->getConfigurationPathName();

      $deleted = unlink($filePathName);
    }
    catch (\Exception $e)
    {

    }

    if (!$deleted)
    {
      throw new \Exception(sprintf(_('Impossible d\'effacer le fichier %s'), $filePathName));
    }

    return $this;
  }

  /**
   * Return configuration service for template_engine
   * @return string
   */
  public function getTemplating()
  {
    return $this->getConfiguration()->get('template_engine');
  }

  /**
   * Return configuration service for orm
   * @return string
   */
  public function getOrm()
  {
    return $this->getConfiguration()->get('orm');
  }

  /**
   * Return the selected service configuration
   *
   * @param type $name
   * @return ParameterBag
   */
  public function getService($name = 'twig')
  {
    $services = $this->getServices();

    try
    {
      $template = $services->get($name);
    }
    catch (\Exception $e)
    {
      throw new \Exception(sprintf('Unknow service name %s declared in %s'
                      , $name
                      , $this->configurationHandler
                              ->getSpecification()
                              ->getServiceFile()
                              ->getFileName()
              )
      );
    }

    return new ParameterBag($template);
  }

  /**
   * return the service file
   * @return \SplFileObject
   */
  public function getServiceFile()
  {
    return $this->configurationHandler->getSpecification()->getServiceFile();
  }

  /**
   * Return the connexion file
   * @return \SplFileObject
   */
  public function getConnexionFile()
  {
    return $this->configurationHandler->getSpecification()->getConnexionFile();
  }

  /**
   * Refresh the configuration
   * @return Configuration
   */
  public function refresh()
  {
    try
    {
      $this->configurationHandler->getSpecification()->getConfigurationFile();
      $this->configurationHandler->getSpecification()->getServiceFile();
      $this->configurationHandler->getSpecification()->getConnexionFile();

      $this->installed = true;
    }
    catch (\Exception $e)
    {
      $this->installed = false;
    }

    if ($this->installed)
    {
      $configuration = $this->configurationHandler->handle($this->environment);
      $this->environment = $this->configurationHandler->getSelectedEnvironnment();
      $this->configuration = new ParameterBag($configuration);
    }

    return $this;
  }

}
