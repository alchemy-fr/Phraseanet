<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryPostProcessor;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;
use Assert\Assertion;

class FieldKey implements Key, QueryPostProcessor
{
    private $name;
    private $field_cache = [];

    public function __construct($name)
    {
        Assertion::string($name);
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getIndexField(QueryContext $context, $raw = false)
    {
        return $this->getField($context)->getIndexField($raw);
    }

    public function getFieldType(QueryContext $context)
    {
        return $this->getField($context)->getType();
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return ValueChecker::isValueCompatible($this->getField($context), $value);
    }

    public function postProcessQuery($query, QueryContext $context)
    {
        $field = $this->getField($context);
        return QueryHelper::wrapPrivateFieldQuery($field, $query);
    }

    private function getField(QueryContext $context)
    {
        $hash = spl_object_hash($context);
        if (!isset($this->field_cache[$hash])) {
            $this->field_cache[$hash] = $context->get($this->name);
        }
        $field = $this->field_cache[$hash];
        if ($field === null) {
            throw new QueryException(sprintf('Field "%s" does not exist', $this->name));
        }
        return $field;
    }

    public function clearCache()
    {
        $this->field_cache = [];
    }

    public function __toString()
    {
        return sprintf('field.%s', $this->name);
    }
}
