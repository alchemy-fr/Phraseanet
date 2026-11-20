<?php

namespace Alchemy\Tests\Phrasea\Controller;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('minify')]
class MinifierTest extends \PhraseanetTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->clearGlobals();
    }

    public function tearDown(): void
    {
        $this->clearGlobals();
        parent::tearDown();
    }

    private function clearGlobals()
    {
        $_GET = [];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideFilesToMinify')]
    public function testFileMinification($file)
    {
        $_GET['f'] = $file;
        self::$DI['client']->request('GET', '/include/minify/?f=' . $file);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk(), "File $file is ok");
    }

    public static function provideFilesToMinify()
    {
        return [['scripts/apps/admin/require.config.js']];
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
