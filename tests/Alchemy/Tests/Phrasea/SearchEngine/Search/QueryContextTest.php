<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

/**
 * @group unit
 * @group searchengine
 */
class QueryContextTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldNarrowing()
    {
        $structure = $this->prophesize(Structure::class)->reveal();
        $available_locales = ['ab', 'cd', 'ef'];
        $context = new QueryContext(null, $structure, $available_locales, 'fr');
        $narrowed = $context->narrowToFields(['some_field']);
        $this->assertEquals(['some_field'], $narrowed->getFields());
    }

    public function testGetUnrestrictedFields()
    {
        $foo_field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => false]);
        $bar_field = new Field('bar', FieldMapping::TYPE_STRING, ['private' => false]);
        $structure = $this->prophesize(Structure::class);
        $structure->getUnrestrictedFields()->willReturn([
            'foo' => $foo_field,
            'bar' => $bar_field
        ]);

        $context = new QueryContext(null, $structure->reveal(), [], 'fr');
        $this->assertEquals([$foo_field, $bar_field], $context->getUnrestrictedFields());

        $narrowed_context = new QueryContext(null, $structure->reveal(), [], 'fr', ['foo']);
        $this->assertEquals([$foo_field], $narrowed_context->getUnrestrictedFields());
    }

    public function testGetPrivateFields()
    {
        $foo_field = new Field('foo', FieldMapping::TYPE_STRING, ['private' => true]);
        $bar_field = new Field('bar', FieldMapping::TYPE_STRING, ['private' => true]);
        $structure = $this->prophesize(Structure::class);
        $structure->getPrivateFields()->willReturn([
            'foo' => $foo_field,
            'bar' => $bar_field
        ]);

        $context = new QueryContext(null, $structure->reveal(), [], 'fr');
        $this->assertEquals([$foo_field, $bar_field], $context->getPrivateFields());

        $narrowed_context = new QueryContext(null, $structure->reveal(), [], 'fr', ['foo']);
        $this->assertEquals([$foo_field], $narrowed_context->getPrivateFields());
    }
}
