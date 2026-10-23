<?php

namespace Alchemy\Tests\Phrasea\Application;

use Alchemy\Phrasea\Application;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RootApplicationTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideEnvironments')]
    public function testApplicationIsBuiltWithTheRightEnv($environment)
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';
        $this->assertEquals($environment, $app->getEnvironment());
    }

    public static function provideEnvironments()
    {
        return [
            [Application::ENV_PROD],
            [Application::ENV_TEST],
            [Application::ENV_DEV],
        ];
    }

    public function testWebProfilerDisableInProdEnv()
    {
        $environment = Application::ENV_PROD;
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';
        $this->assertFalse(isset($app['profiler']));
    }

    public function testWebProfilerDisableInTestEnv()
    {
        $environment = Application::ENV_TEST;
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';
        $this->assertFalse(isset($app['profiler']));
    }

    public function testWebProfilerEnableInDevMode()
    {
        $environment = Application::ENV_DEV;
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';
        $this->assertTrue(isset($app['profiler']));
    }
}
