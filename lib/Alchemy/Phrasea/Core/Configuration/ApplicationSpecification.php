<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Precise some informations about phraseanet configuration mechanism
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ApplicationSpecification implements Specification
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new \Symfony\Component\Yaml\Yaml();
    }

    public function setConfigurations($configurations)
    {
        return file_put_contents(
                $this->getConfigurationsPathFile(), $this->parser->dump($configurations, 7)
        );
    }

    public function setConnexions($connexions)
    {
        return file_put_contents(
                $this->getConnexionsPathFile(), $this->parser->dump($connexions, 7)
        );
    }

    public function setServices($services)
    {
        return file_put_contents(
                $this->getServicesPathFile(), $this->parser->dump($services, 7)
        );
    }

    public function getConfigurations()
    {
        return $this->parser->parse(
                file_get_contents($this->getConfigurationsPathFile())
        );
    }

    public function getConnexions()
    {
        return $this->parser->parse(
                file_get_contents($this->getConnexionsPathFile())
        );
    }

    public function getServices()
    {
        return $this->parser->parse(
                file_get_contents($this->getServicesPathFile())
        );
    }

    protected function getConfigurationsFile()
    {
        return new SymfonyFile($this->getConfigurationsPathFile(), true);
    }

    protected function getConnexionsFile()
    {
        return new SymfonyFile($this->getConnexionsPathFile(), true);
    }

    protected function getServicesFile()
    {
        return new SymfonyFile($this->getServicesPathFile(), true);
    }

    public function delete()
    {
        $files = array(
            $this->getConnexionsPathFile(),
            $this->getConfigurationsPathFile(),
            $this->getServicesPathFile()
        );

        foreach ($files as $file) {
            if (file_exists($file))
                unlink($file);
        }
    }

    public function initialize()
    {
        $this->delete();

        copy(
            $this->getRealRootPath() . "/config/connexions.sample.yml"
            , $this->getConnexionsPathFile()
        );

        copy(
            $this->getRealRootPath() . "/config/services.sample.yml"
            , $this->getServicesPathFile()
        );

        copy(
            $this->getRealRootPath() . "/config/config.sample.yml"
            , $this->getConfigurationsPathFile()
        );

        if (function_exists('chmod')) {
            chmod($this->getConnexionsPathFile(), 0700);
            chmod($this->getConfigurationsPathFile(), 0700);
            chmod($this->getServicesPathFile(), 0700);
        }
    }

    public function isSetup()
    {
        try {
            $this->getConfigurationsFile();
            $this->getConnexionsFile();
            $this->getServicesFile();

            return true;
        } catch (FileNotFoundException $e) {

        }

        return false;
    }

    protected function getConfigurationsPathFile()
    {
        return $this->getRealRootPath() . '/config/config.yml';
    }

    protected function getConnexionsPathFile()
    {
        return $this->getRealRootPath() . '/config/connexions.yml';
    }

    protected function getServicesPathFile()
    {
        return $this->getRealRootPath() . '/config/services.yml';
    }

    protected function getRealRootPath()
    {
        return realpath(__DIR__ . '/../../../../../');
    }
}
