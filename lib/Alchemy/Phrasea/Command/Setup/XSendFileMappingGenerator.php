<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
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
            ->addArgument('type', null, 'The configuration type, either `nginx` or `apache`')
            ->setDescription('Generates Phraseanet xsendfile mapping configuration depending on databoxes configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $paths = $this->extractPath($this->container['phraseanet.appbox']);
        foreach ($paths as $path) {
            $this->container['filesystem']->mkdir($path);
        }

        $type = strtolower($input->getArgument('type'));
        $enabled = $input->getOption('enabled');

        $factory = new XSendFileFactory($this->container['monolog'], true, $type, $this->computeMapping($paths));
        $mode = $factory->getMode(true);

        $conf = array(
            'enabled' => $enabled,
            'type' => $type,
            'mapping' => $mode->getMapping(),
        );

        if ($input->getOption('write')) {
            $output->write("Writing configuration ...");
            $this->container['configuration']['xsendfile'] = $conf;
            $output->writeln(" <info>OK</info>");
            $output->writeln("");
            $output->write("It is now strongly recommended to use <info>xsendfile:dump-configuration</info> command to upgrade your virtual-host");
        } else {
            $output->writeln("Configuration will <info>not</info> be written, use <info>--write</info> option to write it");
            $output->writeln("");
            $output->writeln(Yaml::dump(array('xsendfile' => $conf), 4));
        }

        return 0;
    }

    private function computeMapping($paths)
    {
        return array_merge(array(
            array('mount-point' => 'protected_lazaret', 'directory' => $this->container['root.path'] . '/tmp/lazaret'),
            array('mount-point' => 'protected_download', 'directory' => $this->container['root.path'] . '/tmp/download'),
        ), array_map(array($this, 'pathsToConf'), array_unique($paths)));
    }

    private function pathsToConf($path)
    {
        static $n = 0;
        $n++;

        return array('mount-point' => 'protected_dir_'.$n, 'directory' => $path);
    }

    private function extractPath(\appbox $appbox)
    {
        foreach ($appbox->get_databoxes() as $databox) {
            $paths[] = (string) $databox->get_sxml_structure()->path;
            foreach ($databox->get_subdef_structure() as $group => $subdefs) {
                foreach ($subdefs as $subdef) {
                    $paths[] = $subdef->get_path();
                }
            }
        }

        return array_filter(array_unique($paths));
    }
}
