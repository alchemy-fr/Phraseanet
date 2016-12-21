<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndex;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\TermIndex;

class Index
{

    /**
     * @var array
     */
    private $analysis;

    /**
     * @var ElasticsearchOptions
     */
    private $options;

    /**
     * @var IndexLocator
     */
    private $indexLocator;

    /**
     * @param ElasticsearchOptions $options
     * @param IndexLocator $indexLocator
     */
    public function __construct(
        ElasticsearchOptions $options,
        IndexLocator $indexLocator
    ) {
        $this->options = $options;
        $this->indexLocator = $indexLocator;

        $this->buildDefaultAnalysis();
    }

    /**
     * Returns the index name (this is same value as defined in ElasticsearchOptions)
     *
     * @return string
     */
    public function getName()
    {
        return $this->options->getIndexName();
    }

    /**
     * @return array
     */
    public function getAnalysis()
    {
        return $this->analysis;
    }

    /**
     * @return ElasticsearchOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return RecordIndex
     */
    public function getRecordIndex()
    {
        return $this->indexLocator->getRecordIndex();
    }

    /**
     * @return TermIndex
     */
    public function getTermIndex()
    {
        return $this->indexLocator->getTermIndex();
    }

    private function buildDefaultAnalysis()
    {
        $this->analysis = [
            'analyzer' => [
                // General purpose, without removing stop word or stem: improve meaning accuracy
                'general_light' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    // TODO Maybe replace nfkc_normalizer + asciifolding with icu_folding
                    'filter' => ['nfkc_normalizer', 'asciifolding']
                ],
                'truncation_analyzer' => [
                    'type'      => 'custom',
                    'tokenizer' => 'truncation_tokenizer',
                    'filter'    => ['lowercase', 'stop', 'kstem']
                ],
                'truncation_analyzer#search' => [
                    'type'      => 'custom',
                    'tokenizer' => 'standard',
                    'filter'    => ['lowercase', 'stop', 'kstem']
                ],
                // Lang specific
                'fr_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    // better support for some Asian languages and using custom rules to break Myanmar and Khmer text.
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'elision', 'stop_fr', 'stem_fr']
                ],
                'en_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_en', 'stem_en']
                ],
                'de_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_de', 'stem_de']
                ],
                'nl_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_nl', 'stem_nl_override', 'stem_nl']
                ],
                'es_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_es', 'stem_es']
                ],
                'ar_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_ar', 'stem_ar']
                ],
                'ru_full' => [
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding', 'stop_ru', 'stem_ru']
                ],
                'cn_full' => [ // Standard chinese analyzer is not exposed
                    'type' => 'custom',
                    'tokenizer' => 'icu_tokenizer',
                    'filter' => ['nfkc_normalizer', 'asciifolding']
                ],
                // Thesaurus specific
                'thesaurus_path' => [
                    'type' => 'custom',
                    'tokenizer' => 'thesaurus_path'
                ],
                // Thesaurus strict term lookup
                'thesaurus_term_strict' => [
                    'type' => 'custom',
                    'tokenizer' => 'keyword',
                    'filter' => 'nfkc_normalizer'
                ]
            ],
            'tokenizer' => [
                'truncation_tokenizer' => [
                    "type" => "edgeNGram",
                    "min_gram" => "2",
                    "max_gram" => "15",
                    "token_chars" => [ "letter", "digit" ]
                ],
                'thesaurus_path' => [
                    'type' => 'path_hierarchy'
                ]
            ],
            'filter' => [
                'nfkc_normalizer' => [ // weißkopfseeadler => weisskopfseeadler, ١٢٣٤٥ => 12345.
                    'type' => 'icu_normalizer', // œ => oe, and use the fewest  bytes possible.
                    'name' => 'nfkc_cf' // nfkc_cf do the lowercase job too.
                ],
                'stop_fr' => [
                    'type' => 'stop',
                    'stopwords' => ['l', 'm', 't', 'qu', 'n', 's', 'j', 'd'],
                ],
                'stop_en' => [
                    'type' => 'stop',
                    'stopwords' => '_english_' // Use the Lucene default
                ],
                'stop_de' => [
                    'type' => 'stop',
                    'stopwords' => '_german_' // Use the Lucene default
                ],
                'stop_nl' => [
                    'type' => 'stop',
                    'stopwords' => '_dutch_' // Use the Lucene default
                ],
                'stop_es' => [
                    'type' => 'stop',
                    'stopwords' => '_spanish_' // Use the Lucene default
                ],
                'stop_ar' => [
                    'type' => 'stop',
                    'stopwords' => '_arabic_' // Use the Lucene default
                ],
                'stop_ru' => [
                    'type' => 'stop',
                    'stopwords' => '_russian_' // Use the Lucene default
                ],
                // See http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/analysis-stemmer-tokenfilter.html
                'stem_fr' => [
                    'type' => 'stemmer',
                    'name' => 'light_french',
                ],
                'stem_en' => [
                    'type' => 'stemmer',
                    'name' => 'english', // Porter stemming algorithm
                ],
                'stem_de' => [
                    'type' => 'stemmer',
                    'name' => 'light_german',
                ],
                'stem_nl' => [
                    'type' => 'stemmer',
                    'name' => 'dutch', // Snowball algo
                ],
                'stem_es' => [
                    'type' => 'stemmer',
                    'name' => 'light_spanish',
                ],
                'stem_ar' => [
                    'type' => 'stemmer',
                    'name' => 'arabic', // Lucene Arabic stemmer
                ],
                'stem_ru' => [
                    'type' => 'stemmer',
                    'name' => 'russian', // Snowball algo
                ],
                // Some custom rules
                'stem_nl_override' => [
                    'type' => 'stemmer_override',
                    'rules' => [
                        "fiets=>fiets",
                        "bromfiets=>bromfiets",
                        "ei=>eier",
                        "kind=>kinder"
                    ]
                ]
            ],
        ];
    }
}
