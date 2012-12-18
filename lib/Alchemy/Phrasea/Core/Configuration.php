<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

use Alchemy\Phrasea\Core\Configuration\ApplicationSpecification;
use Alchemy\Phrasea\Core\Configuration\SpecificationInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Handle configuration file mechanism of phraseanet
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Configuration
{
    const KEYWORD_ENV = 'environment';
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

    /**
     *
     * @param  ApplicationSpecification $specifications
     * @param  type                                   $environment
     * @return Configuration
     */
    public static function build($specifications = null, $environment = null)
    {
        if ( ! $specifications) {
            $specifications = new ApplicationSpecification();
        }

        return new self($specifications, $environment);
    }

    /**
     *
     * @param  SpecificationInterface         $specifications
     * @param  type                                $environment
     * @return Configuration
     */
    public function __construct(SpecificationInterface $specifications, $environment = null)
    {
        $this->specifications = $specifications;

        if ($specifications->isSetup()) {
            $configurations = $this->specifications->getConfigurations();
            $environment = $environment ? : $configurations[self::KEYWORD_ENV];
        } else {
            $environment = null;
        }

        $this->setEnvironnement($environment);

        return $this;
    }

    public function get($name)
    {
        return $this->configuration->get($name);
    }

    public function has($name)
    {
        return $this->configuration->has($name);
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

        if ($this->specifications->isSetup()) {
            $configurations = $this->specifications->getConfigurations();

            if ( ! isset($configurations[$this->environment])) {
                throw new \Exception('Requested environnment is not available');
            }

            $this->configuration = new ParameterBag($configurations[$this->environment]);
        } else {
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
        try {
            $debug = (Boolean) $this->getPhraseanet()->get('debug');
        } catch (\Exception $e) {
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
        try {
            $maintained = (Boolean) $this->getPhraseanet()->get('maintenance');
        } catch (\Exception $e) {
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
        try {
            $displayErrors = (Boolean) $this->getPhraseanet()->get('display_errors');
        } catch (\Exception $e) {
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
        return new ParameterBag($this->configuration->get('phraseanet'));
    }

    public function initialize()
    {
        $this->specifications->initialize();
        $this->setEnvironnement('prod');

        return $this;
    }

    public function delete()
    {
        return $this->specifications->delete();
    }

    public function setConfigurations($configurations)
    {
        return $this->specifications->setConfigurations($configurations);
    }

    public function setServices($services)
    {
        return $this->specifications->setServices($services);
    }

    public function setBinaries($binaries)
    {
        return $this->specifications->setBinaries($binaries);
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

    public function getBinaries()
    {
        return $this->specifications->getBinaries();
    }

    public function getConnexions()
    {
        return $this->specifications->getConnexions();
    }

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

        if ( ! isset($connexions[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown connexion name %s', $name));
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

    public function getSearchEngine()
    {
        return 'SearchEngine\\' . $this->configuration->get('search-engine');
    }

    /**
     * Return border service for border-manager
     * @return string
     */
    public function getBorder()
    {
        return 'Border\\' . $this->configuration->get('border-manager');
    }

    public function getTaskManager()
    {
       return 'TaskManager\\' . $this->configuration->get('task-manager');
    }

    /**
     * Return the selected service configuration
     *
     * @param  type         $name
     * @return ParameterBag
     */
    public function getService($name)
    {
        $scopes = explode('\\', $name);
        $services = new ParameterBag($this->getServices());
        $service = null;

        while ($scopes) {
            $scope = array_shift($scopes);

            try {
                $service = new ParameterBag($services->get($scope));
                $services = $service;
            } catch (\Exception $e) {
                throw new InvalidArgumentException(sprintf('Unknow service name %s', $name));
            }
        }

        return $service;
    }
}
