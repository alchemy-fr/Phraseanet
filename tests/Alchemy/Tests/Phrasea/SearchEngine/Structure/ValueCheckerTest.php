<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Structure;

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
     * @dataProvider escapeRawProvider
     */
    public function testValueCompatibility($subject, $value, $compatible)
    {
        $this->assertEquals($compatible, ValueChecker::isValueCompatible($subject, $value));
    }

    public function escapeRawProvider()
    {
        $values = [
            [Mapping::TYPE_FLOAT  ,  42         , true ],
            [Mapping::TYPE_FLOAT  , '42'        , true ],
            [Mapping::TYPE_FLOAT  , '42foo'     , false],
            [Mapping::TYPE_FLOAT  , 'foo'       , false],
            [Mapping::TYPE_DOUBLE ,  42         , true ],
            [Mapping::TYPE_DOUBLE , '42'        , true ],
            [Mapping::TYPE_DOUBLE , '42foo'     , false],
            [Mapping::TYPE_DOUBLE , 'foo'       , false],
            [Mapping::TYPE_INTEGER,  42         , true ],
            [Mapping::TYPE_INTEGER, '42'        , true ],
            [Mapping::TYPE_INTEGER, '42foo'     , false],
            [Mapping::TYPE_INTEGER, 'foo'       , false],
            [Mapping::TYPE_LONG   ,  42         , true ],
            [Mapping::TYPE_LONG   , '42'        , true ],
            [Mapping::TYPE_LONG   , '42foo'     , false],
            [Mapping::TYPE_LONG   , 'foo'       , false],
            [Mapping::TYPE_SHORT  ,  42         , true ],
            [Mapping::TYPE_SHORT  , '42'        , true ],
            [Mapping::TYPE_SHORT  , '42foo'     , false],
            [Mapping::TYPE_SHORT  , 'foo'       , false],
            [Mapping::TYPE_BYTE   ,  42         , true ],
            [Mapping::TYPE_BYTE   , '42'        , true ],
            [Mapping::TYPE_BYTE   , '42foo'     , false],
            [Mapping::TYPE_BYTE   , 'foo'       , false],

            [Mapping::TYPE_STRING , 'foo'       , true ],
            [Mapping::TYPE_STRING , '42'        , true ],
            [Mapping::TYPE_STRING ,  42         , true ],

            [Mapping::TYPE_BOOLEAN, true        , true ],
            [Mapping::TYPE_BOOLEAN, false       , true ],
            [Mapping::TYPE_BOOLEAN, 'yes'       , true ],
            [Mapping::TYPE_BOOLEAN, 'no'        , true ],
            [Mapping::TYPE_BOOLEAN, 'foo'       , true ],
            [Mapping::TYPE_BOOLEAN, 42          , true ],

            [Mapping::TYPE_DATE   , '2015/01/01'         , true ],
            [Mapping::TYPE_DATE   , '2015/01/01 00:00:00', false],
            [Mapping::TYPE_DATE   , 'foo'                , false],
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
