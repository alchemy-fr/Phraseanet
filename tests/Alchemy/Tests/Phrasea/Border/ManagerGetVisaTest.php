<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Border;


use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Border\Checker\Response;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\Visa;
use Doctrine\ORM\EntityManager;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ManagerGetVisaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $app;

    /**
     * @var Manager
     */
    private $sut;

    protected function setUp()
    {
        $this->app = $this->prophesize(Application::class);
        $this->app->offsetGet('orm.em')->willReturn($this->prophesize(EntityManager::class));

        $this->sut = new Manager($this->app->reveal());
    }

    public function testGetVisaWithoutAnyCheckers()
    {
        $file = $this->prophesize(File::class);

        $visa = $this->sut->getVisa($file->reveal());

        $this->assertInstanceOf(Visa::class, $visa);
    }

    public function testGetVisaWithOnlyOneCheckerApplicable()
    {
        $file = $this->prophesize(File::class);

        $checker1 = $this->prophesize(CheckerInterface::class);
        $checker1->isApplicable($file->reveal())
            ->willReturn(false);
        $checker1->check(Argument::any(), $file->reveal())
            ->shouldNotBeCalled();

        $checker2 = $this->prophesize(CheckerInterface::class);
        $checker2->isApplicable($file->reveal())
            ->willReturn(true);
        $checker2->check(Argument::any(), $file->reveal())
            ->willReturn(new Response(true, $checker2->reveal()));

        $this->sut->registerCheckers([$checker1->reveal(), $checker2->reveal()]);

        $visa = $this->sut->getVisa($file->reveal());

        $this->assertInstanceOf(Visa::class, $visa);
        $responses = $visa->getResponses();

        $this->assertCount(1, $responses);
        $this->assertSame($checker2->reveal(), $responses[0]->getChecker());
    }
}
