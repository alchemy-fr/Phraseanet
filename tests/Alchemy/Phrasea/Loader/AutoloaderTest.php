<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class AutoloaderTest extends \PhraseanetPHPUnitAbstract
{

    /**
     * @covers Alchemy\Phrasea\Loader\Autoloader::findFile
     */
    public function testFindFile()
    {
        $testClassName = 'Test_Hello';
        $autoloader = new Alchemy\Phrasea\Loader\Autoloader();
        $autoloader->addPath('fixture', __DIR__ . '/Fixtures');
        $autoloader->loadClass($testClassName);
        $this->assertTrue(class_exists($testClassName));
    }

    /**
     * @covers Alchemy\Phrasea\Loader\Autoloader::addPath
     */
    public function testAddPath()
    {
        $autoloader = new Alchemy\Phrasea\Loader\Autoloader();
        $pathNb = count($autoloader->getPaths());
        $autoloader->addPath('fixture', __DIR__ . '/Fixtures');
        $this->assertGreaterThan($pathNb, count($autoloader->getPaths()));
        $this->assertArrayHasKey('fixture', $autoloader->getPaths());
    }

    /**
     * @covers Alchemy\Phrasea\Loader\Autoloader::getPaths
     */
    public function testGetPath()
    {
        $autoloader = new Alchemy\Phrasea\Loader\Autoloader();
        $this->assertTrue(is_array($autoloader->getPaths()));
        $this->assertTrue(2 === count($autoloader->getPaths()));
        $this->assertArrayHasKey('config', $autoloader->getPaths());
        $this->assertArrayHasKey('library', $autoloader->getPaths());
    }
}
