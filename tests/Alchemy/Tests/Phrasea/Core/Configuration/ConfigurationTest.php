<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Symfony\Component\Yaml\Yaml;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $compiled;

    public function setUp()
    {
        parent::setUp();
        $this->compiled = __DIR__ . '/Fixtures/configuration-compiled.php';
        $this->clean();
    }

    public function tearDown()
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

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testGetConfigInvalidConfig()
    {
        $config = __DIR__ . '/Fixtures/configuration-unknown.yml';
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $config, $compiled, false);
        $conf->getConfig();
    }

    public function testInitialize()
    {
        $configFile = __DIR__ . '/Fixtures/configuration-unknown.yml';

        if (is_file($configFile)) {
            unlink($configFile);
        }

        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $config = $conf->initialize();

        $this->assertArrayHasKey('main', $config);
        $this->assertFileExists($compiled);
        $this->assertFileExists($configFile);

        unlink($configFile);
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testInitializeWrongPath()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = __DIR__ . '/path/to/unknwon/folder/file.php';

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->initialize();
    }

    public function testArrayAccess()
    {
        $config = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $config, $compiled, false);
        $this->assertEquals('sql-host', $conf['main']['database']['host']);

        $conf['extra-key'] = 'extra-value';
        $this->assertEquals('extra-value', $conf['extra-key']);

        $updated = $yaml->parse($config);
        $this->assertEquals('extra-value', $updated['extra-key']);

        $this->assertTrue(isset($conf['extra-key']));
        unset($conf['extra-key']);
        $this->assertFalse(isset($conf['extra-key']));

        $updated = $yaml->parse($config);
        $this->assertFalse(isset($updated['extra-key']));
    }

    public function testDelete()
    {
        $config = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $config, $compiled, false);
        $conf->initialize();
        $conf->delete();
        $this->assertFileNotExists($compiled);
    }

    public function testIsSetup()
    {
        $config = __DIR__ . '/Fixtures/configuration-setup.yml';
        $compiled = $this->compiled;

        @unlink($config);

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $config, $compiled, false);
        $this->assertFalse($conf->isSetup());
        $conf->initialize();
        $this->assertTrue($conf->isSetup());
    }

    public function testSetdefault()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->initialize();
        $config = $conf->getConfig();
        unset($config['main']);
        $conf->setConfig($config);

        $updated = $yaml->parse($configFile);
        $this->assertFalse(isset($updated['main']));

        $conf->setDefault('main');

        $updated = $yaml->parse($configFile);
        $this->assertTrue(isset($updated['main']));
    }

    public function testSetdefaultRecursive()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->initialize();
        $config = $conf->getConfig();
        unset($config['main']['cache']);
        $conf->setConfig($config);

        $updated = $yaml->parse($configFile);
        $this->assertFalse(isset($updated['main']['cache']));

        $conf->setDefault('main', 'cache');

        $updated = $yaml->parse($configFile);
        $this->assertTrue(isset($updated['main']['cache']));
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testSetdefaultInvalidKey()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->setDefault('unexistant key');
    }

    public function testGetConfig()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->initialize();

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertEquals($updated, $conf->getConfig());
    }

    public function testSetConfig()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $compiler = new Compiler();
        $yaml = new Yaml();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        $conf->setConfig(['main' => 'boule']);

        $updated = $yaml->parse(file_get_contents($configFile));
        $this->assertEquals(['main' => 'boule'], $conf->getConfig());
    }

    public function testCompilNever()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();
        file_put_contents($this->compiled, $compiler->compile($yaml->parse($configFile)));

        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $compiler->expects($this->never())
            ->method('compile');

        $yaml = $this->getMockBuilder('Symfony\Component\Yaml\Yaml')
            ->disableOriginalConstructor()
            ->getMock();
        $yaml::staticExpects($this->never())
            ->method('parse');

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
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
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();
        file_put_contents($this->compiled, $compiler->compile($yaml->parse($configFile)));

        // compilation is older than config
        touch($this->compiled, time()-2);
        touch($configFile, time()-1);
        clearstatcache();

        $compiler = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\Compiler')
            ->disableOriginalConstructor()
            ->getMock();
        $compiler->expects($this->once())
            ->method('compile')
            ->with(['main' => 'tiptop'])
            ->will($this->returnValue('<?php return array("main" => "tiptop");'));

        $yaml = $this->getMockBuilder('Symfony\Component\Yaml\Yaml')
            ->disableOriginalConstructor()
            ->getMock();
        $yaml::staticExpects($this->once())
            ->method('parse')
            ->will($this->returnValue(['main' => 'tiptop']));

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, true);
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame(['main' => 'tiptop'], $conf->getConfig());
        $this->assertSame('tiptop', $conf['main']);
        $this->assertSame('tiptop', $conf['main']);
        $this->assertSame('tiptop', $conf['main']);
    }

    public function testGetTestConnectionConf()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, true);
        $data = $conf->getTestConnectionParameters();

        $this->assertArrayHasKey('driver', $data);
        $this->assertArrayHasKey('path', $data);
        $this->assertArrayHasKey('charset', $data);
    }

    public function testCompileAndWrite()
    {
        $configFile = __DIR__ . '/Fixtures/configuration.yml';
        $compiled = $this->compiled;

        $yaml = new Yaml();
        $compiler = new Compiler();

        $conf = new Configuration($yaml, $compiler, $configFile, $compiled, false);
        // triggers initialization
        $this->assertFalse(isset($conf['bim']));

        file_put_contents($configFile, "\nbim: bam\n", FILE_APPEND);
        $this->assertFalse(isset($conf['bim']));

        $conf->compileAndWrite();
        $this->assertTrue(isset($conf['bim']));
        $this->assertEquals('bam', $conf['bim']);
    }
}
