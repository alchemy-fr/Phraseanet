<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Alchemy\Phrasea\Command\MetadataUpdater;

use \appbox;
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class SetmetaCustomCommand extends Command
{
    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;
    /** @var  appbox $appbox */
    private $appbox;
    /** @var array $databoxes */
    private $databoxes;

    
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
        $this->input = $input;
        // add cool styles
        $style = new OutputFormatterStyle('black', 'yellow'); //array('bold'));
        $output->getFormatter()->setStyle('warning', $style);
        $this->output = $output;
        
        $this->appbox = $this->container['phraseanet.appbox'];
        $this->databox = null;
        $databoxes = NULL;

        if (empty($input->getOption('metadata-tag'))) {
            $output->writeln("metadata-tag is mandatory");

            return 0;
        }


        foreach ($this->appbox->get_databoxes() as $databox) {
            $this->databox = $databox;

            if ($input->getOption("sbas-id") != NULL) {
                $databox = $this->appbox->get_databox($input->getOption("sbas-id"));
            }

            $sbas_id = $databox->get_sbas_id();
            $sbas_name = $databox->get_dbname();
            echo "Working ON ".$sbas_name." with Id ".$sbas_id;
            $this->connection = $databox->get_connection();
            $meta = new MetaManager();
            $sql = "SELECT * FROM record";
            $record_max = $input->getOption("max-rid");
            $record_min = $input->getOption("min-rid");

            if($record_min != NULL) {
                $sql .=" WHERE record_id>=".$record_min;
            }

            if($record_max != NULL) {
                $sql .= " AND record_id<=".$record_max;
            }

            $exec = $databox->get_connection()->executeQuery($sql);

            while ($row=$exec->fetch(\PDO::FETCH_ASSOC)) {

                $record = $this->databox->get_record($row['record_id']);

                /** @var media_subdef $subdef */

                if($input->getOption("meta-struct-id") != NULL) {
                    $meta_struct_id = $input->getOption("meta-struct-id");
                } else {
                    echo "meta_struct_id is mandatory";
                    break;
                }

                foreach ($record->get_subdefs() as $subdef) {
                    $name = $subdef->get_name();
                    $path = $subdef->get_path($name);
                    $filepath  = $subdef->get_file($name);
                    $file = $path.$filepath;
                    $this->metadatatag = $input->getOption('metadata-tag');
                    $subdef = ($input->getOption('subdef')==NULL ? "document" : $input->getOption('subdef'));

                    if ($name==$subdef) {
                        echo "Searching for data into: ".$this->metadatatag." into ".$name."\n";
                        $results = $meta->get_meta($file, $this->metadatatag);
                        // var_dump($results);
                    }
                }

                if ($results != NULL) {

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
}      
