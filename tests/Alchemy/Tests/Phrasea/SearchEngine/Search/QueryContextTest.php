<?php

namespace Alchemy\Tests\Phrasea\SearchEngine\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

class QueryContextTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldNarrowing()
    {
        $structure = $this->prophesize(Structure::class)->reveal();
        $available_locales = ['ab', 'cd', 'ef'];
        $context = new QueryContext($structure, $available_locales, 'fr');
        $narrowed = $context->narrowToFields(['some_field']);
        $this->assertEquals(['some_field'], $narrowed->getFields());
    }
}
