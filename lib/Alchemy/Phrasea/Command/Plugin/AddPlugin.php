<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;

class AddPlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:add');

        $this
            ->setDescription('Installs a plugin to Phraseanet')
            ->addArgument('source', InputArgument::REQUIRED, 'The source is a folder');
    }

    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getArgument('source');
        $shouldDownload = $this->shouldDownloadPlugin($source);

        if ($shouldDownload){
            $command = $this->getApplication()->find('plugins:download');
            $arguments = [
                'command' => 'plugins:download',
                'source'  => $source,
                'shouldInstallPlugin' => true
            ];

            $downloadInput = new ArrayInput($arguments);
            $command->run($downloadInput, $output);

        } else {

            $this->doInstallPlugin($source, $input, $output);
        }

        return 0;
    }

    protected function shouldDownloadPlugin($source)
    {
        $allowedScheme = array('https','ssh');

        $scheme =  parse_url($source, PHP_URL_SCHEME);
        if (in_array($scheme, $allowedScheme)){
            return true;
        } else{
            return false;
        }
    }
}
