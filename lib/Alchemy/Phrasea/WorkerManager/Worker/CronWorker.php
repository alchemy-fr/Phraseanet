<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class CronWorker implements WorkerInterface
{
    private $app;
    /** @var LoggerInterface */
    private $logger;
    /** @var MessagePublisher */
    private $messagePublisher;

    public function __construct(PhraseaApplication $app)
    {
        $this->app              = $app;
        $this->messagePublisher = $this->app['alchemy_worker.message.publisher'];
        $this->logger           = $this->app['alchemy_worker.logger'];
    }

    public function process(array $payload)
    {
        $nowTime = time();
        $tNow = strtotime((new \DateTime())->format('H:i:s'));
        $forceRun = false;
        $rePublish = true;

        if (empty($payload['next_execution_timestamp'])) {
            if (isset($payload['first_run'])) {
                try {
                    $tFirstRun = strtotime((new \DateTime($payload['first_run']))->format('H:i:s'));
                    if ($tFirstRun <= $tNow && $tFirstRun > ($tNow - $payload['worker_period'])) {
                        $forceRun = true;
                    }
                } catch(\Exception $e) {
                    $this->logger->info(sprintf('first_run format should be hh:mm:ss for task %s', $payload['name']));
                }
            } else {
                $payload['next_execution_timestamp'] = $nowTime + $payload['period'];
                $payload['next_execution'] = date('Y-m-d\TH:i:s', ($nowTime + $payload['period']));
            }
        }

        if ($forceRun || (!empty($payload['next_execution_timestamp']) &&
            $payload['next_execution_timestamp'] >= ($nowTime - $payload['period']) &&
            $payload['next_execution_timestamp'] <= $nowTime)) {

            foreach ($payload['commands'] as $command) {
                $commandLine = $this->getCommandLine($command);
                if ($commandLine !== null) {
                    $process = new Process($commandLine);
                    $process->start();

                    // compute the next execution with the task period
                    $payload['next_execution_timestamp'] = $nowTime + $payload['period'];
                    $payload['next_execution'] = date('Y-m-d\TH:i:s', ($nowTime + $payload['period']));
                    $payload['last_execution_timestamp'] = time();
                    $payload['last_execution'] = date('Y-m-d\TH:i:s');

                    $this->logger->info(sprintf("Task : %s about : %s executed on %s", $payload['name'], $payload['about'], date('Y-m-d\TH:i:s')));
                } else {
                    $this->logger->info("Unknown type of command given in task " . $payload['name']);
                }
            }
            if (!empty($payload['run']) && $payload['run'] == 'single') {
                // remove from the list of task
                $rePublish = false;
            }
        }

//        foreach ($payload['config']['tasks'] as $key => $task) {
//            $forceRun = false;
//            if (empty($payload['config']['tasks'][$key]['next_execution_timestamp'])) {
//                if (isset($task['first_run'])) {
//                    // if first_run is defined, initiate only the task on the indicated time
//                    // the indicated time is greater enough than now minus the period of loop
//                    try {
//                        $tFirstRun = strtotime((new \DateTime($task['first_run']))->format('H:i:s'));
//                        if ($tFirstRun <= $tNow && $tFirstRun > ($tNow - $payload['config']['period'])) {
//                            $forceRun = true;
//                        }
//                    } catch(\Exception $e) {
//                        $this->logger->info(sprintf('first_run format should be hh:mm:ss for task %s', $task['name']));
//                    }
//
//                } else {
//                    $payload['config']['tasks'][$key]['next_execution_timestamp'] = $nowTime + $task['period'];
//                    $payload['config']['tasks'][$key]['next_execution'] = date('Y-m-d\TH:i:s', ($nowTime + $task['period']));
//                }
//            }
//
//            if ($forceRun || $this->canExecuteNow($payload, $task, $key, $nowTime)) {
//                foreach ($task['commands'] as $command) {
//                    $commandLine = $this->getCommandLine($command);
//                    if ($commandLine !== null) {
//                        $process = new Process($commandLine);
//                        $process->start();
//
//                        if (!empty($task['run']) && $task['run'] == 'single') {
//                            // remove from the list of task
//                            unset($payload['config']['tasks'][$key]);
//                        } else {
//                            // compute the next execution with the task period
//                            $payload['config']['tasks'][$key]['next_execution_timestamp'] = $nowTime + $task['period'];
//                            $payload['config']['tasks'][$key]['next_execution'] = date('Y-m-d\TH:i:s', ($nowTime + $task['period']));
//                            $payload['config']['tasks'][$key]['last_execution_timestamp'] = time();
//                            $payload['config']['tasks'][$key]['last_execution'] = date('Y-m-d\TH:i:s');
//                        }
//
//                        $this->logger->info(sprintf("Task : %s about : %s executed on %s", $task['name'], $task['about'], date('Y-m-d\TH:i:s')));
//                    } else {
//                        $this->logger->info("Unknown type of command given in task " . $task['name']);
//                    }
//                }
//            }
//        }
//
//        // if a backup period is defined
//        // save it as yml on ./config/crontab_backup.yml
//        if (isset($payload['config']['backup_period'])) {
//            if (empty($payload['config']['last_backup_timestamp'])) {
//                // initiate var
//                $payload['config']['last_backup_timestamp'] = time();
//            }
//
//            if (($payload['config']['last_backup_timestamp'] + $payload['config']['backup_period']) <= time()) {
//                $this->dumpConfiguration($payload['config']);
//                $payload['config']['last_backup_timestamp'] = time();
//            }
//        }

        if ($rePublish) {
            $payload = [
                'message_type' => MessagePublisher::CRON_TYPE,
                'payload' => $payload
            ];

            $this->messagePublisher->publishRetryMessage($payload, MessagePublisher::CRON_TYPE, 0, 'Loop for cron');
        }
    }

    /**
     * @param array $payload
     * @param array $task
     * @param $key
     * @param $nowTime
     * @return bool
     */
    private function canExecuteNow(array $payload, array $task, $key, $nowTime)
    {
        if (!empty($payload['config']['tasks'][$key]['next_execution_timestamp']) &&
            $payload['config']['tasks'][$key]['next_execution_timestamp'] >= ($nowTime - $task['period']) &&
            $payload['config']['tasks'][$key]['next_execution_timestamp'] <= $nowTime) {

            return true;
        }

        return false;
    }

    private function getCommandLine($command)
    {
        switch ($command['type']) {
            case 'phraseanet_console':
                if (strpos($command['args'], 'worker:execute') == false) {
                    return 'bin/console ' . $command['args'];
                }
                return null;
            default:
                return null;
        }

    }

    private function dumpConfiguration(array $config)
    {
        $content = Yaml::dump($config);

        file_put_contents($this->app['root.path'] . '/config/crontab_backup.yml', $content);
    }
}
