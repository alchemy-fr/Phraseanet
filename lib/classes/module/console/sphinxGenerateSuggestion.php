<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class module_console_sphinxGenerateSuggestion extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Generate suggestions for Sphinx Search Engine');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        define('FREQ_THRESHOLD', 10);
        define('SUGGEST_DEBUG', 0);

        $params = phrasea::sbas_params($this->container);

        foreach ($params as $sbas_id => $p) {
            $index = sprintf("%u", crc32(
                str_replace(
                    array('.', '%')
                    , '_'
                    , sprintf('%s_%s_%s_%s', $p['host'], $p['port'], $p['user'], $p['dbname'])
                )
            ));

            $tmp_file = $this->container['phraseanet.registry']->get('GV_RootPath') . 'tmp/dict' . $index . '.txt';

            $databox = $this->getService('phraseanet.appbox')->get_databox($sbas_id);

            $output->writeln("process Databox " . $databox->get_label($this->container['locale.I18n']) . " / $index\n");

            if ( ! is_executable("/usr/local/bin/indexer")) {
                $output->writeln("<error>'/usr/local/bin/indexer' is not executable</error>");

                return 1;
            }

            $builder = ProcessBuilder::create(array('/usr/local/bin/indexer'));
            $builder->add('metadatas' . $index)
                ->add('--buildstops')
                ->add($tmp_file)
                ->add(1000000)
                ->add('--buildfreqs');

            $builder->getProcess()->run();

            if ( ! file_exists($tmp_file)) {
                $output->writeln("<error> file '" . $tmp_file . "' does not exist</error>");

                return 1;
            }

            try {
                $connbas = connection::getPDOConnection($this->container, $sbas_id);
            } catch (Exception $e) {
                continue;
            }

            $sql = 'TRUNCATE suggest';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $sql = $this->BuildDictionarySQL($output, file_get_contents($tmp_file));

            if (trim($sql) !== '') {
                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
            }

            unlink($tmp_file);
        }

        return 0;
    }

    protected function BuildTrigrams($keyword)
    {
        $t = "__" . $keyword . "__";

        $trigrams = "";
        for ($i = 0; $i < strlen($t) - 2; $i ++ )
            $trigrams .= substr($t, $i, 3) . " ";

        return $trigrams;
    }

    protected function BuildDictionarySQL(OutputInterface $output, $in)
    {
        $out = '';

        $n = 0;
        $lines = explode("\n", $in);
        foreach ($lines as $line) {
            if (trim($line) === '')
                continue;
            list ( $keyword, $freq ) = explode(" ", trim($line));

            if ($freq < FREQ_THRESHOLD || strstr($keyword, "_") !== false || strstr($keyword, "'") !== false)
                continue;

            if (ctype_digit($keyword)) {
                continue;
            }
            if (mb_strlen($keyword) < 3) {
                continue;
            }

            $trigrams = $this->BuildTrigrams($keyword);

            if ($n ++)
                $out .= ",\n";
            $out .= "( $n, '$keyword', '$trigrams', $freq )";
        }

        if (trim($out) !== '') {
            $out = "INSERT INTO suggest VALUES " . $out . ";";
        }

        $output->writeln(sprintf("Generated <info>%d</info> suggestions", $n));

        return $out;
    }
}
