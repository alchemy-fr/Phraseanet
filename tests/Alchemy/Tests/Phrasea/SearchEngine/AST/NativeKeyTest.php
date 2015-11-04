<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

/**
 * @group unit
 * @group searchengine
 * @group ast
 */
class NativeKeyTest extends \PHPUnit_Framework_TestCase
{
    public function testSerialization()
    {
        $this->assertTrue(method_exists(NativeKey::class, '__toString'), 'Class does not have method __toString');
        $this->assertEquals('database', (string) NativeKey::database());
        $this->assertEquals('collection', (string) NativeKey::collection());
        $this->assertEquals('media_type', (string) NativeKey::mediaType());
        $this->assertEquals('record_identifier', (string) NativeKey::recordIdentifier());
    }

    public function testDatabaseQuery()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = NativeKey::database();
        $query = $key->buildQueryForValue('bar', $query_context->reveal());

        $expected = '{
            "term": {
                "databox_name": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testCollectionQuery()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = NativeKey::collection();
        $query = $key->buildQueryForValue('bar', $query_context->reveal());

        $expected = '{
            "term": {
                "collection_name": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testMediaTypeQuery()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = NativeKey::mediaType();
        $query = $key->buildQueryForValue('bar', $query_context->reveal());

        $expected = '{
            "term": {
                "type": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }

    public function testRecordIdentifierQuery()
    {
        $query_context = $this->prophesize(QueryContext::class);
        $key = NativeKey::recordIdentifier();
        $query = $key->buildQueryForValue('bar', $query_context->reveal());

        $expected = '{
            "term": {
                "record_id": "bar"
            }
        }';

        $this->assertEquals(json_decode($expected, true), $query);
    }
}
