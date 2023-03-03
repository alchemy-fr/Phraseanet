<?php

namespace Alchemy\Phrasea\Command\Report;

use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Notification\Attachment;
use Alchemy\Phrasea\Notification\Mail\MailReportConnections;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Report\Report;
use Alchemy\Phrasea\Report\ReportConnections;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConnectionsCommand extends Command
{
    use NotifierAware;

    public function __construct()
    {
        parent::__construct('connections:all');

        $this
            ->setDescription('Get all connections report')
            ->addOption('databox_id', null, InputOption::VALUE_REQUIRED,                             'the databox to get report')
            ->addOption('email', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY ,'email to send the report')
            ->addOption('dmin', null, InputOption::VALUE_REQUIRED, 'minimum date')
            ->addOption('dmax', null, InputOption::VALUE_REQUIRED, 'maximum date')

            ->setHelp(
                ""
            );
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->setDelivererLocator(new LazyLocator($this->container, 'notification.deliverer'));

        $sbasId = $input->getOption('databox_id');
        $emails = $input->getOption('email');
        $dmin = $input->getOption('dmin');
        $dmax = $input->getOption('dmax');

        if (empty($emails)) {
            $output->writeln("<error>set '--email' option</error>");

            return 1;
        }

        if (!$this->isDateOk($dmin)) {
            $output->writeln("<error>invalid value from '--dmin' option</error>");

            return 1;
        }

        if (!$this->isDateOk($dmax)) {
            $output->writeln("<error>invalid value from '--dmax' option</error>");

            return 1;
        }

        $report = (new ReportConnections(
            $this->findDbOr404($sbasId),
            [
                'dmin'      => $dmin,
                'dmax'      => $dmax,
                'group'     => '',
                'anonymize' => $this->container['conf']->get(['registry', 'modules', 'anonymous-report'])
            ]
        ))
            ->setAppKey($this->container['conf']->get(['main', 'key']));

        $report->setFormat(Report::FORMAT_CSV);

        $absoluteDirectoryPath = \p4string::addEndSlash($this->container['tmp.download.path'])
            .'report' . DIRECTORY_SEPARATOR
            . date('Ymd'). 'Sbas' . $sbasId;

        $report->render($absoluteDirectoryPath);
        $filePath = $absoluteDirectoryPath . DIRECTORY_SEPARATOR . $report->getName() . '.csv';

        $attachement = new Attachment($filePath);

        foreach ($emails as $email) {
            $receiver = new Receiver('', $email);
            $mail = MailReportConnections::create($this->container, $receiver);

            $this->deliver($mail, false, [$attachement]);
        }

        $output->writeln("<info>finish !</info>");

        return 0;
    }

    /**
     * @param int $sbasId
     * @return \databox
     */
    private function findDbOr404($sbasId)
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
}
