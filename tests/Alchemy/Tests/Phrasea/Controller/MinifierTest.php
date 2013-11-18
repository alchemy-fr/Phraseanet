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
        $_GET = [];
    }

    /**
     * @dataProvider provideGroupsToMinify
     */
    public function testGenerationOfGroups($name)
    {
        $_GET['g'] = $name;
        self::$DI['client']->request('GET', '/include/minify/?g=' . $name);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk(), "Group $name is ok");
    }

    public function provideGroupsToMinify()
    {
        $groups = require __DIR__ . '/../../../../../lib/conf.d/minifyGroupsConfig.php';

        return array_map(function ($group) {return [$group];}, array_keys($groups));
    }

    /**
     * @dataProvider provideFilesToMinify
     */
    public function testFileMinification($file)
    {
        $_GET['f'] = $file;
        self::$DI['client']->request('GET', '/include/minify/?f=' . $file);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk(), "File $file is ok");
    }

    public function provideFilesToMinify()
    {
        $files = [];

        $groups = require __DIR__ . '/../../../../../lib/conf.d/minifyGroupsConfig.php';

        foreach ($groups as $name => $data) {
            foreach ($data as $file) {
                $files[] = substr($file, 2);
            }
        }

        return array_map(function ($file) {return [$file];}, array_unique($files));
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
