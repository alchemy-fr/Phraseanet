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

use Cz\Git\GitRepository as GitRepository;



class DownloadPlugin extends AbstractPluginCommand
{

    public function __construct()
    {
        parent::__construct('plugins:download');

        $this
            ->setDescription('Downloads and installs a plugin to Phraseanet')
            ->addArgument('source', InputArgument::REQUIRED, 'The source is a remote url (.zip or .git)')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Temporary download destination');
    }


    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {

        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');

        if ($destination){

            $destination = trim($destination);
            $destination = ltrim($destination, '/');
            $destination = rtrim($destination, '/');

            $local_plugin_path = $destination;

        } else {

            $local_plugin_path = 'tmp/plugin';
        }

        if (!is_dir($local_plugin_path)) {
            mkdir($local_plugin_path, 0755, true);
        }

        $local_download_path = 'tmp/download';

        if (!is_dir($local_download_path)) {
            mkdir($local_download_path, 0755, true);
        }

        $local_archive_file = $local_download_path . '/tmp_file.zip';

        $extension = $this->validateSource($source);

        if ($extension){

            switch ($extension){

                case 'zip':

                    // download
                    $output->writeln("Downloading <info>$source</info>...");
                    set_time_limit(0);
                    $fp = fopen ($local_archive_file, 'w+');
                    $ch = curl_init($source);;
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    // unpack
                    $output->writeln("Unpacking <info>$source</info>...");
                    $zip = new \ZipArchive();
                    $error_unpack = false;

                    if ($zip->open($local_archive_file)) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            if (!($zip->extractTo($local_plugin_path, array($zip->getNameIndex($i))))) {
                                $error_unpack = true;
                            }
                        }
                        $zip->close();
                        unlink($local_archive_file);
                    }

                    if ($error_unpack){
                        $output->writeln("Failed unzipping <info>$source</info>");
                    }

                    break;

                case 'git':
                    $output->writeln("Downloading <info>$source</info>...");
                    $repo = GitRepository::cloneRepository($source, $local_plugin_path);
                    break;

            }

        } else {

            $output->writeln("The source <info>$source</info> is not valid remote(.zip or .git)");

        }


        $temporaryDir = $this->container['temporary-filesystem']->createTemporaryDirectory();

        $output->write("Importing <info>$source</info>...");
        $this->container['plugins.importer']->import($local_plugin_path, $temporaryDir);
        $output->writeln(" <comment>OK</comment>");

        $this->delDirTree($local_plugin_path);

        $output->write("Validating plugin...");
        $manifest = $this->container['plugins.plugins-validator']->validatePlugin($temporaryDir);
        $output->writeln(" <comment>OK</comment> found <info>".$manifest->getName()."</info>");

        $targetDir  = $this->container['plugin.path'] . DIRECTORY_SEPARATOR . $manifest->getName();

        $output->write("Setting up composer...");
        $this->container['plugins.composer-installer']->install($temporaryDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Installing plugin <info>".$manifest->getName()."</info>...");
        $this->container['filesystem']->mirror($temporaryDir, $targetDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Copying public files <info>".$manifest->getName()."</info>...");
        $this->container['plugins.assets-manager']->update($manifest);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Removing temporary directory...");
        $this->container['filesystem']->remove($temporaryDir);
        $output->writeln(" <comment>OK</comment>");

        $output->write("Activating plugin...");
        $this->container['conf']->set(['plugins', $manifest->getName(), 'enabled'], true);
        $output->writeln(" <comment>OK</comment>");

        $this->updateConfigFiles($input, $output);

        return 0;
    }
}
