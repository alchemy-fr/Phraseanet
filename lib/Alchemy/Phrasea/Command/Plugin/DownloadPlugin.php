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

        $destinationSubdir = '/plugin-'.md5($source);

        if ($destination){

            $destination = trim($destination);
            $destination = rtrim($destination, '/');

            $localDownloadPath = $destination;

        } else {

            $localDownloadPath = '/tmp/plugin-download' . $destinationSubdir;
        }

        if (!is_dir($localDownloadPath)) {
            mkdir($localDownloadPath, 0755, true);
        }

        $extension = $this->getURIExtension($source);

        if ($extension){

            switch ($extension){

                case 'zip':

                    $localUnpackPath = '/tmp/plugin-zip'. $destinationSubdir;

                    if (!is_dir($localUnpackPath)) {
                        mkdir($localUnpackPath, 0755, true);
                    }

                    $localArchiveFile = $localUnpackPath . '/plugin-downloaded.zip';

                    // download
                    $output->writeln("Downloading <info>$source</info>...");
                    set_time_limit(0);
                    $fp = fopen ($localArchiveFile, 'w+');
                    $ch = curl_init($source);;
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    // unpack
                    $output->writeln("Unpacking <info>$source</info>...");
                    $zip = new \ZipArchive();
                    $errorUnpack = false;

                    if ($zip->open($localArchiveFile)) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            if (!($zip->extractTo($localDownloadPath, array($zip->getNameIndex($i))))) {
                                $errorUnpack = true;
                            }
                        }
                        $zip->close();
                    }

                    if ($errorUnpack){
                        $output->writeln("Failed unzipping <info>$source</info>");
                    } else {
                        $output->writeln("Plugin downloaded to <info>$localDownloadPath</info>");
                    }

                    // remove zip archive
                    $this->delDirTree($localUnpackPath);

                    break;

                case 'git':
                    $output->writeln("Downloading <info>$source</info>...");
                    $repo = GitRepository::cloneRepository($source, $localDownloadPath);
                    $output->writeln("Plugin downloaded to <info>$localDownloadPath</info>");
                    break;

            }

        } else {

            $output->writeln("The source <info>$source</info> is not supported. Only .zip and .git are supported.");
        }

        return 0;
    }
}
