<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\NativeKey;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

#[\PHPUnit\Framework\Attributes\Group('unit')]
#[\PHPUnit\Framework\Attributes\Group('searchengine')]
#[\PHPUnit\Framework\Attributes\Group('ast')]
class NativeKeyTest extends \PHPUnit\Framework\TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use \Prophecy\PhpUnit\ProphecyTrait;
    public function testSerialization()
    {
        $this->assertTrue(method_exists(NativeKey::class, '__toString'), 'Class does not have method __toString');
        $this->assertEquals('database', (string) NativeKey::database());
        $this->assertEquals('collection', (string) NativeKey::collection());
        $this->assertEquals('media_type', (string) NativeKey::mediaType());
        $this->assertEquals('record_identifier', (string) NativeKey::recordIdentifier());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('keyProvider')]
    public function testGetIndexField($key, $field)
    {
        $query_context = $this->prophesize(QueryContext::class);
        $this->assertEquals($key->getIndexField($query_context->reveal()), $field);
    }

    public static function keyProvider()
    {
        return [
            [NativeKey::database(),         'databox_name'],
            [NativeKey::collection(),       'collection_name'],
            [NativeKey::mediaType(),        'type'],
            [NativeKey::recordIdentifier(), 'record_id']
        ];
    }
}
