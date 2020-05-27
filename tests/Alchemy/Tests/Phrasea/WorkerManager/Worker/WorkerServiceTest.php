<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\WorkerManager\Worker\AssetsIngestWorker;
use Alchemy\Phrasea\WorkerManager\Worker\CreateRecordWorker;
use Alchemy\Phrasea\WorkerManager\Worker\ExportMailWorker;
use Alchemy\Phrasea\WorkerManager\Worker\SubdefCreationWorker;
use Alchemy\Phrasea\WorkerManager\Worker\WriteMetadatasWorker;

/**
 * @covers Alchemy\Phrasea\WorkerManager\Worker\ExportMailWorker
 * @covers Alchemy\Phrasea\WorkerManager\Worker\SubdefCreationWorker
 * @covers Alchemy\Phrasea\WorkerManager\Worker\WriteMetadatasWorker
 */
class WorkerServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementationClass()
    {
        $app = new Application(Application::ENV_TEST);

        $exportMailWorker = new ExportMailWorker($app);
        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface', $exportMailWorker);

        $app['subdef.generator'] = $this->prophesize('Alchemy\Phrasea\Media\SubdefGenerator')->reveal();
        $app['alchemy_worker.message.publisher'] = $this->prophesize('Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher')->reveal();
        $app['alchemy_worker.logger'] = $this->prophesize("Monolog\Logger")->reveal();
        $app['dispatcher'] = $this->prophesize('Symfony\Component\EventDispatcher\EventDispatcherInterface')->reveal();
        $app['phraseanet.filesystem'] = $this->prophesize('Alchemy\Phrasea\Filesystem\FilesystemService')->reveal();
        $app['repo.worker-running-job'] = $this->prophesize('Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository')->reveal();
        $app['elasticsearch.indexer'] = $this->prophesize('Alchemy\Phrasea\SearchEngine\Elastic\Indexer')->reveal();

        $writer = $this->prophesize('PHPExiftool\Writer')->reveal();

        $subdefCreationWorker = new SubdefCreationWorker(
            $app['subdef.generator'],
            $app['alchemy_worker.message.publisher'],
            $app['alchemy_worker.logger'],
            $app['dispatcher'],
            $app['phraseanet.filesystem'],
            $app['repo.worker-running-job'],
            $app['elasticsearch.indexer']
            );
        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface', $subdefCreationWorker);

        $writemetadatasWorker = new WriteMetadatasWorker(
            $writer,
            $app['alchemy_worker.logger'],
            $app['alchemy_worker.message.publisher'],
            $app['repo.worker-running-job']
        );
        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface', $writemetadatasWorker);

        $assetsWorker = new AssetsIngestWorker($app);
        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface', $assetsWorker);

        $createRecordWorker = new CreateRecordWorker($app);
        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\WorkerInterface', $createRecordWorker);
    }
}
