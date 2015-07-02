<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Search;

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
        $context = new QueryContext($structure, [], $available_locales, 'fr');
        $narrowed = $context->narrowToFields(['some_field']);
        $this->assertEquals(['some_field'], $narrowed->getFields());
    }

    public function testFieldNormalization()
    {
        $public_field = new Field('foo', Mapping::TYPE_STRING, ['private' => false]);
        $restricted_field = new Field('bar', Mapping::TYPE_STRING, ['private' => true]);
        $structure = $this->prophesize(Structure::class);
        $structure->get('foo')->willReturn($public_field);
        $structure->get('bar')->willReturn($restricted_field);

        $context = new QueryContext($structure->reveal(), [], [], 'fr');
        $this->assertEquals('caption.foo', $context->normalizeField('foo'));
        $this->assertEquals('private_caption.bar', $context->normalizeField('bar'));
    }
}
