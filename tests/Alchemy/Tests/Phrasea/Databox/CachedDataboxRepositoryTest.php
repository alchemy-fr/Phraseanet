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

use Alchemy\Phrasea\Cache\Exception;
use Alchemy\Phrasea\Databox\CachedDataboxRepository;
use Alchemy\Phrasea\Databox\DataboxHydrator;
use Alchemy\Phrasea\Databox\DataboxRepositoryInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class CachedDataboxRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectProphecy */
    private $appbox;
    /** @var ObjectProphecy */
    private $hydrator;
    /** @var ObjectProphecy */
    private $repository;

    /** @var CachedDataboxRepository */
    private $sut;

    protected function setUp()
    {
        $this->appbox = $this->prophesize(\appbox::class);
        $this->repository = $this->prophesize(DataboxRepositoryInterface::class);
        $this->hydrator = $this->prophesize(DataboxHydrator::class);

        $this->sut = new CachedDataboxRepository($this->repository->reveal(), $this->appbox->reveal(), $this->hydrator->reveal());
    }

    public function testItImplementsDataboxRepositoryInterface()
    {
        $this->assertInstanceOf(DataboxRepositoryInterface::class, $this->sut);
    }

    public function testItFindsASpecificDataboxWhenNotInCache()
    {
        $databox = $this->prophesize(\databox::class);

        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willReturn(false);
        $this->repository->find(42)
            ->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItHydrateDataboxWhenInCache()
    {
        $databox = $this->prophesize(\databox::class);

        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willReturn([42 => ['foo' => 'bar']]);
        $this->repository->find(42)
            ->shouldNotBeCalled();
        $this->hydrator->hydrateRow(42, ['foo' => 'bar'])
            ->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItProperlySaveCacheOnFindAll()
    {
        $databox = $this->prophesize(\databox::class);
        $databox->get_sbas_id()
            ->willReturn(42);
        $databox->getAsRow()
            ->willReturn(['foo' => 'bar']);

        $cache_data = [42 => ['foo' => 'bar']];
        $databoxes = [42 => $databox->reveal()];

        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willThrow(new Exception());
        $this->repository->findAll()
            ->willReturn($databoxes);
        $this->appbox->set_data_to_cache($cache_data, \appbox::CACHE_LIST_BASES)
            ->shouldBeCalled();

        $this->hydrator->hydrateRows(Argument::any())
            ->shouldNotBeCalled();

        $this->assertSame($databoxes, $this->sut->findAll());
    }

    public function testItFindsAllDeclaredDataboxesFromCache()
    {
        $databox = $this->prophesize(\databox::class);

        $cache_data = [42 => ['foo' => 'bar']];
        $databoxes = [42 => $databox->reveal()];

        $this->appbox->get_data_from_cache(\appbox::CACHE_LIST_BASES)
            ->willReturn($cache_data);
        $this->repository->findAll()
            ->shouldNotBeCalled();
        $this->hydrator->hydrateRows($cache_data)
            ->willReturn($databoxes);

        $this->assertSame($databoxes, $this->sut->findAll());
    }
}
