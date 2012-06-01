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
 * Rebuild only missing subdefs
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class BuildMissingSubdefs extends Command
{
    protected $appbox;

    /**
     * Constructor
     */
    public function __construct($name = null)
    {
        parent::__construct($name);


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

        $core = \bootstrap::getCore();
        $this->appbox = \appbox::get_instance($core);

        $start = microtime(true);
        $n = 0;

        $logger = $core['monolog'];

        foreach ($this->appbox->get_databoxes() as $databox) {

            $subdefStructure = $databox->get_subdef_structure();

            $sql = 'SELECT record_id FROM record WHERE parent_record_id = 0';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($rs as $row) {
                $record = $databox->get_record($row['record_id']);

                $group = $subdefStructure->getSubdefGroup($record->get_type());

                if ($group) {
                    foreach ($group as $subdef) {
                        if ( ! $record->has_subdef($subdef->get_name())) {
                            $record->generate_subdefs($databox, $logger, array($subdef->get_name()));
                            $output->writeln("generate " . $subdef->get_name() . " for record " . $record->get_record_id());
                            $n ++;
                        }
                    }
                }

                unset($record);
            }
        }

        $output->writeln($n . " subdefs done");
        $stop = microtime(true);
        $duration = $stop - $start;

        $output->writeln(sprintf("process took %s, (%f sd/s.)", $this->getFormattedDuration($duration), round($n / $duration, 3)));

        return;
    }

    /**
     * Format a duration in seconds to human readable
     *
     * @param type $seconds the time to format
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
}
