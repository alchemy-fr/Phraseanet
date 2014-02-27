<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class H264MappingGenerator extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('h264-pseudo-streaming:generate-mapping');

        $this->addOption('write', 'w', null, 'Writes the configuration')
             ->addOption('enabled', 'e', null, 'Set the enable toggle to `true`')
             ->addArgument('type', InputArgument::REQUIRED, 'The configuration type, either `nginx` or `apache`')
             ->setDescription('Generates Phraseanet H264 pseudo streaming mapping configuration depending on databoxes configuration');
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

        $factory = new H264Factory($this->container['monolog'], true, $type, $this->computeMapping($paths));
        $mode = $factory->createMode(true);

        $currentConf = isset($this->container['phraseanet.configuration']['h264-pseudo-streaming']) ? $this->container['phraseanet.configuration']['h264-pseudo-streaming'] : array();
        $currentMapping = (isset($currentConf['mapping']) && is_array($currentConf['mapping'])) ? $currentConf['mapping'] : array();

        $conf = array(
            'enabled' => $enabled,
            'type' => $type,
            'mapping' => array_replace_recursive($mode->getMapping(), $currentMapping),
        );

        if ($input->getOption('write')) {
            $output->write("Writing configuration ...");
            $this->container['phraseanet.configuration']['h264-pseudo-streaming'] = $conf;
            $output->writeln(" <info>OK</info>");
            $output->writeln("");
            $output->write("It is now strongly recommended to use <info>h264-pseudo-streaming:dump-configuration</info> command to upgrade your virtual-host");
        } else {
            $output->writeln("Configuration will <info>not</info> be written, use <info>--write</info> option to write it");
            $output->writeln("");
            $output->writeln(Yaml::dump(array('h264-pseudo-streaming' => $conf), 4));
        }

        return 0;
    }

    private function computeMapping($paths)
    {
        $paths = array_unique($paths);

        $ret = array();

        foreach ($paths as $path) {
            $ret[$path] = $this->pathsToConf($path);
        }

        return $ret;
    }

    private function pathsToConf($path)
    {
        static $n = 0;
        $n++;

        return array('mount-point' => 'mp4-videos-'.$n, 'directory' => $path, 'passphrase' => \random::generatePassword(32));
    }

    private function extractPath(\appbox $appbox)
    {
        $paths = array();

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_subdef_structure() as $group => $subdefs) {
                if ('video' !== $group) {
                    continue;
                }
                foreach ($subdefs as $subdef) {
                    $paths[] = $subdef->get_path();
                }
            }
        }

        return array_filter(array_unique($paths));
    }
}
