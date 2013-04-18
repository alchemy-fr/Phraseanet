<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\System\RequirementCollectionInterface;

abstract class RequirementsTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return RequirementCollectionInterface
     */
    abstract protected function provideRequirements();

    public function testIsInterface()
    {
        $this->assertInstanceOf('Alchemy\Phrasea\Setup\System\RequirementCollectionInterface', $this->provideRequirements());
    }

    public function testAdd()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAddCollection()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAddPhpIniRecommendation()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAddPhpIniRequirement()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAddRecommendation()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAddRequirement()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testAll()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetFailedRecommendations()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetFailedRequirements()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetInformations()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetName()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetPhpIniConfigPath()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetRecommendations()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testGetRequirements()
    {
        $this->markTestIncomplete('Incomplete');
    }

    public function testHasPhpIniConfigIssue()
    {
        $this->markTestIncomplete('Incomplete');
    }
}
