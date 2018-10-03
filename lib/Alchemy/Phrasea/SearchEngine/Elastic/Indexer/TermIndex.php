<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
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

        $mapping->addStringField('raw_value')->disableAnalysis();
        $mapping->addStringField('value')
            ->setAnalyzer('general_light')
            ->addAnalyzedChild('strict', 'thesaurus_term_strict')
            ->addLocalizedChildren($this->locales);

        $mapping->addStringField('context')
            ->setAnalyzer('general_light')
            ->addAnalyzedChild('strict', 'thesaurus_term_strict')
            ->addLocalizedChildren($this->locales);

        $mapping->addStringField('path')
            ->setAnalyzer('thesaurus_path', 'indexing')
            ->setAnalyzer('keyword', 'searching')
            ->addRawChild();

        $mapping->addStringField('lang')->disableAnalysis();
        $mapping->addIntegerField('databox_id');

        return $mapping->getMapping();
    }
}
