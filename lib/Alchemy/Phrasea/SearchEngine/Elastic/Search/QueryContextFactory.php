<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\LimitedStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;

class QueryContextFactory
{
    private $structure;

    /**
     * QueryContextFactory constructor.
     * @param Structure|callable $structure
     * @param array $locales
     * @param $current_locale
     */
    public function __construct($structure, array $locales, $current_locale)
    {
        $this->structure = $structure;
        $this->locales = $locales;
        $this->current_locale = $current_locale;
    }

    /**
     * @return Structure
     */
    public function getStructure()
    {
        if (!($this->structure instanceof Structure)) {
            $this->structure = call_user_func($this->structure);
        }

        return $this->structure;
    }


    public function createContext(SearchEngineOptions $options = null)
    {
        $structure = $options
            ? $this->getLimitedStructure($options)
            : $this->getStructure();

        $context = new QueryContext($options, $structure, $this->locales, $this->current_locale);

        if ($options) {
            $fields = $this->getSearchedFields($options);
            $context = $context->narrowToFields($fields);
        }

        return $context;
    }

    private function getSearchedFields(SearchEngineOptions $options)
    {
        $fields = [];

        foreach ($options->getFields() as $field) {
            $fields[] = $field->get_name();
        }

        return $fields;
    }

    public function getLimitedStructure(SearchEngineOptions $options)
    {
        return new LimitedStructure($this->getStructure(), $options);
    }
}
