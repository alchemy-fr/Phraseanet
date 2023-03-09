<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Notification\Attachment;
use Alchemy\Phrasea\Notification\Mail\MailReportConnections;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Report\Report;
use Cocur\Slugify\Slugify;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractReportCommand extends Command
{
    use NotifierAware;

    protected $sbasId;
    protected $emails;
    protected $dmin;
    protected $dmax;
    protected $type;

    protected $isAppboxConnection = false;

    public function __construct($name)
    {
        parent::__construct($name);

        $this
            ->addOption('databox_id', null, InputOption::VALUE_REQUIRED,                             'the application databox')
            ->addOption('email', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY ,'emails to send the report')
            ->addOption('dmin', null, InputOption::VALUE_REQUIRED, 'minimum date yyyy-mm-dd')
            ->addOption('dmax', null, InputOption::VALUE_REQUIRED, 'maximum date yyyy-mm-dd, until today if not set');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));

        $this->sbasId = $input->getOption('databox_id');
        $this->emails = $input->getOption('email');
        $this->dmin = $input->getOption('dmin');
        $this->dmax = $input->getOption('dmax');

        if (empty($this->emails)) {
            $output->writeln("<error>set '--email' option</error>");

            return 1;
        }

        if (!$this->isDateOk($this->dmin)) {
            $output->writeln("<error>invalid value from '--dmin' option</error>");

            return 1;
        }

        if (!empty($this->dmax) && !$this->isDateOk($this->dmax)) {
            $output->writeln("<error>invalid value from '--dmax' option</error>");

            return 1;
        }

        $report = $this->getReport($input, $output);

        if (!$report instanceof Report) {
            return 1;
        }

        $report->setFormat(Report::FORMAT_CSV);

        $absoluteDirectoryPath = \p4string::addEndSlash($this->container['tmp.download.path'])
            .'report' . DIRECTORY_SEPARATOR
            . date('Ymd');

        if ($this->isAppboxConnection) {
            $absoluteDirectoryPath .= 'appbox';
        } else {
            $absoluteDirectoryPath .= 'Sbas' . $this->sbasId;
        }

        $report->render($absoluteDirectoryPath);

        $filePath = $absoluteDirectoryPath . DIRECTORY_SEPARATOR . $this->normalizeString($report->getName()) . '.csv';

        $attachement = new Attachment($filePath);

        foreach ($this->emails as $email) {
            $receiver = new Receiver('', $email);
            $mail = MailReportConnections::create($this->container, $receiver);

            $this->deliver($mail, false, [$attachement]);
        }

        $output->writeln("<info>finish !</info>");

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Report
     */
    abstract protected function getReport(InputInterface $input, OutputInterface $output);

    /**
     * @param int $sbasId
     * @return \databox
     */
    protected function findDbOr404($sbasId)
    {
        $db = $this->container->getApplicationBox()->get_databox(($sbasId));
        if(!$db) {
            throw new NotFoundHttpException(sprintf('Databox %s not found', $sbasId));
        }

        return $db;
    }

    private function isDateOk($date)
    {
        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})/i", $date, $matches);
        $n = count($matches);
        if ($n === 2) {
            return true;
        }

        return false;
    }

    private function normalizeString($filename)
    {
        return (new Slugify())->slugify($filename, '-');
    }
}
