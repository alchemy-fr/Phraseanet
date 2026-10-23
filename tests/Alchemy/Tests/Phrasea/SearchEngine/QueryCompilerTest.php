<?php

namespace Alchemy\Tests\Phrasea\SearchEngine;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryCompiler;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryVisitor;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Tests\Tools\CsvFileIterator;
use Hoa\Compiler;
use Hoa\File;

/**
 * @group unit
 * @group searchengine
 */
class QueryCompilerTest extends \PHPUnit\Framework\TestCase
{
    private $compiler;

    protected function setUp()
    {
        $grammar_path = 'grammar/query.pp';
        $project_root = '../../../../..';
        $grammar_path = realpath(implode('/', [__DIR__, $project_root, $grammar_path]));
        $parser = Compiler\Llk\Llk::load(new File\Read($grammar_path));

        $structure = $this->getMock(Structure::class);

        $queryVisitorFactory = function () use ($structure) {
            return new QueryVisitor($structure);
        };

        $thesaurus = $this->getMockBuilder(Thesaurus::class)
                          ->disableOriginalConstructor()
                          ->getMock();

        $this->compiler = new QueryCompiler($parser, $queryVisitorFactory, $thesaurus);
    }

    /**
     * @dataProvider queryProvider
     */
    public function testQueryParsing($query, $expected)
    {
        $this->assertEquals($expected, $this->compiler->parse($query)->dump());
    }

    public function queryProvider()
    {
        return new CsvFileIterator(sprintf('%s/resources/queries.csv', __DIR__), '|', '\'');
    }
}
