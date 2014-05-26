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
use Alchemy\Phrasea\Http\StaticFile\StaticFileFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class StaticSymLinkGenerator extends Command
{
    public function __construct($name = null)
    {
        parent::__construct('static-file:generate-symlink');

        $this
             ->setDescription('Generates Phraseanet Static file symlinks');
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container['phraseanet.static-file-factory']->isStaticFileModeEnabled()) {
            $output->writeln('Static file support is <error>disabled</error>');

            return 1;
        }

        $output->writeln("Removing symlinks ...");
        $this->container['filesystem']->remove($this->container['phraseanet.thumb-symlinker']->getPublicDir());
        $total = 0;
        foreach ($this->container['phraseanet.appbox']->get_databoxes() as $databox) {
            $sql = 'SELECT count(subdef_id) as total FROM subdef WHERE `name`="thumbnail"';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $total += $row['total'];
        }
        $output->writeln("Creating symlinks ...");
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, $total);
        $i = 0;
        do {
            foreach ($this->container['phraseanet.appbox']->get_databoxes() as $databox) {
                $sql = 'SELECT record_id FROM record';
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rows as $row) {
                    $record = $databox->get_record($row['record_id']);
                    foreach ($record->get_subdefs() as $subdef) {
                        if ($subdef->get_name() !== 'thumbnail') {
                            continue;
                        }
                        $this->container['phraseanet.thumb-symlinker']->symlink($subdef->get_pathfile());
                        $progress->advance();
                        $i++;
                    }
                }
            }
        } while ($i < $total);

        $progress->finish();

        $output->writeln("<info>OK</info>");

        return 0;
    }
}
