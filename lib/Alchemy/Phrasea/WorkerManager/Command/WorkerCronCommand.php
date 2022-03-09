<?php

namespace Alchemy\Phrasea\WorkerManager\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class WorkerCronCommand extends Command
{
    public function __construct()
    {
        parent::__construct('worker:cron');

        $this->setDescription('Start, stop or save message for cron worker')
            ->addOption('start', null, InputOption::VALUE_NONE, 'Start cron worker')
            ->addOption('stop', null, InputOption::VALUE_NONE, 'Save json message from Q on file and stop the cron')
            ->addOption('yml_dump', null, InputOption::VALUE_NONE, 'Save message from Q as yml')
        ;

    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('stop')) {
            $message = $this->getPublisher()->getMessageFromQ(MessagePublisher::CRON_TYPE . '_retry');
            if (is_object($message)) {
                if ($input->getOption('yml_dump')) {
                    $body = json_decode($message->body, true);
                    $body = Yaml::dump($body['payload']['config']);

                    file_put_contents($this->container['root.path'] . '/config/crontab_backup.yml', $body);
                    $output->writeln('A backup of yml message saved on ./config/crontab_backup.yml');
                } else {
                    // save the json in log directory
                    $filename = $this->container['conf']->get(['main', 'storage', 'log']). '/crontab_backup.txt' ;
                    file_put_contents($filename, $message->body);

                    $output->writeln(sprintf('A backup of json message saved on %s', $filename));
                }
            }

            $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::CRON_TYPE]);

            $output->writeln(sprintf('The worker cron is successfully stopped on %s', date('Y-m-d\TH:i:s')));
        } elseif ($input->getOption('yml_dump')) {
            $message = $this->getPublisher()->getMessageFromQ(MessagePublisher::CRON_TYPE . '_retry');
            if (is_object($message)) {
                $body = json_decode($message->body, true);
                $body = Yaml::dump($body['payload']['config']);

                file_put_contents($this->container['root.path'] . '/config/crontab_backup.yml', $body);
                $output->writeln('A backup of yml message saved on ./config/crontab_backup.yml');
            } else {
                $output->writeln('No message saved');
            }
        } else {
            if (!is_file($this->container['root.path'] . '/config/crontab.yml')) {
                $output->writeln(sprintf('<error>Missing configuration file in %s : ', $this->container['root.path'] . '/config/crontab.yml</error>'));

                return 0;
            }

            $config = Yaml::parse(file_get_contents($this->container['root.path'] . '/config/crontab.yml'));

//            $this->container['conf']->set(['workers', 'queues', MessagePublisher::CRON_TYPE, 'ttl_retry'], $config['period'] * 1000);
//            $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::CRON_TYPE]);
//
//            $payload = [
//                'message_type' => MessagePublisher::CRON_TYPE,
//                'payload' => [
//                    'config' => $config
//                ]
//            ];
//
//            $this->getPublisher()->publishRetryMessage($payload, MessagePublisher::CRON_TYPE, 0, 'Loop for cron');
//            $output->writeln(sprintf('The worker cron is successfully launch at %s', date('Y-m-d\TH:i:s')));

            foreach ($config['tasks'] as $task) {
                $task['worker_period'] = $config['period'];
                $payload = [
                    'message_type' => MessagePublisher::CRON_TYPE,
                    'payload' => $task
                ];

                $this->getPublisher()->publishRetryMessage($payload, MessagePublisher::CRON_TYPE, 0, 'Loop for cron');
            }
        }
    }

    /**
     * @return MessagePublisher
     */
    private function getPublisher()
    {
        return $this->container['alchemy_worker.message.publisher'];
    }

    /**
     * @return AMQPConnection
     */
    private function getAMQPConnection()
    {
        return $this->container['alchemy_worker.amqp.connection'];
    }
}
