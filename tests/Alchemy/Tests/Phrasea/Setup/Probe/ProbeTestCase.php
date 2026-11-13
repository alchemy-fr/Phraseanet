<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

abstract class ProbeTestCase extends \PhraseanetTestCase
{
    abstract protected function getClassName();

    private function getProbe()
    {
        $classname = $this->getClassName();

        return $classname::create(self::$DI['app']);
    }

    public function testIsInstance()
    {
        $this->assertInstanceOf('Alchemy\Phrasea\Setup\Probe\ProbeInterface', $this->getProbe());
    }

    public function testGetRecommendations()
    {
        $this->assertIsArray($this->getProbe()->getRecommendations());

        foreach ($this->getProbe()->getRecommendations() as $recommandation) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementInterface', $recommandation);
        }
    }

    public function testGetRequirements()
    {
        $this->assertIsArray($this->getProbe()->getRequirements());

        foreach ($this->getProbe()->getRequirements() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\RequirementInterface', $requirement);
        }
    }

    public function testGetInformations()
    {
        $this->assertIsArray($this->getProbe()->getInformations());

        foreach ($this->getProbe()->getInformations() as $information) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\InformationInterface', $information);
        }
    }
}
