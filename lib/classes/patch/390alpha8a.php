<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Task;

class patch_390alpha8a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.8';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['20131118000004'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM Tasks';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'SELECT task_id, active, crashed, name, class, settings FROM task2';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            try {
                $job = $this->createJob($app, $row['class']);
            } catch (\RuntimeException $e) {
                continue;
            }

            $settings = simplexml_load_string($row['settings']);
            $period = $job->getEditor()->getDefaultPeriod();
            if ($settings->period) {
                $period = (int) $settings->period;
                unset($settings->period);
                $row['settings'] = $settings->asXML();
            }

            $task = new Task();
            $task->setCrashed($row['crashed'])
                ->setJobId($job->getJobId())
                ->setName($row['name'])
                ->setPeriod($period)
                ->setSettings($row['settings'])
                ->setStatus($row['active'] ? Task::STATUS_STARTED : Task::STATUS_STOPPED);

            $app['orm.em']->persist($task);
        }
        $app['orm.em']->flush();
    }

    private function createJob(Application $app, $class)
    {
        switch (strtolower($class)) {
            case 'task_period_recordmover':
            case 'recordmover':
                $name = 'RecordMover';
                break;
            case 'task_period_apibridge':
            case 'apibridge':
                $name = 'Bridge';
                break;
            case 'task_period_archive':
            case 'archive':
                $name = 'Archive';
                break;
            case 'task_period_emptycoll':
            case 'emptycoll':
                $name = 'EmptyCollection';
                break;
            case 'task_period_ftp':
            case 'ftp':
                $name = 'Ftp';
                break;
            case 'task_period_ftppull':
            case 'ftppull':
                $name = 'FtpPull';
                break;
            case 'task_period_subdef':
            case 'subdef':
                $name = 'Subdefs';
                break;
            case 'task_period_test':
            case 'test':
                $name = 'Null';
                break;
            case 'task_period_writemeta':
            case 'writemeta':
                $name = 'WriteMetadata';
                break;
            default:
                throw new \RuntimeException(sprintf('Unable to migrate task named %s ', $class));
        }

        return $app['task-manager.job-factory']->create($name);
    }
}
