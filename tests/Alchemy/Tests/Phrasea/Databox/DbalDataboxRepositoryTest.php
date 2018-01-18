<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Tests\Phrasea\Databox;

use Alchemy\Phrasea\Databox\DataboxFactory;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Databox\DbalDataboxRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

final class DbalDataboxRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ObjectProphecy */
    private $connection;
    /** @var ObjectProphecy */
    private $factory;
    /** @var DbalDataboxRepository */
    private $sut;

    protected function setUp()
    {
        $this->connection = $this->prophesize(Connection::class);
        $this->factory = $this->prophesize(DataboxFactory::class);

        $this->sut = new DbalDataboxRepository($this->connection->reveal(), $this->factory->reveal());
    }

    public function testItImplementsDataboxRepositoryInterface()
    {
        $this->assertInstanceOf(DataboxRepository::class, $this->sut);
    }

    public function testItFindsDataboxProperly()
    {
        $databox = $this->prophesize(\databox::class);

        $statement = $this->prophesize(Statement::class);
        $this->connection
            ->prepare('SELECT ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas WHERE sbas_id = :id')
            ->willReturn($statement->reveal());
        $statement->execute(['id' => 42])
            ->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(['foo' => 'bar']);
        $statement->closeCursor()
            ->shouldBeCalled();

        $this->factory->create(42, ['foo' => 'bar'])
            ->willReturn($databox->reveal());

        $this->assertSame($databox->reveal(), $this->sut->find(42));
    }

    public function testItReturnsNullOnNonExistentDatabox()
    {
        $statement = $this->prophesize(Statement::class);
        $this->connection
            ->prepare('SELECT ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas WHERE sbas_id = :id')
            ->willReturn($statement->reveal());
        $statement->execute(['id' => 42])
            ->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(false);
        $statement->closeCursor()
            ->shouldBeCalled();

        $this->factory->create(42, Argument::any())
            ->shouldNotBeCalled();

        $this->assertNull($this->sut->find(42));
    }

    public function testItFindsAllDataboxes()
    {
        $databox = $this->prophesize(\databox::class);

        $statement = $this->prophesize(Statement::class);
        $this->connection
            ->prepare('SELECT sbas_id, ord, viewname, label_en, label_fr, label_de, label_nl FROM sbas')
            ->willReturn($statement->reveal());
        $statement->execute()
            ->shouldBeCalled();
        $statement->fetch(\PDO::FETCH_ASSOC)
            ->willReturn(['sbas_id' => 42, 'foo' => 'bar'], false);
        $statement->closeCursor()
            ->shouldBeCalled();

        $this->factory->createMany([42 => ['foo' => 'bar']])
            ->willReturn([$databox->reveal()]);

        $this->assertSame([$databox->reveal()], $this->sut->findAll());
    }
}
