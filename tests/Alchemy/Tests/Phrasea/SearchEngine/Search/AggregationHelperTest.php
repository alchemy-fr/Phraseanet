<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\AggregationHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

/**
 * @group unit
 * @group searchengine
 */
class AggregationHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testAggregationWrappingOnPrivateField()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, [
            'private' => true,
            'used_by_collections' => [1, 2, 3]
        ]);
        $agg = [
            'terms' => 'bar'
        ];
        $expected = '{
            "filter": {
                "terms": {
                    "base_id": [1, 2, 3]
                }
            },
            "aggs": {
                "__wrapped_private_field__": {
                    "terms": "bar"
                }
            }
        }';

        $wrapped = AggregationHelper::wrapPrivateFieldAggregation($field, $agg);
        $this->assertEquals(json_decode($expected, true), $wrapped);
    }

    public function testAggregationWrappingOnUnrestrictedField()
    {
        $field = new Field('foo', FieldMapping::TYPE_TEXT, ['private' => false]);
        $agg = [
            'terms' => 'bar'
        ];

        $wrapped = AggregationHelper::wrapPrivateFieldAggregation($field, $agg);
        $this->assertEquals($agg, $wrapped);
    }

    public function testAggregationUnwrapping()
    {
        $agg = [
            'doc_count' => 3,
            '__wrapped_private_field__' => [
                'buckets' => [[
                    'key' => 'foo',
                    'doc_count' => 1
                ]]
            ]
        ];

        $expected = [
            'buckets' => [[
                'key' => 'foo',
                'doc_count' => 1
            ]]
        ];

        $unwrapped = AggregationHelper::unwrapPrivateFieldAggregation($agg);
        $this->assertEquals($expected, $unwrapped);
    }

    public function testUnwrappingOnNotWrappedAggregation()
    {
        $agg = [
            'buckets' => [[
                'key' => 'foo',
                'doc_count' => 1
            ]]
        ];

        $unwrapped = AggregationHelper::unwrapPrivateFieldAggregation($agg);
        $this->assertEquals($agg, $unwrapped);
    }
}
