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
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Databox\DataboxRepositoryInterface;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Statement;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DataboxRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectProphecy */
    private $app;
    /** @var ObjectProphecy */
    private $appbox;
    /** @var ObjectProphecy */
    private $factory;

    /** @var DataboxRepository */
    private $sut;

    protected function setUp()
    {
        $this->app = $this->prophesize(Application::class);
        $this->appbox = $this->prophesize(\appbox::class);
        $this->factory = $this->prophesize(DataboxFactory::class);

        $this->sut = new DataboxRepository($this->app->reveal(), $this->appbox->reveal(), $this->factory->reveal());
    }

    public function testItImplementsDataboxRepositoryInterface()
    {
        $this->assertInstanceOf(DataboxRepositoryInterface::class, $this->sut);
    }

    public function testItFindsDataboxProperly()
    {
        $databox = $this->prophesize(\databox::class);
        $this->factory->create($this->app->reveal(), 42)->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItReturnsNullOnNonExistentDatabox()
    {
        $this->factory->create($this->app->reveal(), 42)->willThrow(new NotFoundHttpException());

        $this->assertNull($this->sut->find(42));
    }

    public function testItFindsAllDeclaredDataboxes()
    {
        $databox1 = $this->prophesize(\databox::class);
        $databox1->get_sbas_id()
            ->willReturn(1);
        $databox1_cache = ['sbas_id' => 1, 'foo' => 'bar'];

        $databox2 = $this->prophesize(\databox::class);
        $databox2->get_sbas_id()
            ->willReturn(2);
        $databox2_cache = ['sbas_id' => 2, 'bar' => 'baz'];

        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willReturn([
                $databox1_cache,
                $databox2_cache,
            ]);

        $this->factory->create($this->app->reveal(), 1, $databox1_cache)
            ->willReturn($databox1->reveal());

        $this->factory->create($this->app->reveal(), 2, $databox2_cache)
            ->willReturn($databox2->reveal());

        $this->assertEquals([
            1 => $databox1->reveal(),
            2 => $databox2->reveal(),
        ], $this->sut->findAll());
    }

    public function testItFindsAllDeclaredDataboxesWhenNoCacheIsPresent()
    {
        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willReturn(false);

        $connection = $this->prophesize(Connection::class);
        $this->appbox->get_connection()
            ->willReturn($connection->reveal());

        $statement = $this->prophesize(Statement::class);
        $connection->prepare('SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas')
            ->willReturn($statement->reveal());

        $databox1 = $this->prophesize(\databox::class);
        $databox1->get_sbas_id()
            ->willReturn(1);
        $cache = [['sbas_id' => 1, 'foo' => 'bar']];

        $statement->execute()->shouldBeCalled();
        $statement->fetchAll(\PDO::FETCH_ASSOC)
            ->willReturn($cache);
        $statement->closeCursor()->shouldBeCalled();

        $this->appbox->set_data_to_cache($cache, \appbox::CACHE_LIST_BASES)
            ->shouldBeCalled();

        $this->factory->create($this->app->reveal(), 1, $cache[0])
            ->willReturn($databox1->reveal());

        $this->assertEquals([1 => $databox1->reveal()], $this->sut->findAll());
    }
}
