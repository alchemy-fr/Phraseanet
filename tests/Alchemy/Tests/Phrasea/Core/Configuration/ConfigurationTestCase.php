<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Symfony\Component\Yaml\Yaml;

abstract class ConfigurationTestCase extends \PhraseanetTestCase
{
    protected $compiled;

    public function setUp(): void
    {
        parent::setUp();
        $this->compiled = __DIR__ . '/Fixtures/configuration-compiled.php';
        $this->clean();
    }

    public function tearDown(): void
    {
        $this->clean();
        parent::tearDown();
    }

    private function clean()
    {
        copy(__DIR__ . '/..' . Configuration::CONFIG_REF, __DIR__ . '/Fixtures/configuration.yml');
        if (is_file($this->compiled)) {
            unlink($this->compiled);
        }
    }

    abstract protected function provideConfiguration($confFile, $compiledFile = null, ?Compiler $compiler = null, ?Yaml $yaml = null);

    public function testGetConfigInvalidConfig()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $config = __DIR__ . '/Fixtures/configuration-unknown.yml';

        $conf = $this->provideConfiguration($config);
        $conf->getConfig();
    }

    public function testInitialize()
    {
        $configFile = __DIR__ . '/Fixtures/configuration-unknown.yml';

        if (is_file($configFile)) {
            unlink($configFile);
        }

        $compiled = $this->compiled;

        $conf = $this->provideConfiguration($configFile);
        $this->assertSame($conf, $conf->initialize());
        $config = $conf->getConfig();

        $this->assertArrayHasKey('main', $config);
        $this->assertFileExists($compiled);
        $this->assertFileExists($configFile);

        unlink($configFile);
    }

    public function testInitializeWrongPath()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\RuntimeException::class);
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = __DIR__ . '/path/to/unknwon/folder/file.php';

        $conf = $this->provideConfiguration($configFile, $compiled);
        $conf->initialize();
    }

    public function testArrayAccess()
    {
        $config = __DIR__ . '/Fixtures/configuration.yml';
        $yaml = new Yaml();

        $conf = $this->provideConfiguration($config);
        $this->assertEquals('sql-host', $conf['main']['database']['host']);

        $conf['extra-key'] = 'extra-value';
        $this->assertEquals('extra-value', $conf['extra-key']);

        $updated = $yaml->parse(file_get_contents($config));
        $this->assertEquals('extra-value', $updated['extra-key']);

        $this->assertTrue(isset($conf['extra-key']));
        unset($conf['extra-key']);
        $this->assertFalse(isset($conf['extra-key']));

        $updated = $yaml->parse(file_get_contents($config));
        $this->assertFalse(isset($updated['extra-key']));
    }

    public function testDelete()
    {
        $config = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $conf = $this->provideConfiguration($config);
        $conf->initialize();
        $conf->delete();
        $this->assertFileDoesNotExist($compiled);
    }

    public function testIsSetup()
    {
        $config = __DIR__ . '/Fixtures/configuration-setup.yml';

        @unlink($config);

        $conf = $this->provideConfiguration($config);
        $this->assertFalse($conf->isSetup());
        $conf->initialize();
        $this->assertTrue($conf->isSetup());
    }

    public function testSetdefault()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $yaml = new Yaml();

        $conf = $this->provideConfiguration($configFile);
        $conf->initialize();
        $config = $conf->getConfig();
        unset($config['main']);
        $conf->setConfig($config);

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertFalse(isset($updated['main']));

        $conf->setDefault('main');

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertTrue(isset($updated['main']));
    }

    public function testSetdefaultRecursive()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $yaml = new Yaml();

        $conf = $this->provideConfiguration($configFile);
        $conf->initialize();
        $config = $conf->getConfig();
        unset($config['main']['cache']);
        $conf->setConfig($config);

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertFalse(isset($updated['main']['cache']));

        $conf->setDefault('main', 'cache');

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertTrue(isset($updated['main']['cache']));
    }

    public function testSetdefaultInvalidKey()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $configFile = __DIR__ . '/Fixtures/configuration.yml';

        $conf = $this->provideConfiguration($configFile);
        $conf->setDefault('unexistant key');
    }

    public function testGetConfig()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $yaml = new Yaml();

        $conf = $this->provideConfiguration($configFile);
        $conf->initialize();

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertEquals($updated, $conf->getConfig());
    }

    public function testSetConfig()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $yaml = new Yaml();

        $conf = $this->provideConfiguration($configFile);
        $conf->setConfig(['main' => 'boule']);

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertEquals(['main' => 'boule'], $conf->getConfig());
    }

    public function testCompilNever()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';

        $yaml = new Yaml();
        $compiler = new Compiler();
        file_put_contents($this->compiled, $compiler->compile($yaml->parse(file_get_contents($configFile))));

        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $compiler->expects($this->never())
                 ->method('compile');

        // This Yaml Parser throws exception when trying to parse.
        $yaml = new YamlCountingParse();
        $yaml::reset();
        $yaml::setParseBehavior(function () {
            throw new \RuntimeException('Should not be called');
        });

        $conf = $this->provideConfiguration($configFile, null, $compiler, $yaml);
        $conf->getConfig();
        $conf->getConfig();
        $conf->getConfig();
        $conf['main'];
        $conf['main'];
        $conf['main'];
    }

    public function testCompilInDebugMode()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';

        $yaml = new Yaml();
        $compiler = new Compiler();
        file_put_contents($this->compiled, $compiler->compile($yaml->parse(file_get_contents($configFile))));

        // compilation is older than config
        touch($this->compiled, time()-2);
        touch($configFile, time()-1);
        clearstatcache();

        $compiler = $this->createMock('Alchemy\Phrasea\Core\Configuration\Compiler');
        $compiler->expects($this->once())
                 ->method('compile')
                 ->with(['main' => 'tiptop'])
                 ->will($this->returnValue('<?php return ["main" => "tiptop"];'));

        $yaml = new YamlCountingParse();
        $yaml::reset();
        $yaml::setParseBehavior(function () {
            return ['main' => 'tiptop'];
        });
        $conf = $this->provideConfiguration($configFile, null, $compiler, $yaml, true);
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame('tiptop', $conf['main']);
        $this->assertSame('tiptop', $conf['main']);
        $this->assertSame('tiptop', $conf['main']);
        $this->assertEquals(1, $yaml::getParseCount());
    }

    public function testCompileAndWrite()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';

        $conf = $this->provideConfiguration($configFile);
        // triggers initialization
        $this->assertFalse(isset($conf['bim']));

        file_put_contents($configFile, "\nbim: bam\n", FILE_APPEND);
        $this->assertFalse(isset($conf['bim']));

        $conf->compileAndWrite();
        $this->assertTrue(isset($conf['bim']));
        $this->assertEquals('bam', $conf['bim']);
    }
}
