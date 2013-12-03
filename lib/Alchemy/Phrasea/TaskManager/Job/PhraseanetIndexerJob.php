<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\TaskManager\Editor\PhraseanetIndexerEditor;
use Alchemy\Phrasea\TaskManager\Event\PhraseanetIndexerStopperSubscriber;
use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Process\ProcessBuilder;

class PhraseanetIndexerJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans("Phrasea indexation task");
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'PhraseanetIndexer';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("This task is used to index records for Phrasea engine.");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new PhraseanetIndexerEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();
        $task = $data->getTask();

        $settings = simplexml_load_string($task->getSettings());
        $socketPort = (integer) $settings->socket;
        $indexerPath = $this->getPhraseanetIndexerPath($app);

        $builder = new ProcessBuilder($this->getCommandline($indexerPath, $app, $task));
        $builder
            ->setWorkingDirectory(dirname($indexerPath))
            ->setTimeout(0);
        $process = $builder->getProcess();

        if (0 < $socketPort) {
            $this->addSubscriber(new PhraseanetIndexerStopperSubscriber($socketPort));
        }

        $process->run();
    }

    private function getPhraseanetIndexerPath(Application $app)
    {
        $binaries = $app['conf']->get('binaries');

        if (isset($binaries['phraseanet_indexer'])) {
            $path = $binaries['phraseanet_indexer'];
        } else {
            if (null === $binary = $app['executable-finder']->find('phraseanet_indexer')) {
                // let's be careful, when an executable is not found, an exit code must be returned
                // see documentation
                throw new RuntimeException('Unable to find phraseanet indexer binary. Either set it up in the configuration, or update the PATH to allow auto detection.');
            }
            $path = $binary;
        }

        if (!is_executable($path)) {
            throw new RuntimeException(sprintf('Phraseanet Indexer path `%s` does not seem to be executable. Please update configuration.', $path));
        }

        return $path;
    }

    private function getCommandline($indexerPath, Application $app, Task $task)
    {
        $cmd = [$indexerPath, '-o'];

        $settings = simplexml_load_string($task->getSettings());

        $host = trim($settings->host);
        $port = (integer) $settings->port;
        $base = trim($settings->base);
        $user = trim($settings->user);
        $password = trim($settings->password);
        $socket = (integer) $settings->socket;
        $charset = trim($settings->charset);
        $stem = trim($settings->stem);
        $sortempty = trim($settings->sortempty);
        $debugmask = (integer) $settings->debugmask;
        $nolog = \p4field::isyes(trim($settings->nolog));
        $winsvc_run = \p4field::isyes(trim($settings->winsvc_run));

        if ('' !== $host) {
            $cmd[] = '--host';
            $cmd[] = $host;
        }
        if (0 < $port) {
            $cmd[] = '--port';
            $cmd[] = $port;
        }
        if ('' !== $base) {
            $cmd[] = '--base';
            $cmd[] = $base;
        }
        if ('' !== $user) {
            $cmd[] = '--user';
            $cmd[] = $user;
        }
        if ('' !== $password) {
            $cmd[] = '--password';
            $cmd[] = $password;
        }
        if (0 < $socket) {
            $cmd[] = '--socket';
            $cmd[] = $socket;
        }

        if ('' !== $charset) {
            $cmd[] = '--default-character-set';
            $cmd[] = $charset;
        }
        if ('' !== $stem) {
            $cmd[] = '--stem';
            $cmd[] = $stem;
        }
        if ('' !== $sortempty) {
            $cmd[] = '--sort-empty';
            $cmd[] = $sortempty;
        }
        if (0 < $debugmask) {
            $cmd[] = '--debug';
            $cmd[] = $debugmask;
        }
        if ($nolog) {
            $cmd[] = '--nolog';
        }
        if ($winsvc_run) {
            $cmd[] = '--run';
        }

        return $cmd;
    }
}
