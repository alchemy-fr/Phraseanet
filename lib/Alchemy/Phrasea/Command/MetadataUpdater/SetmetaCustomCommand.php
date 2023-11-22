<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Alchemy\Phrasea\Command\MetadataUpdater;

use \appbox;
use Alchemy\Phrasea\Command\Command;
use Monolog\Logger;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Reader;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetmetaCustomCommand extends Command
{
    public function configure()
    {
        $this->setName("metadatas:set")
            ->setDescription('Read metadata from file and add it to field')
            ->addOption('dry', null, InputOption::VALUE_NONE, "Dry run (list alert only and list record and meta values).")
            ->addOption('max-rid', null, InputOption::VALUE_REQUIRED, "highest record_id value")
            ->addOption('min-rid', null, InputOption::VALUE_REQUIRED, "lowest record_id value")
            ->addOption('metadata-tag', null, InputOption::VALUE_REQUIRED, "tag to search exemple IPTC:KEYWORD")
            ->addOption('meta-struct-id', null, InputOption::VALUE_REQUIRED, "ID of the field de fill")
            ->addOption('sbas-id', null, InputOption::VALUE_REQUIRED, "Id of a database to work on")
            ->addOption('subdef', null, InputOption::VALUE_REQUIRED, "name of the definition to read")
            // ->setHelp('')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var appbox $appbox */
        $appbox = $this->container['phraseanet.appbox'];

        $metadataTag = $input->getOption('metadata-tag');

        if ($metadataTag != NULL) {
            $meta_struct_id = $input->getOption("meta-struct-id");
        } else {
            $output->writeln("meta_struct_id is mandatory");

            return 0;
        }

        if (empty($metadataTag)) {
            $output->writeln("metadata-tag is mandatory");

            return 0;
        }

        $record_max = $input->getOption("max-rid");
        $record_min = $input->getOption("min-rid");

        if ($input->getOption("sbas-id") != NULL) {
            try {
                $databoxes = [$appbox->get_databox($input->getOption("sbas-id"))];
            } catch (\Exception $e) {
                $databoxes = [];
            }
        } else {
            $databoxes = $appbox->get_databoxes();
        }


        foreach ($databoxes as $databox) {
            $sbasId = $databox->get_sbas_id();
            $sbasName = $databox->get_dbname();

            $output->writeln(sprintf("<comment>Working on database %s with Id : %d</comment>", $sbasName, $sbasId));

            $clauses[]= '1';
            if ($record_min != NULL) {
                $clauses[] = "record_id >= " . $record_min;
            }

            if ($record_max != NULL) {
                $clauses[] = "record_id <= " . $record_max;
            }

            $sql_where = join(" AND ", $clauses);

            $sql = "SELECT * FROM record WHERE " . $sql_where;

            $stmt = $databox->get_connection()->prepare($sql);

            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $record = $databox->get_record($row['record_id']);

                try {
                    $documentSubdef = $record->get_subdef('document');
                } catch (\Exception $e) {
                    $output->writeln(sprintf("<error>Document not found for the record %d on database %s! </error>", $row['record_id'], $sbasName));
                    continue;
                }


                if ($documentSubdef->is_physically_present()) {
                    $output->writeln(sprintf("<info>Searching for data %s into the record %d on database %s </info>", $metadataTag, $row['record_id'], $sbasName));
                    try {
                        $results = $this->getMeta($documentSubdef->getRealPath(), $metadataTag);
                    } catch (\Exception $e) {
                        $output->writeln("<error>" . $e->getMessage() . "</error>");
                        $results = [];
                    }
                } else {
                    $output->writeln(sprintf("<warning>Document not found for the record %d on database %s! </warning>", $row['record_id'], $sbasName));
                    $results = [];
                }

                if ($results != NULL) {
                  $metadatas = [];
                  foreach ($results as $result) {
                    $metadatas[] = [
                        'action'         => 'set',
                        'meta_struct_id' => $meta_struct_id,
                        'meta_id'        => null,
                        'value'          => $result
                    ];
                  }

                  $actions['metadatas'] = $metadatas;

                  $record->setMetadatasByActions(json_decode(json_encode($actions)));
                }
            }
        }
    }

    private function getMeta($file,$tag )
    {
        $logger = new Logger('exif-tool');
        $reader = Reader::create($logger);

        $metadatas = $reader->files($file)->first();

        $value = NULL;

        /** @var Metadata $metadata */
        foreach ($metadatas as $metadata) {
            if ($metadata->getTag() == $tag) {
                $value = explode(";", $metadata->getValue());
                break;
            }
        }

        return $value;
    }
}
