<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\Checker\AbstractChecker;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractCheckerTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var AbstractChecker
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();

        $this->object = new AbstractCheckerTester(self::$DI['app']);
        $this->file = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', ['getCollection'], [], 'CheckerTesterMock' . mt_rand(), false);
    }

    public function tearDown()
    {
        $this->file = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\AbstractChecker::restrictToDataboxes
     * @covers Alchemy\Phrasea\Border\Checker\AbstractChecker::isApplicable
     * @dataProvider getDataboxesCombinaison
     */
    public function testRestrictToDataboxes($databoxes, $collection, $assertion)
    {
        $this->file->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $this->object->restrictToDataboxes($databoxes);

        $this->assertEquals($assertion, $this->object->isApplicable($this->file));
    }

    public function getDataboxesCombinaison()
    {
        $databox = $collection = null;
        $app = new Application('test');

        foreach ($app['phraseanet.appbox']->get_databoxes() as $db) {
            if (! $collection) {
                foreach ($db->get_collections() as $coll) {
                    $collection = $coll;
                    break;
                }
            }
            if (! $collection) {
                $this->fail('Unable to get a collection');
            }

            if ($db->get_sbas_id() != $collection->get_databox()->get_sbas_id()) {
                $databox = $db;
                break;
            }
        }

        $ret = [
            [[$collection->get_databox()], $collection, true],
            [$collection->get_databox(), $collection, true],
            [$collection->get_databox(), null, true],
        ];

        if ($databox) {
            $ret[] = [$databox, $collection, false];
        }

        return $ret;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\AbstractChecker::restrictToCollections
     * @covers Alchemy\Phrasea\Border\Checker\AbstractChecker::isApplicable
     * @dataProvider getCollectionsCombinaison
     */
    public function testRestrictToCollections($collection, $othercollection, $assertion)
    {
        $this->file->expects($this->any())
            ->method('getCollection')
            ->will($this->returnValue($othercollection));

        $this->object->restrictToCollections($collection);

        $this->assertEquals($assertion, $this->object->isApplicable($this->file));
    }

    public function getCollectionsCombinaison()
    {
        $othercollection = $collection = null;
        $app = new Application('test');
        $databoxes = $app['phraseanet.appbox']->get_databoxes();
        if (count($databoxes) === 0) {
            $this->fail('Unable to find collections');
        }
        $databox = array_pop($databoxes);

        foreach ($databoxes as $db) {
            if (! $collection) {
                foreach ($db->get_collections() as $coll) {
                    $collection = $coll;
                    break;
                }
            }

            if (! $othercollection && $collection) {
                foreach ($db->get_collections() as $coll) {
                    if ($coll->get_base_id() != $collection->get_base_id()) {
                        $othercollection = $coll;
                        break;
                    }
                }
            }
        }

        if (null === $othercollection) {
            $othercollection = \collection::create($app, $databox, $app['phraseanet.appbox'], 'other coll');
        }
        if (null === $collection) {
            $collection = \collection::create($app, $databox, $app['phraseanet.appbox'], 'other coll');
        }

        return [
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
        $this->object->restrictToCollections($collection);
        $this->object->restrictToDataboxes($databox);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \LogicException
     */
    public function testMixDataboxFirst($databox, $collection)
    {
        $this->object->restrictToDataboxes($databox);
        $this->object->restrictToCollections($collection);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidDatabox($databox, $collection)
    {
        $this->object->restrictToDataboxes($collection);
    }

    /**
     * @dataProvider getDataboxAndCollection
     * @expectedException  \InvalidArgumentException
     */
    public function testInvalidCollection($databox, $collection)
    {
        $this->object->restrictToCollections($databox);
    }

    public function getDataboxAndCollection()
    {
        $databox = $collection = null;
        $app = new Application('test');

        foreach ($app['phraseanet.appbox']->get_databoxes() as $db) {
            if (! $databox) {
                $databox = $db;
            }
            if (! $collection) {
                foreach ($db->get_collections() as $coll) {
                    $collection = $coll;
                    break;
                }
            }
        }

        return [
            [$databox, $collection],
        ];
    }
}

class AbstractCheckerTester extends AbstractChecker
{

    public static function getMessage(TranslatorInterface $translator)
    {

    }

    public function check(EntityManager $em, File $file)
    {

    }
}
