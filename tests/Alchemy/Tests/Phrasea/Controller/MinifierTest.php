<?php

namespace Alchemy\Tests\Phrasea\Controller;

/**
 * @group functional
 * @group legacy
 * @group minify
 */
class MinifierTest extends \PhraseanetTestCase
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
        return [['scripts/apps/admin/require.config.js']];
    }

    public function testFileMinificationWithoutParamsShouldReturnA400()
    {
        self::$DI['client']->request('GET', '/include/minify/');
        $this->assertEquals(400, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testFileMinificationWithUnknownGroupShouldReturnA500()
    {
        // there is an exception thrown
        try {
            $this->fail('An exception should have been raised');
            self::$DI['client']->request('GET', '/include/minify/?g=prout');
        } catch (\Exception $e) {
        }
    }

    public function testFileMinificationWithUnknownFileShouldReturnA500()
    {
        // there is an exception thrown
        try {
            $this->fail('An exception should have been raised');
            self::$DI['client']->request('GET', '/include/minify/?f=prout');
        } catch (\Exception $e) {
        }
    }
}
