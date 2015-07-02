<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Databox\DataboxRepositoryInterface;
use Prophecy\Prophecy\ObjectProphecy;

final class DataboxRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectProphecy */
    private $app;
    /** @var ObjectProphecy */
    private $appbox;

    /** @var DataboxRepository */
    private $sut;

    protected function setUp()
    {
        $this->app = $this->prophesize(Application::class);
        $this->appbox = $this->prophesize(\appbox::class);

        $this->sut = new DataboxRepository($this->app->reveal(), $this->appbox->reveal());
    }

    public function testItImplementsDataboxRepositoryInterface()
    {
        $this->assertInstanceOf(DataboxRepositoryInterface::class, $this->sut);
    }
}
