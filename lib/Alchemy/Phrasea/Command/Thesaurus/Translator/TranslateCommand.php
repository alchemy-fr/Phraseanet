<?php

namespace Alchemy\Phrasea\Command\Thesaurus\Translator;


use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager as BorderManager;
use Alchemy\Phrasea\Command\Command as phrCommand;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use collection;
use databox;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Exception;
use Guzzle\Http\Client as Guzzle;
use igorw;
use MediaVorus\MediaVorus;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use PDO;
use record_adapter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;


/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class TranslateCommand extends phrCommand
{
    /** @var InputInterface $input */
    private $input;
    /** @var OutputInterface $output */
    private $output;

    /** @var GlobalConfiguration */
    private $config;

    public function configure()
    {
        $this->setName('thesaurus:translate')
            ->setDescription('Translate fields values using thesaurus')
            ->addOption('dry',    null,  InputOption::VALUE_NONE, "list translations but don't apply.", null)
        ;
    }

    /**
     * @param  $input
     * @param  $output
     * @return int
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        // add cool styles
        $style = new OutputFormatterStyle('black', 'yellow'); // , array('bold'));
        $output->getFormatter()->setStyle('warning', $style);

        $this->input = $input;
        $this->output = $output;

        // config must be ok
        //
        try {
            $this->config = GlobalConfiguration::create(
                $this->container['phraseanet.appbox'],
                $this->container['unicode'],
                $this->container['root.path'],
                $input->getOption('dry'),
                $output
            );
        }
        catch(\Exception $e) {
            $output->writeln(sprintf("<error>missing or bad configuration: %s</error>", $e->getMessage()));

            return -1;
        }

        /**
         * @var string $jobName
         * @var Job $job
         */
        foreach ($this->config->getJobs() as $jobName => $job) {
            $output->writeln("");
            $output->writeln(sprintf("======== Playing job %s ========", $jobName));

            if(!$job->isValid()) {
                $output->writeln("<warning>Configuration error(s)... :</warning>");
                foreach ($job->getErrors() as $err) {
                    $output->writeln(sprintf(" - %s", $err));
                }
                $output->writeln("<warning>...Job ignored</warning>");

                continue;
            }

            if(!$job->isActive()) {
                $output->writeln(sprintf("job is inactive, skipped."));
                continue;
            }

            $job->run();
        }

        return 0;
    }

}
