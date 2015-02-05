<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\SQLParserUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildSubdefs extends Command
{
    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Build subviews for given subview names and record types');
        $this->addArgument('databox', InputArgument::REQUIRED, 'The databox id');
        $this->addArgument('type', InputArgument::REQUIRED, 'Types of the document to rebuild');
        $this->addArgument('subdefs', InputArgument::REQUIRED, 'Names of sub-definition to re-build');
        $this->addOption('max_record', 'max', InputOption::VALUE_OPTIONAL, 'Max record id');
        $this->addOption('min_record', 'min', InputOption::VALUE_OPTIONAL, 'Min record id');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $availableTypes = array('document', 'audio', 'video', 'image', 'flash', 'map');

        $typesOption =  $input->getArgument('type');

        $recordsType = explode(',', $typesOption);
        $recordsType = array_filter($recordsType, function($type) use($availableTypes) {
            return in_array($type, $availableTypes);
        });

        if (count($recordsType) === 0) {
            $output->write(sprintf('Invalid records type provided %s', implode(', ', $availableTypes)));
            return;
        }

        $subdefsOption = $input->getArgument('subdefs');
        $subdefsName = explode(',', $subdefsOption);

        if (count($subdefsOption) === 0) {
            $output->write('No subdef options provided');
            return;
        }

        $sqlCount = "
            SELECT COUNT(DISTINCT(r.record_id)) AS nb_records
            FROM record r
            INNER JOIN subdef s
            ON (r.record_id = s.record_id)
            WHERE s.name IN (?)
            AND r.type IN (?)
        ";

        $types = array(Connection::PARAM_STR_ARRAY, Connection::PARAM_STR_ARRAY);
        $params = array($subdefsName, $recordsType);

        if (null !== $min = $input->getOption('min_record')) {
            $sqlCount .= " AND (r.record_id >= ?)";

            $params[] = (int) $min;
            $types[] = \PDO::PARAM_INT;
        }
        if (null !== $max = $input->getOption('max_record')) {
            $sqlCount .= " AND (r.record_id <= ?)";

            $params[] = (int) $max;
            $types[] = \PDO::PARAM_INT;
        }

        list($sqlCount, $stmtParams) = SQLParserUtils::expandListParameters($sqlCount, $params, $types);

        $totalRecords = 0;
        foreach ($this->container['phraseanet.appbox']->get_databoxes() as $databox) {
            $connection = $databox->get_connection();
            $stmt = $connection->prepare($sqlCount);
            $stmt->execute($stmtParams);
            $row = $stmt->fetch();
            $totalRecords += $row['nb_records'];
        }

        if ($totalRecords === 0) {
            return;
        }

        $progress = $this->getHelperSet()->get('progress');

        $progress->start($output, $totalRecords);

        $progress->display();

        $databox = $this->container['phraseanet.appbox']->get_databox($input->getArgument('databox'));

        $sql = "
            SELECT DISTINCT(r.record_id)
            FROM record r
            INNER JOIN subdef s
            ON (r.record_id = s.record_id)
            WHERE s.name IN (?)
            AND r.type IN (?)
        ";

        $types = array(Connection::PARAM_STR_ARRAY, Connection::PARAM_STR_ARRAY);
        $params = array($subdefsName, $recordsType);

        if ($min) {
            $sql .= " AND (r.record_id >= ?)";

            $params[] = (int) $min;
            $types[] = \PDO::PARAM_INT;
        }
        if ($max) {
            $sql .= " AND (r.record_id <= ?)";

            $params[] = (int) $max;
            $types[] = \PDO::PARAM_INT;
        }

        list($sql, $stmtParams) = SQLParserUtils::expandListParameters($sql, $params, $types);

        $connection = $databox->get_connection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($stmtParams);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $output->write(sprintf(' (#%s)', $row['record_id']));

            $record = new \record_adapter($this->container, $databox->get_sbas_id(), $row['record_id']);

            $subdefs = array_filter($record->get_subdefs(), function($subdef) use ($subdefsName) {
                return in_array($subdef->get_name(), $subdefsName);
            });

            foreach ($subdefs as $subdef) {
                $subdef->remove_file();
            }

            $record->generate_subdefs($databox, $this->container, $subdefsName);

            $stmt->closeCursor();

            $progress->advance();
        }

        unset($rows, $record, $stmt, $connection);

        $progress->finish();
    }
}
