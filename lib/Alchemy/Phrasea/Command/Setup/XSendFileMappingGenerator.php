<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Databox\DataboxPathExtractor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Http\XSendFile\XSendFileFactory;
use Symfony\Component\Yaml\Yaml;

class XSendFileMappingGenerator extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('xsendfile:generate-mapping');

        $this->addOption('write', 'w', null, 'Writes the configuration')
            ->addOption('enabled', 'e', null, 'Set the enable toggle to `true`')
            ->addArgument('type', InputArgument::REQUIRED, 'The configuration type, either `nginx` or `apache`')
            ->setDescription('Generates Phraseanet xsendfile mapping configuration depending on databoxes configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $extractor = new DataboxPathExtractor($this->container->getApplicationBox());
        $paths = $extractor->extractPaths('xsendfile');
        foreach ($paths as $path) {
            $this->container['filesystem']->mkdir($path);
        }

        $type = strtolower($input->getArgument('type'));
        $enabled = $input->getOption('enabled');

        $factory = new XSendFileFactory($this->container['monolog'], true, $type, $this->computeMapping($paths));
        $mode = $factory->getMode(true);

        $conf = [
            'enabled' => $enabled,
            'type' => $type,
            'mapping' => $mode->getMapping(),
        ];

        if ($input->getOption('write')) {
            $output->write("Writing configuration ...");
            $this->container['conf']->set('xsendfile', $conf);
            $output->writeln(" <info>OK</info>");
            $output->writeln("");
            $output->writeln("It is now strongly recommended to use <info>xsendfile:dump-configuration</info> command to upgrade your virtual-host");
        } else {
            $output->writeln("Configuration will <info>not</info> be written, use <info>--write</info> option to write it");
            $output->writeln("");
            $output->writeln(Yaml::dump(['xsendfile' => $conf], 4));
        }

        $output->writeln("");

        return 0;
    }

    private function computeMapping($paths)
    {
        return array_merge([
            ['mount-point' => 'protected_lazaret', 'directory' => $this->container['tmp.lazaret.path']],
            ['mount-point' => 'protected_download', 'directory' => $this->container['tmp.download.path']],
        ], array_map([$this, 'pathsToConf'], array_unique($paths)));
    }

    private function pathsToConf($path)
    {
        static $n = 0;
        $n++;

        return ['mount-point' => 'protected_dir_'.$n, 'directory' => $path];
    }
}
