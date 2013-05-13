<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;
use Alchemy\Phrasea\Setup\RequirementCollectionInterface;

abstract class RequirementsTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return RequirementCollectionInterface
     */
    abstract protected function provideRequirements();

    public function testIsInterface()
    {
        $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementCollectionInterface', $this->provideRequirements());
    }

    public function testAdd()
    {
        $collection = $this->provideRequirements();
        $requirement = $this->getMock('Alchemy\Phrasea\Setup\RequirementInterface');
        $collection->add($requirement);

        $found = false;
        foreach ($collection->getRequirements() as $req) {
            if ($req === $requirement) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->fail('Unable to add a requirement');
        }
    }

    public function testAddCollection()
    {
        $collection = $this->provideRequirements();

        $requirement = $this->getMock('Alchemy\Phrasea\Setup\RequirementInterface');
        $coll = new RequirementCollection();
        $coll->add($requirement);

        $collection->addCollection($coll);

        $found = false;
        foreach ($collection->getRequirements() as $req) {
            if ($req === $requirement) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->fail('Unable to add a requirement');
        }
    }

    public function testAll()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('array', $collection->all());

        foreach ($collection->all() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementInterface', $requirement);
        }
    }

    public function testGetFailedRecommendations()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('array', $collection->getFailedRecommendations());

        foreach ($collection->getFailedRecommendations() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementInterface', $requirement);
        }
    }

    public function testGetFailedRequirements()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('array', $collection->getFailedRequirements());

        foreach ($collection->getFailedRequirements() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementInterface', $requirement);
        }
    }

    public function testGetInformations()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('array', $collection->getInformations());

        foreach ($collection->getInformations() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\InformationInterface', $requirement);
        }
    }

    public function testGetName()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('string', $collection->getName());
        $this->assertGreaterThan(0, strlen($collection->getName()));
    }

    public function testGetPhpIniConfigPath()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('string', $collection->getPhpIniConfigPath());
        $this->assertGreaterThan(0, strlen($collection->getPhpIniConfigPath()));
    }

    public function testHasPhpIniConfigIssue()
    {
        $collection = $this->provideRequirements();

        $this->assertInternalType('boolean', $collection->hasPhpIniConfigIssue());
    }
}
