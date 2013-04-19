<?php

namespace Alchemy\Tests\Phrasea\Setup\Probe;

abstract class ProbeTestCase extends \PhraseanetPHPUnitAbstract
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
        $this->assertInternalType('array', $this->getProbe()->getRecommendations());

        foreach ($this->getProbe()->getRecommendations() as $recommandation) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\System\RequirementInterface', $recommandation);
        }
    }

    public function testGetRequirements()
    {
        $this->assertInternalType('array', $this->getProbe()->getRequirements());

        foreach ($this->getProbe()->getRequirements() as $requirement) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\System\RequirementInterface', $requirement);
        }
    }

    public function testGetInformations()
    {
        $this->assertInternalType('array', $this->getProbe()->getInformations());

        foreach ($this->getProbe()->getInformations() as $information) {
            $this->assertInstanceOf('Alchemy\Phrasea\Setup\System\InformationInterface', $information);
        }
    }
}
