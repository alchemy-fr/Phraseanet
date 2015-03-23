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
        if (is_array($this->fields)) {
            // Ensure we are not escaping from original fields restrictions
            $fields = array_intersect($this->fields, $fields);
            if (!$fields) {
                throw new QueryException('Query narrowed to non available fields');
            }
        } else {
            $fields = null;
        }

        return new static($this->locales, $this->queryLocale, $fields);
    }

    public function getLocalizedFields()
    {
        // TODO Private fields handling
        if ($this->fields === null) {
            return $this->localizeField('caption_all');
        }

        $fields = array();
        foreach ($this->fields as $field) {
            foreach ($this->localizeField(sprintf('caption.%s', $field)) as $fields[]);
        }

        return $fields;
    }

    private function localizeField($field)
    {
        $fields = array();
        foreach ($this->locales as $locale) {
            $boost = ($locale === $this->queryLocale) ? '^5' : '';
            $fields[] = sprintf('%s.%s%s', $field, $locale, $boost);
        }
        // TODO Put generic analyzers on main field instead of "light" sub-field
        $fields[] = sprintf('%s.light^10', $field);

        return $fields;
    }

    public function getFields()
    {
        return $this->fields;
    }
}
