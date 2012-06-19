<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Rescan Technical Datas command : Rescan all records of all databases and
 * rescan technical datas.
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class RescanTechnicalDatas extends Command
{
    /**
     * The average speed (record/sec), used to warn about duration
     */
    const AVG_SPEED = 2.8;

    protected $appbox;

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Rescan databases for technical datas');
        $this->setHelp('Old Phraseanet version did not fully read technical datas. This command rescan all records of these datas.');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function requireSetup()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkSetup();

        $this->appbox = \appbox::get_instance(\bootstrap::getCore());

        $quantity = $this->computeQuantity();
        $duration = $this->getFormattedDuration($quantity);

        $dialog = $this->getHelperSet()->get('dialog');
        do {
            $continue = mb_strtolower($dialog->ask($output, sprintf('Estimated duration is %s, <question>continue ? (y/N)</question>', $duration), 'N'));
        } while ( ! in_array($continue, array('y', 'n')));

        if (strtolower($continue) !== 'y') {
            $output->writeln('Aborting !');

            return;
        }

        $start = microtime(true);
        $n = 0;

        foreach ($this->appbox->get_databoxes() as $databox) {

            $sql = 'SELECT record_id FROM record WHERE parent_record_id = 0';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                $record = $databox->get_record($row['record_id']);
                $record->insertTechnicalDatas();
                unset($record);
                $output->write("\r" . $n . " records done");
                $n ++;
            }
        }

        $output->writeln("\n");

        $stop = microtime(true);
        $duration = $stop - $start;

        $output->writeln(sprintf("process took %s, (%f rec/s.)", $this->getFormattedDuration($duration), round($quantity / $duration, 3)));

        return;
    }

    /**
     * Format a duration in seconds to human readable
     *
     * @param  type   $seconds the time to format
     * @return string
     */
    public function getFormattedDuration($seconds)
    {
        $duration = round($seconds / (60 * self::AVG_SPEED)) . ' minutes';

        if ($duration > 60) {
            $duration = round($duration / (60 * self::AVG_SPEED), 1) . ' hours';
        }
        if ($duration > 24) {
            $duration = round($duration / (24 * self::AVG_SPEED), 1) . ' days';
        }

        return $duration;
    }

    /**
     * Return the total quantity of records to process
     *
     * @return integer
     */
    protected function computeQuantity()
    {
        $n = 0;

        foreach ($this->appbox->get_databoxes() as $databox) {
            $n += $databox->get_record_amount();
        }

        return $n;
    }
}
