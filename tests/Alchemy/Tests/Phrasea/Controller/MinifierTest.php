<?php

namespace Alchemy\Tests\Phrasea\Controller;

class MinifierTest extends \PhraseanetPHPUnitAbstract
{
    public function setUp()
    {
        parent::setUp();
        $this->clearGlobals();
    }

    public function tearDown()
    {
        $this->clearGlobals();
        parent::tearDown();
    }

    private function clearGlobals()
    {
        $_GET = array();
    }

    public function testGenerationOfGroups()
    {
        $groups = require __DIR__ . '/../../../../../lib/conf.d/minifyGroupsConfig.php';

        foreach ($groups as $name => $data) {
            $_GET['g'] = $name;
            self::$DI['client']->request('GET', '/include/minify/?g=' . $name);
            $this->assertTrue(self::$DI['client']->getResponse()->isOk(), "Group $name is ok");
        }
    }

    public function testFileMinification()
    {
        $groups = require __DIR__ . '/../../../../../lib/conf.d/minifyGroupsConfig.php';

        foreach ($groups as $name => $data) {
            foreach ($data as $file) {
                $file = substr($file, 2);
                $_GET['f'] = $file;
                self::$DI['client']->request('GET', '/include/minify/?f=' . $file);
                $this->assertTrue(self::$DI['client']->getResponse()->isOk(), "Group $file is ok");
                break 2;
            }
        }
    }

    public function testFileMinificationWithoutParamsShouldReturnA400()
    {
        self::$DI['client']->request('GET', '/include/minify/');
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testFileMinificationWithUnknownGroupShouldReturnA500()
    {
        self::$DI['client']->request('GET', '/include/minify/?g=prout');
        $this->assertEquals(500, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testFileMinificationWithUnknownFileShouldReturnA500()
    {
        self::$DI['client']->request('GET', '/include/minify/?f=prout');
        $this->assertEquals(500, self::$DI['client']->getResponse()->getStatusCode());
    }
}
