<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Databox;

use Alchemy\Phrasea\Databox\CachingDataboxRepositoryDecorator;
use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Doctrine\Common\Cache\Cache;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class CachedDataboxRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectProphecy */
    private $cache;
    private $cacheKey = 'test_key';
    /** @var ObjectProphecy */
    private $factory;
    /** @var ObjectProphecy */
    private $repository;

    /** @var CachingDataboxRepositoryDecorator */
    private $sut;

    protected function setUp()
    {
        $this->cache = $this->prophesize(Cache::class);
        $this->repository = $this->prophesize(DataboxRepository::class);
        $this->factory = $this->prophesize(DataboxFactory::class);

        $this->sut = new CachingDataboxRepositoryDecorator(
            $this->repository->reveal(),
            $this->cache->reveal(),
            $this->cacheKey,
            $this->factory->reveal()
        );
    }

    public function testItImplementsDataboxRepositoryInterface()
    {
        $this->assertInstanceOf(DataboxRepository::class, $this->sut);
    }

    public function testItFindsASpecificDataboxWhenNotInCache()
    {
        $databox = $this->prophesize(\databox::class);

        $this->cache->fetch($this->cacheKey)
            ->willReturn(false);
        $this->repository->find(42)
            ->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItHydrateDataboxWhenInCache()
    {
        $databox = $this->prophesize(\databox::class);

        $this->cache->fetch($this->cacheKey)
            ->willReturn([42 => ['foo' => 'bar']]);
        $this->repository->find(42)
            ->shouldNotBeCalled();
        $this->factory->create(42, ['foo' => 'bar'])
            ->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItProperlySaveCacheOnFindAll()
    {
        $databox = $this->prophesize(\databox::class);
        $databox->get_sbas_id()
            ->willReturn(42);
        $databox->getRawData()
            ->willReturn(['foo' => 'bar']);

        $cache_data = [42 => ['foo' => 'bar']];
        $databoxes = [42 => $databox->reveal()];

        $this->cache->fetch($this->cacheKey)
            ->willReturn(false);
        $this->repository->findAll()
            ->willReturn($databoxes);
        $this->cache->save($this->cacheKey, $cache_data)
            ->shouldBeCalled();

        $this->factory->createMany(Argument::any())
            ->shouldNotBeCalled();

        $this->assertSame($databoxes, $this->sut->findAll());
    }

    public function testItFindsAllDeclaredDataboxesFromCache()
    {
        $databox = $this->prophesize(\databox::class);

        $cache_data = [42 => ['foo' => 'bar']];
        $databoxes = [42 => $databox->reveal()];

        $this->cache->fetch($this->cacheKey)
            ->willReturn($cache_data);
        $this->repository->findAll()
            ->shouldNotBeCalled();
        $this->factory->createMany($cache_data)
            ->willReturn($databoxes);

        $this->assertSame($databoxes, $this->sut->findAll());
    }
}
