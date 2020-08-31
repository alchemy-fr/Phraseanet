<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping\RawFieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\MappingBuilder;
use Alchemy\Phrasea\SearchEngine\Elastic\MappingProvider;

class TermIndex implements MappingProvider
{
    /**
     * @var string[]
     */
    private $locales;

    /**
     * @param string[] $locales
     */
    public function __construct(array $locales)
    {
        $this->locales = $locales;
    }

    /**
     * @return \Alchemy\Phrasea\SearchEngine\Elastic\Mapping
     */
    public function getMapping()
    {
        $mapping = new MappingBuilder();

        $mapping->addKeywordField('raw_value');
        $mapping->addTextField('value')
            ->setAnalyzer('general_light')
            ->addAnalyzedChild('strict', 'thesaurus_term_strict')
            ->addLocalizedChildren($this->locales);

        $mapping->addTextField('context')
            ->setAnalyzer('general_light')
            ->addAnalyzedChild('strict', 'thesaurus_term_strict')
            ->addLocalizedChildren($this->locales);

        $mapping->addTextField('path')
            ->setAnalyzer('thesaurus_path', 'indexing')
            ->setAnalyzer('keyword', 'searching')
            // ->addChild(new RawFieldMapping($this->getType()));
            ->addRawChild();

        $mapping->addKeywordField('lang');
        $mapping->addIntegerField('databox_id');

        return $mapping->getMapping();
    }
}
