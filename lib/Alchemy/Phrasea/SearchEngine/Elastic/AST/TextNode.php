<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class TextNode extends AbstractTermNode implements ContextAbleInterface
{
    /**
     * Merge two text nodes by concatenating their content.
     *
     * /!\ Text contexts are lost in the process
     *
     * @param  TextNode $a First node
     * @param  TextNode $b The other one
     * @return TextNode    Merged text node
     */
    public static function merge(TextNode $a, TextNode $b)
    {
        return new self(sprintf('%s%s', $a->getValue(), $b->getValue()));
    }

    /**
     * Creates a new text node with the same content and the provided context.
     *
     * /!\ The original node context will not be preserved (ie. not merged).
     *
     * @param  Context $context Context to add on the new node
     * @return TextNode         A text node with a context
     */
    public function withContext(Context $context)
    {
        return new self($this->getValue(), $context);
    }

    public function buildQuery(QueryContext $context)
    {
        $query_builder = function (array $fields) {
            return [
                'multi_match' => [
                    'fields'   => $fields,
                    'query'    => $this->text,
                    'operator' => 'and',
                ]
            ];
        };

        $fields = $context->getLocalizedFields();
        $query = count($fields) ? $query_builder($fields) : null;

        foreach (QueryHelper::buildPrivateFieldQueries($context, $query_builder) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        foreach ($this->buildConceptQueries($context) as $concept_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<text:%s>', Term::dump($this));
    }
}
