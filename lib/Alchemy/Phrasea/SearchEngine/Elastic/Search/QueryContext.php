<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class QueryContext
{
    private $fields;
    private $locales;
    private $queryLocale;

    public function __construct(array $fields, array $locales, $queryLocale)
    {
        $this->fields = $fields;
        $this->locales = $locales;
        $this->queryLocale = $queryLocale;
    }

    public function narrowToFields(array $fields)
    {
        // Ensure we are not escaping from original fields restrictions
        $fields = array_intersect($this->fields, $fields);

        return new static($fields, $this->locales, $this->queryLocale);
    }

    public function getLocalizedFields()
    {
        $fields = array();
        foreach ($this->fields as $field) {
            foreach ($this->locales as $locale) {
                $boost = ($locale === $this->queryLocale) ? '^5' : '';
                $fields[] = sprintf('caption.%s.%s%s', $field, $locale, $boost);
            }
            // TODO Put generic analyzers on main field instead of "light" sub-field
            $fields[] = sprintf('caption.%s.%s', $field, 'light^10');
        }

        return $fields;
    }
}
