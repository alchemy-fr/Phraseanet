<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;

class module_console_fieldsRename extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Renames a documentation field from a Databox');

        $this->addArgument('name', InputArgument::REQUIRED, 'The new name');
        $this->addArgument('meta_struct_id', InputArgument::REQUIRED, 'Metadata structure id destination');
        $this->addArgument('sbas_id', InputArgument::REQUIRED, 'Databox sbas_id');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $new_name = $input->getArgument('name');

        try {
            /** @var databox $databox */
            $databox = $this->getService('phraseanet.appbox')->get_databox((int) $input->getArgument('sbas_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid databox id </error>");

            return 1;
        }

        try {
            $field = $databox
                ->get_meta_structure()
                ->get_element((int) $input->getArgument('meta_struct_id'));
        } catch (\Exception $e) {
            $output->writeln("<error>Invalid meta struct id </error>");

            return 1;
        }

        $dialog = $this->getHelperSet()->get('dialog');
        $continue = mb_strtolower(
            $dialog->ask(
                $output
                , "<question>About to rename " . $field->get_name() . " into " . $new_name . " (y/N)</question>"
                , 'n'
            )
        );

        if ($continue != 'y') {
            $output->writeln("Request canceled by user");

            return 1;
        }

        $output->write("Renaming ... ");

        $field->set_name($new_name);
        $field->save();

        $output->writeln("<info>OK</info>");

        $sql = 'SELECT count(record_id) as total FROM record';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $total = $data['total'];
        $start = 0;
        $quantity = 100;

        $builder = $databox->get_connection()->createQueryBuilder();
        $builder
            ->select('r.record_id')
            ->from('record', 'r')
            ->orderBy('r.record_id', 'ASC')
            ->setFirstResult($start)
            ->setMaxResults($quantity)
        ;
        do {
            $output->write("\rUpdating records... <info>".min($start, $total)." / $total</info>");

            $stmt = $builder->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($results as $row) {
                $record = $databox->get_record($row['record_id']);
                $record->set_metadatas([]);
                unset($record);
            }

            // order to write metas for those records
            $this->container['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent(array_column($results, 'record_id'), $input->getArgument('sbas_id'))
            );

            $start += $quantity;
        } while (count($results) > 0);

        $output->writeln("\nDone with success !");

        return 0;
    }
}
