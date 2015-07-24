<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\Field as ASTField;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;

/**
 * @todo Check for private fields and only search on them if allowed
 */
class QueryContext
{
    /** @var Structure */
    private $structure;
    /** @var array */
    private $locales;
    /** @var string */
    private $queryLocale;
    /** @var array */
    private $fields;

    public function __construct(Structure $structure, array $locales, $queryLocale, array $fields = null)
    {
        $this->structure = $structure;
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
        }

        return new static($this->structure, $this->locales, $this->queryLocale, $fields);
    }

    public function getUnrestrictedFields()
    {
        // TODO Restore search optimization by using "caption_all" field
        // (only when $this->fields is null)
        $fields = $this->structure->getUnrestrictedFields();
        if ($this->fields !== null) {
            $fields = array_intersect_key($fields, array_flip($this->fields));
        }

        return array_values($fields);
    }

    public function getPrivateFields()
    {
        $fields = $this->structure->getPrivateFields();
        if ($this->fields !== null) {
            $fields = array_intersect_key($fields, array_flip($this->fields));
        }

        return array_values($fields);
    }

    public function get($name)
    {
        if ($name instanceof ASTField) {
            $name = $name->getValue();
        }
        $field = $this->structure->get($name);
        if (!$field) {
            return null;
        }
        return $field;
    }

    /**
     * @todo Maybe we should put this logic in Field class?
     */
    public function localizeField(Field $field)
    {
        $index_field = $field->getIndexField();
        if ($field->getType() === Mapping::TYPE_STRING) {
            return $this->localizeFieldName($index_field);
        } else {
            return [$index_field];
        }
    }

    private function localizeFieldName($field)
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
