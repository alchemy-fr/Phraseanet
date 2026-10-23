<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\TaskManager\Job\AbstractJob;
use Alchemy\Phrasea\TaskManager\Job\EmptyCollectionJob;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmptyCollectionJobTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var ObjectProphecy
     */
    private $translator;

    /**
     * @var EmptyCollectionJob
     */
    private $sut;

    protected function setUp(): void
    {
        $this->translator = $this->prophesize(\Symfony\Contracts\Translation\TranslatorInterface::class);

        $this->sut = new EmptyCollectionJob($this->translator->reveal());
    }

    public function testItExtendsAbstractJob()
    {
        $this->assertInstanceOf(AbstractJob::class, $this->sut);
    }
}
