<?php

namespace Alchemy\Tests\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Plugin\AddPlugin;

class AddPluginTest extends PluginCommandTestCase
{
    public function testExecute()
    {
        $source = 'TestPlugin';

        $input = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $input->expects($this->once())
            ->method('getArgument')
            ->with($this->equalTo('source'))
            ->will($this->returnValue($source));

        $output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

        $command = new AddPlugin();
        $command->setContainer(self::$DI['cli']);

        $manifest = $this->createManifestMock();
        $manifest->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($source));

        self::$DI['cli']['temporary-filesystem'] = $this->createTemporaryFilesystemMock();
        self::$DI['cli']['plugins.autoloader-generator'] = $this->createPluginsAutoloaderGeneratorMock();
        self::$DI['cli']['plugins.explorer'] = array(self::$DI['cli']['plugins.directory'].'/TestPlugin');
        self::$DI['cli']['plugins.plugins-validator'] = $this->createPluginsValidatorMock();
        self::$DI['cli']['filesystem'] = $this->createFilesystemMock();
        self::$DI['cli']['plugins.composer-installer'] = $this->createComposerInstallerMock();
        self::$DI['cli']['plugins.importer'] = $this->createPluginsImporterMock();

        self::$DI['cli']['temporary-filesystem']->expects($this->once())
            ->method('createTemporaryDirectory')
            ->will($this->returnValue('tempdir'));

        self::$DI['cli']['plugins.importer']->expects($this->once())
            ->method('import')
            ->with($source, 'tempdir');

        // the plugin is checked when updating config files
        self::$DI['cli']['plugins.plugins-validator']->expects($this->at(0))
            ->method('validatePlugin')
            ->with('tempdir')
            ->will($this->returnValue($manifest));

        self::$DI['cli']['plugins.plugins-validator']->expects($this->at(1))
            ->method('validatePlugin')
            ->with(self::$DI['cli']['plugins.directory'].'/TestPlugin')
            ->will($this->returnValue($manifest));

        self::$DI['cli']['plugins.composer-installer']->expects($this->once())
            ->method('install')
            ->with('tempdir');

        self::$DI['cli']['filesystem']->expects($this->at(0))
            ->method('mirror')
            ->with('tempdir', self::$DI['cli']['plugins.directory'].'/TestPlugin');

        self::$DI['cli']['filesystem']->expects($this->at(1))
            ->method('mirror')
            ->with(self::$DI['cli']['plugins.directory'].'/TestPlugin/public', self::$DI['cli']['root.path'].'/www/plugins/TestPlugin');

        self::$DI['cli']['filesystem']->expects($this->at(2))
            ->method('remove')
            ->with('tempdir');

        self::$DI['cli']['plugins.autoloader-generator']->expects($this->once())
            ->method('write')
            ->with(array($manifest));

        $result = $command->execute($input, $output);

        $this->assertSame(0, $result);
    }
}
