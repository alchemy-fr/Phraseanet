<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\TaskManager\Job\AbstractJob;
use Alchemy\Phrasea\TaskManager\Job\EmptyCollectionJob;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Translation\TranslatorInterface;

class EmptyCollectionJobTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $translator;

    /**
     * @var EmptyCollectionJob
     */
    private $sut;

    protected function setUp()
    {
        $this->translator = $this->prophesize(\Symfony\Contracts\Translation\TranslatorInterface::class);

        $this->sut = new EmptyCollectionJob($this->translator->reveal());
    }

    public function testItExtendsAbstractJob()
    {
        $this->assertInstanceOf(AbstractJob::class, $this->sut);
    }
}
