<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;

class FieldEqualsExpression extends Node
{
    private $field;
    private $value;

    public function __construct(Field $field, $value)
    {
        $this->field = $field;
        $this->value = $value;
    }

    public function buildQuery(QueryContext $context)
    {
        $structure_field = $context->get($this->field);
        if (!$structure_field) {
            throw new QueryException(sprintf('Field "%s" does not exist', $this->field->getValue()));
        }
        if (!$structure_field->isValueCompatible($this->value)) {
            return null;
        }

        $query = [
            'term' => [
                $structure_field->getIndexField(true) => $this->value
            ]
        ];

        return QueryHelper::wrapPrivateFieldQuery($structure_field, $query);
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('(%s == <value:"%s">)', $this->field, $this->value);
    }
}
