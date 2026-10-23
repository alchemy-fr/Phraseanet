<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Repositories\BasketElementRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Prophecy\Argument;

class BasketElementRepositoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFindByRecordIdsWithBasketSucceed()
    {
        $result = [(object)['fake_object' => 'basket_element']];

        $entityManager = $this->prophesize(EntityManager::class);

        $builder = new QueryBuilder($entityManager->reveal());
        $entityManager->createQueryBuilder()->willReturn($builder);

        $entityManager->getExpressionBuilder()->willReturn(new Expr());

        $query = $this->prophesize(StubQuery::class);
        $query->setDql(Argument::any())->will(function ($arguments) {
            $this->getDql()->willReturn($arguments[0]);

            return $this;
        });
        $query->setParameters(Argument::any())->will(function ($arguments) {
            $this->getParameters()->willReturn($arguments[0]);

            return $this;
        });
        $query->setFirstResult(Argument::any())->will(function ($arguments) {
            $this->getFirstResult()->willReturn($arguments[0]);

            return $this;
        });
        $query->setMaxResults(Argument::any())->will(function ($arguments) {
            $this->getMaxResults()->willReturn($arguments[0]);

            return $this;
        });
        $query->getResult()->willReturn($result);


        $entityManager->createQuery(Argument::any())->will(function ($arguments) use ($query) {
            $query->reveal()->setDql($arguments[0]);

            return $query->reveal();
        });


        $sut = new BasketElementRepository($entityManager->reveal(), new ClassMetadata(BasketElement::class));

        $records = [
            ['databox_id' => 1, 'record_id' => 42],
        ];
        $basketId = 2;

        $this->assertSame($result, $sut->findByRecords($records, $basketId));
        $this->assertSame(sprintf('SELECT e FROM %s e WHERE e.basket = :basket_id AND (e.sbas_id = :databoxId1 AND e.record_id IN (:recordIds1))', BasketElement::class), $query->reveal()->getDql());

        $parameters = $query->reveal()->getParameters();
        $this->assertCount(3, $parameters);
        $this->assertEquals(new Query\Parameter('basket_id', 2, \PDO::PARAM_INT), $parameters[0]);
        $this->assertEquals(new Query\Parameter('databoxId1', 1, \PDO::PARAM_INT), $parameters[1]);
        $this->assertEquals(new Query\Parameter('recordIds1', [42], Connection::PARAM_INT_ARRAY), $parameters[2]);
    }
}

class StubQuery extends AbstractQuery
{
    public function setDql($dql = '')
    {
    }

    public function getDql()
    {
    }

    public function setFirstResult($firstResult)
    {
    }

    public function getFirstResult()
    {
    }

    public function setMaxResults($maxResult)
    {
    }

    public function getMaxResults()
    {
    }

    public function getSQL()
    {
    }

    protected function _doExecute()
    {
    }
}
