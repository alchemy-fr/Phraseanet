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

use Cz\Git\GitRepository as GitRepository;



class DownloadPlugin extends AbstractPluginCommand
{

    public function __construct()
    {
        parent::__construct('plugins:download');

        $this
            ->setDescription('Downloads a plugin to Phraseanet')
            ->addArgument('source', InputArgument::REQUIRED, 'The source is a remote url (.zip or .git)')
            ->addArgument('destination', InputArgument::OPTIONAL, 'Download destination');
    }


    protected function doExecutePluginAction(InputInterface $input, OutputInterface $output)
    {

        $source = $input->getArgument('source');
        $destination = $input->getArgument('destination');

        $destination_subdir = '/plugin-'.time();

        if ($destination){

            $destination = trim($destination);
            $destination = ltrim($destination, '/');
            $destination = rtrim($destination, '/');

            $local_download_path = $destination;

        } else {

            $local_download_path = 'tmp/plugin-download' . $destination_subdir;
        }

        if (!is_dir($local_download_path)) {
            mkdir($local_download_path, 0755, true);
        }

        $local_unpack_path = 'tmp/plugin-zip'. $destination_subdir;

        if (!is_dir($local_unpack_path)) {
            mkdir($local_unpack_path, 0755, true);
        }

        $local_archive_file = $local_unpack_path . '/plugin-downloaded.zip';

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
                            if (!($zip->extractTo($local_download_path, array($zip->getNameIndex($i))))) {
                                $error_unpack = true;
                            }
                        }
                        $zip->close();
                    }

                    if ($error_unpack){
                        $output->writeln("Failed unzipping <info>$source</info>");
                    } else {
                        $output->writeln("Plugin downloaded to <info>$local_download_path</info>");
                    }

                    // remove zip archive
                    $this->delDirTree($local_unpack_path);

                    break;

                case 'git':
                    $output->writeln("Downloading <info>$source</info>...");
                    $repo = GitRepository::cloneRepository($source, $local_download_path);
                    $output->writeln("Plugin downloaded to <info>$local_download_path</info>");
                    break;

            }

        } else {

            $output->writeln("The source <info>$source</info> is not valid remote(.zip or .git)");
        }

        return 0;
    }
}
