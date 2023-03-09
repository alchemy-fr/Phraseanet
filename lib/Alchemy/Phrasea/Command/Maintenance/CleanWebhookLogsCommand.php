<?php

namespace Alchemy\Phrasea\Command\Maintenance;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanWebhookLogsCommand extends Command
{
    public function __construct()
    {
        parent::__construct('clean:webhooklog');

        $this
            ->setDescription('BETA - Clean the webhook log')
            ->addOption('older_than', null, InputOption::VALUE_REQUIRED, 'delete older than <OLDER_THAN>')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'dry run, list and count')
            ->addOption('show_sql', null, InputOption::VALUE_NONE, 'show sql pre-selecting WebhookEvents')

            ->setHelp(
                "example: <info>bin/maintenance clean:webhooklog --dry-run --older_than '10 month'</info>\n"
                . "\<OLDER_THAN> can be absolute or relative from now, e.g.:\n"
                . "- <info>2022-01-01</info>\n"
                . "- <info>10 days</info>\n"
                . "- <info>2 weeks</info>\n"
                . "- <info>6 months</info>\n"
                . "- <info>1 year</info>\n"
            );
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dry = false;

        $older_than = str_replace(['-', '/', ' '], '-', $input->getOption('older_than'));
        if($older_than === "") {
            $output->writeln("<error>set '--older_than' option</error>");

            return 1;
        }

        $matches = [];
        preg_match("/(\d{4}-\d{2}-\d{2})|(\d+)-(day|week|month|year)s?/i", $older_than, $matches);
        $n = count($matches);
        if ($n === 2) {
            // yyyy-mm-dd
            $clauseWhere = "`created` < '" . $matches[1] ."'";
        } elseif ($n === 4 && empty($matches[1])) {
            // 1-day ; 2-weeks ; ...
            $expr = (int)$matches[2];
            $unit = strtoupper($matches[3]);
            $clauseWhere = sprintf("`created` < DATE_SUB(NOW(), INTERVAL %d %s)", $expr, $unit);
        } else {
            $output->writeln("<error>invalid value form '--older_than' option</error>");

            return 1;
        }

        if ($input->getOption('dry-run')) {
            $dry = true;
        }

        $sql = 'SELECT id, `name`, `type`, created FROM WebhookEvents WHERE ' . $clauseWhere;
        $connection = $this->container->getApplicationBox()->get_connection();

        $stmt = $connection->prepare($sql);
        $stmt->execute();
        $webhookEventsList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $webhookEventsId = array_column($webhookEventsList, 'id');

        if (!empty($webhookEventsId)) {
            $sqlDelivery = "SELECT id, application_id, event_id, created FROM WebhookEventDeliveries WHERE event_id IN (" . implode(', ', $webhookEventsId) . ")";
            $stmt = $connection->prepare($sqlDelivery);
            $stmt->execute();
            $webhookEventDeliveryList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $deliveryIds = array_column($webhookEventDeliveryList, 'id');
        }

        if ($input->getOption('show_sql')) {
            $output->writeln($sql);
        }

        if ($dry) {
            $output->writeln(sprintf("\n \n dry-run , %d WebhookEvents entry to delete", count($webhookEventsList)));
            $eventEntryTable = $this->getHelperSet()->get('table');
            $headers = ['id', 'name', 'type', 'created'];
            $eventEntryTable
                ->setHeaders($headers)
                ->setRows($webhookEventsList)
                ->render($output);

            if (!empty($webhookEventsId)) {
                $output->writeln(sprintf("\n \n dry-run , %d WebhookEventDelivery entry to delete", count($webhookEventDeliveryList)));
                $deliveryEntryTable = $this->getHelperSet()->get('table');
                $headers = ['id', 'application_id', 'event_id', 'created'];
                $deliveryEntryTable
                    ->setHeaders($headers)
                    ->setRows($webhookEventDeliveryList)
                    ->render($output);

                if (!empty($deliveryIds)) {
                    $sqlPayload = "SELECT id, delivery_id, status FROM WebhookEventPayloads WHERE delivery_id IN (". implode(', ', $deliveryIds).")";
                    $stmt = $connection->prepare($sqlPayload);
                    $stmt->execute();
                    $webhookEventPayloadsList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    $output->writeln(sprintf("\n \n dry-run , %d WebhookEventPayloads entry to delete", count($webhookEventPayloadsList)));
                    $payloadEntryTable = $this->getHelperSet()->get('table');
                    $headers = ['id', 'delivery_id', 'status'];
                    $payloadEntryTable
                        ->setHeaders($headers)
                        ->setRows($webhookEventPayloadsList)
                        ->render($output);
                }
            }
        } else {
            if (!empty($deliveryIds)) {
                $sqlPayloadDelete = 'DELETE FROM WebhookEventPayloads WHERE delivery_id IN (' . implode(',', $deliveryIds) . ')';
                $stmt = $connection->executeQuery($sqlPayloadDelete);

                $output->writeln(sprintf("%d WebhookEventPayloads entry deleted", $stmt->rowCount()));
            }

            if (!empty($webhookEventsId)) {
                $sqlDeliveryDelete = 'DELETE FROM WebhookEventDeliveries WHERE event_id IN ('. implode(',', $webhookEventsId) .')';
                $stmt = $connection->executeQuery($sqlDeliveryDelete);

                $output->writeln(sprintf("%d WebhookEventDeliveries entry deleted", $stmt->rowCount()));
            }

            $sqlEventDelete = 'DELETE FROM WebhookEvents WHERE ' . $clauseWhere;
            $stmt = $connection->executeQuery($sqlEventDelete);

            $output->writeln(sprintf("%d WebhookEvents entry deleted", $stmt->rowCount()));
        }
    }
}
