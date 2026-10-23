<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Checker\AbstractChecker;
use Alchemy\Phrasea\Border\File;

class AbstractCheckerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var AbstractChecker
     */
    private $sut;

    public function setUp()
    {
        parent::setUp();

        $this->app = $this->prophesize(Application::class);

        $this->sut = $this->getMockBuilder(AbstractChecker::class)
            ->setConstructorArgs([$this->app->reveal()])
            ->getMockForAbstractClass();


    }

    /**
     * @dataProvider getDataboxesCombination
     */
    public function testRestrictToDataboxes($databoxes, $collection, $assertion)
    {
        $file = $this->prophesize(File::class);
        $file->getCollection()->willReturn($collection);

        $this->sut->restrictToDataboxes($databoxes);

        $this->assertEquals($assertion, $this->sut->isApplicable($file->reveal()));
    }

    public function getDataboxesCombination()
    {
        $databox = $this->prophesize(\databox::class);
        $databox->get_sbas_id()->willReturn(1);

        $collection = $this->prophesize(\collection::class);
        $collection->get_databox()->willReturn($databox->reveal());
        $collection->get_base_id()->willReturn(2);

        $anotherDatabox = $this->prophesize(\databox::class);
        $anotherDatabox->get_sbas_id()->willReturn(3);

        return [
            [[], $collection, true],
            [[$databox->reveal()], $collection, true],
            [$databox->reveal(), $collection, true],
            [$databox->reveal(), null, true],
            [$anotherDatabox->reveal(), $collection, false],
        ];
    }

    /**
     * @dataProvider getCollectionsCombination
     */
    public function testRestrictToCollections($collection, $othercollection, $assertion)
    {
        $file = $this->prophesize(File::class);
        $file->getCollection()->willReturn($othercollection);

        $this->sut->restrictToCollections($collection);

        $this->assertEquals($assertion, $this->sut->isApplicable($file->reveal()));
    }

    public function getCollectionsCombination()
    {
        $databox = $this->prophesize(\databox::class);
        $databox->get_sbas_id()->willReturn(1);

        $collectionProphecy = $this->prophesize(\collection::class);
        $collectionProphecy->get_databox()->willReturn($databox->reveal());
        $collectionProphecy->get_base_id()->willReturn(2);
        $collection = $collectionProphecy->reveal();

        $otherCollectionProphecy = $this->prophesize(\collection::class);
        $otherCollectionProphecy->get_databox()->willReturn($databox->reveal());
        $otherCollectionProphecy->get_base_id()->willReturn(3);
        $othercollection = $otherCollectionProphecy->reveal();

        return [
            [[], $collection, true],
            [[$collection], $collection, true],
            [$collection, $collection, true],
            [$collection, null, true],
            [$collection, $othercollection, false],
            [[$collection], $othercollection, false],
        ];
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \LogicException
     */
    public function testMixCollectionFirst($databox, $collection)
    {
        $this->sut->restrictToCollections($collection);
        $this->sut->restrictToDataboxes($databox);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \LogicException
     */
    public function testMixDataboxFirst($databox, $collection)
    {
        $this->sut->restrictToDataboxes($databox);
        $this->sut->restrictToCollections($collection);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidDatabox($databox, $collection)
    {
        $this->sut->restrictToDataboxes($collection);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidCollection($databox, $collection)
    {
        $this->sut->restrictToCollections($databox);
    }

    public function getDataboxAndCollection()
    {
        $databox = $this->prophesize(\databox::class);
        $databox->get_sbas_id()->willReturn(1);

        $collection = $this->prophesize(\collection::class);
        $collection->get_databox()->willReturn($databox->reveal());
        $collection->get_base_id()->willReturn(2);

        return [
            [$databox, $collection],
        ];
    }
}
