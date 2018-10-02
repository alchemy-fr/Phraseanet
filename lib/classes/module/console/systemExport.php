<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemExport extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Exports all phraseanet records to a given directory');

        /**
         * To implement
         */
//    $this->addOption('useoriginalname', 'o', InputOption::VALUE_OPTIONAL
//      , 'Use original name for dest files', false);

        /**
         * To implement
         */
//    $this->addOption('excludefield', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
//      , 'Exclude field from XML', []);

        /**
         * To implement
         */
//    $this->addOption('excludestatus', '', InputOption::VALUE_OPTIONAL
//      , 'Exclude Status', false);

        $this->addOption('docperdir', 'd', InputOption::VALUE_OPTIONAL
            , 'Maximum number of files per dir', 100);

        $this->addOption('caption', 'c', InputOption::VALUE_OPTIONAL
            , 'Export Caption (XML)', false);

        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL
            , 'Limit files quantity (for test purposes)', false);

        $this->addOption('base_id', 'b', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
            , 'Restrict on base_ids', []);

        $this->addOption('sbas_id', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY
            , 'Restrict on sbas_ids', []);

        $this->addArgument('directory', InputOption::VALUE_REQUIRED
            , 'The directory where to export');

        $this->addOption('sanitize', '', InputOption::VALUE_REQUIRED
            , 'Sanitize filenames. Set to 0 to disable', true);

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $docPerDir = max(1, (int) $input->getOption('docperdir'));

        /**
         *
         * To implement
         *
          $useOriginalName = !!$input->getOption('useoriginalname');
          $excludeFields = $input->getOption('excludefield');
          $exportStatus = !$input->getOption('excludestatus');
         *
         */
        $Caption = $input->getOption('caption');

        $limit = ctype_digit($input->getOption('limit')) ? max(0, (int) $input->getOption('limit')) : false;

        $restrictBaseIds = $input->getOption('base_id');

        $restrictSbasIds = $input->getOption('sbas_id');

        $sanitize = $input->getOption('sanitize');

        $directory = $input->getArgument('directory');

        $export_directory = realpath(substr($directory, 0, 1) === '/' ? $directory : getcwd() . '/' . $directory . '/');

        if (! $export_directory) {
            throw new Exception('Export directory does not exists or is not accessible');
        }

        if ( ! is_writable($export_directory)) {
            throw new Exception('Export directory is not writable');
        }

        /**
         * Sanitize
         */
        foreach ($restrictBaseIds as $key => $base_id) {
            $restrictBaseIds[$key] = (int) $base_id;
        }

        foreach ($restrictSbasIds as $key => $sbas_id) {
            $restrictSbasIds[$key] = (int) $sbas_id;
        }

        if (count($restrictSbasIds) > 0) {
            $output->writeln("Export datas from selected sbas_ids");
        } elseif (count($restrictBaseIds) > 0) {
            $output->writeln("Export datas from selected base_ids");
        }

        $total = $errors = 0;

        /** @var databox $databox */
        foreach ($this->getService('phraseanet.appbox')->get_databoxes() as $databox) {
            $output->writeln(sprintf("Processing <info>%s</info>", $databox->get_label($this->container['locale'])));

            if (count($restrictSbasIds) > 0 && ! in_array($databox->get_sbas_id(), $restrictSbasIds)) {
                $output->writeln(sprintf("Databox not selected, bypassing ..."));
                continue;
            }

            $go = true;
            $coll_ids = [];

            if (count($restrictBaseIds) > 0) {
                $go = false;
                foreach ($databox->get_collections() as $collection) {
                    if (in_array($collection->get_base_id(), $restrictBaseIds)) {

                        $go = true;
                        $coll_ids[] = $collection->get_coll_id();
                    }
                }
            }

            if (! $go) {
                $output->writeln(sprintf("Collections not selected, bypassing ..."));
                continue;
            }

            $local_export = $export_directory
                . '/' . $this->container['unicode']->remove_nonazAZ09($databox->get_viewname(), true, true)
                . '/';

            $this->getService('filesystem')->mkdir($local_export);

            $builder = $databox->get_connection()->createQueryBuilder();
            $builder
                ->select('r.record_id')
                ->from('record', 'r')
                ->where($builder->expr()->eq('r.parent_record_id', $builder->expr()->literal(0)))
            ;

            if (count($coll_ids) > 0) {
                $builder
                    ->andWhere($builder->expr()->in('r.coll_id', [':collIds']))
                    ->setParameter('collIds', $coll_ids, Connection::PARAM_INT_ARRAY)
                ;
            }

            $builder->orderBy('r.record_id', 'ASC');

            if ($limit) {
                $builder->setMaxResults($limit);
            }

            $stmt = $builder->execute();
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $done = 0;
            $current_total = count($rs);
            $total += $current_total;
            $l = strlen((string) $current_total) + 1;

            $dir_format = 'datas%' . strlen((string) ceil($current_total / $docPerDir)) . 'd';

            $dir_increment = 0;
            foreach ($rs as $row) {
                $record = $databox->get_record($row['record_id']);
                if (($done % $docPerDir) === 0) {
                    $dir_increment ++;
                    $in_dir_files = [];
                    $current_dir = $local_export . sprintf($dir_format, $dir_increment) . '/';
                    $this->getService('filesystem')->mkdir($current_dir);
                }

                if ($sanitize) {
                    $filename = $this->container['unicode']->remove_nonazAZ09($record->get_original_name(), true, true, true);
                } else {
                    $filename = $record->get_original_name();
                }

                $this->generateDefinitiveFilename($in_dir_files, $filename);

                $output_file = $current_dir . $filename;

                if ( ! $this->processRecords($record, $output_file, $Caption)) {
                    $errors ++;
                }

                $done ++;

                $output->write(sprintf("\r#%" . $l . "d record remaining", $current_total - $done));
            }
            $output->writeln(" | " . $current_total . " records done\n");
        }

        $output->writeln("$total records done, $errors errors occured");

        return 0;
    }

    protected function generateDefinitiveFilename(array &$existing, &$filename)
    {
        $definitive_filename = $filename;
        $suffix = 2;
        while (array_key_exists($definitive_filename, $existing)) {
            $pathinfo = pathinfo($filename);

            $definitive_filename = $pathinfo['filename'] . '_' . $suffix .
                (isset($pathinfo['extension']) ? '.' . $pathinfo['extension'] : '');
            $suffix ++;
        }

        $existing[$filename] = $filename;
        $filename = $definitive_filename;

        return;
    }

    protected function processRecords(\record_adapter $record, $outfile, $caption)
    {
        if ( ! file_exists($record->get_subdef('document')->getRealPath())) {
            return false;
        }

        $this->getService('filesystem')
            ->copy($record->get_subdef('document')->getRealPath(), $outfile);

        $dest_file = new \SplFileInfo($outfile);

        touch(
            $dest_file->getPathname(),
            $record->getCreated()->format('U'),
            $record->getUpdated()->format('U')
        );

        switch (strtolower($caption)) {
            case 'xml':
                $pathinfo = pathinfo($dest_file->getPathname());
                $xml = $this->container['serializer.caption']->serialize($record->get_caption(), CaptionSerializer::SERIALIZE_XML);
                $xml_file = dirname($outfile) . '/' . $pathinfo['filename'] . '.xml';
                file_put_contents($xml_file, $xml);
                break;
            default:
                break;
        }

        return true;
    }
}
