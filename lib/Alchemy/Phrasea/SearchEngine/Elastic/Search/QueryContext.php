<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;

class QueryContext
{
    private $locales;
    private $queryLocale;
    private $fields;

    public function __construct(array $locales, $queryLocale, array $fields = null)
    {
        $this->locales = $locales;
        $this->queryLocale = $queryLocale;
        $this->fields = $fields;
    }

    public function narrowToFields(array $fields)
    {
        // Ensure we are not escaping from original fields restrictions
        $fields = array_intersect($this->fields, $fields);
        if (!$fields) {
            throw new QueryException('Query narrowed to non available fields');
        }

        return new static($this->locales, $this->queryLocale, $fields);
    }

    public function getLocalizedFields()
    {
        if ($this->fields === null) {
            return $this->localizeField('*');
        }

        $fields = array();
        foreach ($this->fields as $field) {
            foreach ($this->localizeField($field) as $fields[]);
        }

        return $fields;
    }

    private function localizeField($field)
    {
        $fields = array();
        foreach ($this->locales as $locale) {
            $boost = ($locale === $this->queryLocale) ? '^5' : '';
            $fields[] = sprintf('caption.%s.%s%s', $field, $locale, $boost);
        }
        // TODO Put generic analyzers on main field instead of "light" sub-field
        $fields[] = sprintf('caption.%s.%s', $field, 'light^10');

        return $fields;
    }
}
