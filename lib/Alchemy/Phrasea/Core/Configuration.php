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
use Alchemy\Phrasea\Core\Configuration\ApplicationSpecification;

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
  protected $specifications;

  /**
   * Return the current environnement
   * @var string
   */
  private $environment;

  public static function build($specifications = null, $environment = null)
  {
    if (!$specifications)
    {
      $specifications = new Configuration\ApplicationSpecification();
    }
    return new self($specifications, $environment);
  }

  public function __construct(Configuration\Specification $specifications, $environment = null)
  {
    $this->specifications = $specifications;

    if ($specifications->isSetup())
    {
      $configurations = $this->specifications->getConfigurations();
      $environment    = $environment ? : $configurations[self::KEYWORD_ENV];
    }
    else
    {
      $environment = null;
    }

    $this->setEnvironnement($environment);

    return $this;
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
  public function setEnvironnement($environment)
  {
    $this->environment = $environment;

    if ($this->specifications->isSetup())
    {
      $configurations = $this->specifications->getConfigurations();

      if(!isset($configurations[$this->environment]))
      {
        throw new \Exception('Requested environnment is not available');
      }

      $this->configuration = new ParameterBag($configurations[$this->environment]);
    }
    else
    {
      $this->configuration = new ParameterBag(array());
    }

    return $this;
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
    $phraseanetConf = $this->configuration->get('phraseanet');

    return new ParameterBag($phraseanetConf);
  }

  /**
   * Tell if the application is installed
   *
   * @return boolean
   */
  public function isInstalled()
  {
    return $this->specifications->isSetup();
  }

  public function initialize()
  {
    return $this->specifications->initialize();
  }

  public function setConfigurations($configurations)
  {
    return $this->specifications->setConfigurations($configurations);
  }

  public function setServices($services)
  {
    return $this->specifications->setServices($services);
  }

  public function setConnexions($connexions)
  {
    return $this->specifications->setConnexions($connexions);
  }

  public function getConfigurations()
  {
    return $this->specifications->getConfigurations();
  }

  public function getServices()
  {
    return $this->specifications->getServices();
  }

  public function getConnexions()
  {
    return $this->specifications->getConnexions();
  }

  const KEYWORD_ENV = 'environment';

  public function getSelectedEnvironnment()
  {
    return $this->selectedEnvironnment;
  }

  /**
   * Return the connexion parameters as configuration parameter object
   *
   * @return ParameterBag
   */
  public function getConnexion($name = 'main_connexion')
  {
    $connexions = $this->getConnexions();

    if (!isset($connexions[$name]))
    {
      throw new \Exception(sprintf('Unknown connexion name %s', $name));
    }

    return new Parameterbag($connexions[$name]);
  }

  /**
   * Return configuration service for template_engine
   * @return string
   */
  public function getTemplating()
  {
    return 'TemplateEngine\\' . $this->configuration->get('template_engine');
  }

  public function getCache()
  {
    return 'Cache\\' . $this->configuration->get('cache');
  }

  public function getOpcodeCache()
  {
    return 'Cache\\' . $this->configuration->get('opcodecache');
  }

  /**
   * Return configuration service for orm
   * @return string
   */
  public function getOrm()
  {
    return 'Orm\\' . $this->configuration->get('orm');
  }

  /**
   * Return the selected service configuration
   *
   * @param type $name
   * @return ParameterBag
   */
  public function getService($name)
  {
    $scopes   = explode('\\', $name);
    $services = new ParameterBag($this->getServices());
    $service  = null;

    while ($scopes)
    {
      $scope = array_shift($scopes);

      try
      {
        $service  = new ParameterBag($services->get($scope));
        $services = $service;
      }
      catch (\Exception $e)
      {
        throw new \Exception(sprintf('Unknow service name %s', $name));
      }
    }

    return $service;
  }

}
