<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

/**
 * @see https://github.com/sensio/SensioDistributionBundle/blob/master/Resources/skeleton/app/SymfonyRequirements.php
 *
 * A RequirementCollection represents a set of Requirement instances.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class RequirementCollection implements RequirementCollectionInterface
{
    private $requirements = [];
    private $informations = [];
    private $name;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function addInformation($name, $value)
    {
        $this->informations[] = new Information($name, $value);

        return $this;
    }

    public function getInformations()
    {
        return $this->informations;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->requirements);
    }

    /**
     * {@inheritdoc}
     */
    public function add(RequirementInterface $requirement)
    {
        $this->requirements[] = $requirement;
    }

    /**
     * {@inheritdoc}
     */
    public function addRequirement($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * {@inheritdoc}
     */
    public function addRecommendation($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new Requirement($fulfilled, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * {@inheritdoc}
     */
    public function addPhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * {@inheritdoc}
     */
    public function addPhpIniRecommendation($cfgName, $evaluation, $approveCfgAbsence = false, $testMessage = null, $helpHtml = null, $helpText = null)
    {
        $this->add(new PhpIniRequirement($cfgName, $evaluation, $approveCfgAbsence, $testMessage, $helpHtml, $helpText, true));
    }

    /**
     * {@inheritdoc}
     */
    public function addCollection(RequirementCollection $collection)
    {
        $this->requirements = array_merge($this->requirements, $collection->all());
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->requirements;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirements()
    {
        $array = [];
        foreach ($this->requirements as $req) {
            if (!$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getFailedRequirements()
    {
        $array = [];
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && !$req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecommendations()
    {
        $array = [];
        foreach ($this->requirements as $req) {
            if ($req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getFailedRecommendations()
    {
        $array = [];
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req->isOptional()) {
                $array[] = $req;
            }
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPhpIniConfigIssue()
    {
        foreach ($this->requirements as $req) {
            if (!$req->isFulfilled() && $req instanceof PhpIniRequirement) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpIniConfigPath()
    {
        return get_cfg_var('cfg_file_path');
    }
}
