<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Typed;

/**
 * @group unit
 * @group structure
 */
class ValueCheckerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider valueCheckerProvider
     */
    public function testValueCompatibility($subject, $value, $compatible)
    {
        $this->assertEquals($compatible, ValueChecker::isValueCompatible($subject, $value));
    }

    public function valueCheckerProvider()
    {
        $values = [
            [FieldMapping::TYPE_FLOAT  ,  42         , true ],
            [FieldMapping::TYPE_FLOAT  , '42'        , true ],
            [FieldMapping::TYPE_FLOAT  , '42foo'     , true],
            [FieldMapping::TYPE_FLOAT  , 'foo'       , true],
            [FieldMapping::TYPE_DOUBLE ,  42         , true ],
            [FieldMapping::TYPE_DOUBLE , '42'        , true ],
            [FieldMapping::TYPE_DOUBLE , '42foo'     , true],
            [FieldMapping::TYPE_DOUBLE , 'foo'       , true],
            [FieldMapping::TYPE_INTEGER,  42         , true ],
            [FieldMapping::TYPE_INTEGER, '42'        , true ],
            [FieldMapping::TYPE_INTEGER, '42foo'     , true],
            [FieldMapping::TYPE_INTEGER, 'foo'       , true],
            [FieldMapping::TYPE_LONG   ,  42         , true ],
            [FieldMapping::TYPE_LONG   , '42'        , true ],
            [FieldMapping::TYPE_LONG   , '42foo'     , true],
            [FieldMapping::TYPE_LONG   , 'foo'       , true],
            [FieldMapping::TYPE_SHORT  ,  42         , true ],
            [FieldMapping::TYPE_SHORT  , '42'        , true ],
            [FieldMapping::TYPE_SHORT  , '42foo'     , true],
            [FieldMapping::TYPE_SHORT  , 'foo'       , true],
            [FieldMapping::TYPE_BYTE   ,  42         , true ],
            [FieldMapping::TYPE_BYTE   , '42'        , true ],
            [FieldMapping::TYPE_BYTE   , '42foo'     , true],
            [FieldMapping::TYPE_BYTE   , 'foo'       , true],

            [FieldMapping::TYPE_TEXT , 'foo'       , true ],
            [FieldMapping::TYPE_TEXT , '42'        , true ],
            [FieldMapping::TYPE_TEXT ,  42         , true ],

            [FieldMapping::TYPE_BOOLEAN, true        , true ],
            [FieldMapping::TYPE_BOOLEAN, false       , true ],
            [FieldMapping::TYPE_BOOLEAN, 'yes'       , true ],
            [FieldMapping::TYPE_BOOLEAN, 'no'        , true ],
            [FieldMapping::TYPE_BOOLEAN, 'foo'       , true ],
            [FieldMapping::TYPE_BOOLEAN, 42          , true ],

            [FieldMapping::TYPE_DATE   , '2015/01/01'         , true ],
            [FieldMapping::TYPE_DATE   , '2015/01/01 00:00:00', true],
            [FieldMapping::TYPE_DATE   , 'foo'                , true],
        ];

        foreach ($values as &$value) {
            $value[0] = $this->createTypedMock($value[0]);
        }

        return $values;
    }

    private function createTypedMock($type)
    {
        $typed = $this->prophesize(Typed::class);
        $typed->getType()->willReturn($type)->shouldBeCalled();
        return $typed->reveal();
    }
}
