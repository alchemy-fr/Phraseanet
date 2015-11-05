<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Assert\Assertion;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\FieldKey;
use Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue\Key;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class RangeExpression extends Node
{
    private $key;
    private $field_cache;
    private $lower_bound;
    private $lower_inclusive;
    private $higher_bound;
    private $higher_inclusive;

    public static function lessThan(Key $key, $bound)
    {
        return new self($key, $bound, false);
    }

    public static function lessThanOrEqual(Key $key, $bound)
    {
        return new self($key, $bound, true);
    }

    public static function greaterThan(Key $key, $bound)
    {
        return new self($key, null, false, $bound, false);
    }

    public static function greaterThanOrEqual(Key $key, $bound)
    {
        return new self($key, null, false, $bound, true);
    }

    public function __construct(Key $key, $lb, $li = false, $hb = null, $hi = false)
    {
        Assertion::nullOrScalar($lb);
        Assertion::boolean($li);
        Assertion::nullOrScalar($hb);
        Assertion::boolean($hi);
        $this->key = $key;
        $this->lower_bound = $lb;
        $this->lower_inclusive = $li;
        $this->higher_bound = $hb;
        $this->higher_inclusive = $hi;
    }

    public function buildQuery(QueryContext $context)
    {
        $params = array();
        if ($this->lower_bound !== null) {
            if (!$this->isValueCompatible($this->lower_bound, $context)) {
                return;
            }
            if ($this->lower_inclusive) {
                $params['lte'] = $this->lower_bound;
            } else {
                $params['lt'] = $this->lower_bound;
            }
        }
        if ($this->higher_bound !== null) {
            if (!$this->isValueCompatible($this->higher_bound, $context)) {
                return;
            }
            if ($this->higher_inclusive) {
                $params['gte'] = $this->higher_bound;
            } else {
                $params['gt'] = $this->higher_bound;
            }
        }

        $query = [];
        $query['range'][$this->getIndexField($context)] = $params;

        return $this->postProcessQuery($query, $context);
    }

    private function isValueCompatible($value, QueryContext $context)
    {
        if ($this->key instanceof FieldKey) {
            return $this->getField($context)->isValueCompatible($value);
        } else {
            return true;
        }
    }

    private function getIndexField(QueryContext $context)
    {
        if ($this->key instanceof FieldKey) {
            return $this->getField($context)->getIndexField();
        } else {
            return $this->key->getIndexField();
        }
    }

    private function postProcessQuery($query, QueryContext $context)
    {
        if ($this->key instanceof FieldKey) {
            $field = $this->getField($context);
            return QueryHelper::wrapPrivateFieldQuery($field, $query);
        } else {
            return $query;
        }
    }

    private function getField(QueryContext $context)
    {
        if ($this->field_cache === null) {
            $this->field_cache = $context->get($this->key->getValue());
        }
        if ($this->field_cache === null) {
            throw new QueryException(sprintf('Field "%s" does not exist', $this->key->getValue()));
        }
        return $this->field_cache;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        $string = '';
        if ($this->lower_bound !== null) {
            if ($this->lower_inclusive) {
                $string .= sprintf(' lte="%s"', $this->lower_bound);
            } else {
                $string .= sprintf(' lt="%s"', $this->lower_bound);
            }
        }
        if ($this->higher_bound !== null) {
            if ($this->higher_inclusive) {
                $string .= sprintf(' gte="%s"', $this->higher_bound);
            } else {
                $string .= sprintf(' gt="%s"', $this->higher_bound);
            }
        }

        return sprintf('<range:%s%s>', $this->key, $string);
    }
}
