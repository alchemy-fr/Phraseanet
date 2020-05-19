<?php

namespace Alchemy\Tests\Phrasea\WorkerManager\Worker\Factory;

use Alchemy\Phrasea\WorkerManager\Worker\Factory\CallableWorkerFactory;

class CallableWorkerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testClassImplements()
    {
        $sut = new CallableWorkerFactory(function () {});

        $this->assertInstanceOf('Alchemy\\Phrasea\\WorkerManager\\Worker\\Factory\\WorkerFactoryInterface', $sut);
    }
}
