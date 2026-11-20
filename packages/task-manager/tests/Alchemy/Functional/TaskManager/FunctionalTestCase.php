<?php

namespace Alchemy\Functional\TaskManager;

use Symfony\Component\Finder\Finder;

class FunctionalTestCase extends \PHPUnit\Framework\TestCase
{
    protected $lockDir;

    protected function setUp(): void
    {
        $this->lockDir = __DIR__ . '/LockDir';
        if (!is_dir($this->lockDir)) {
            mkdir($this->lockDir);
        }

        $finder = Finder::create();

        $finder->files()->in($this->lockDir);

        foreach ($finder as $file) {
            unlink($file->getPathname());
        }
    }

    protected function getNonStoppingScript($time, $extra, $conf)
    {
        return '<?php
        require "'.__DIR__.'/../../../../vendor/autoload.php";

        use Alchemy\TaskManager\Job\JobDataInterface;

        declare(ticks=1);

        class Job extends Alchemy\TaskManager\Job\AbstractJob
        {
            protected function doRun(JobDataInterface $data)
            {
                '.$extra.'
                usleep('.($time*10).'*100000);
            }
        }

        $job = new Job();
        '.$conf.'
        assert($job === $job->run());
        ';
    }

    protected function getSelfStoppingScript()
    {
        return '<?php
        require "'.__DIR__.'/../../../../vendor/autoload.php";

        use Alchemy\TaskManager\Job\JobDataInterface;

        declare(ticks=1);

        class Job extends Alchemy\TaskManager\Job\AbstractJob
        {
            protected function doRun(JobDataInterface $data)
            {
                $n = 0;
                while ($n < 60 && $this->getStatus() === static::STATUS_STARTED) {
                    usleep(10000);
                    $n++;
                }
                $this->stop($data);
            }
        }

        $job = new Job();
        $job->addSubscriber(new \Alchemy\TaskManager\Event\JobSubscriber\StopSignalSubscriber(Neutron\SignalHandler\SignalHandler::getInstance()));
        $job->addSubscriber(new \Alchemy\TaskManager\Event\JobSubscriber\LockFileSubscriber("id", null, "'.$this->lockDir.'"));
        $job->run();
        ';
    }
}
