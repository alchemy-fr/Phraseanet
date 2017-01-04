<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\ValueChecker;
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
        $query_builder = function (array $fields) use ($context) {
            // Full text
            $index_fields = [];
            foreach (ValueChecker::filterByValueCompatibility($fields, $this->text) as $field) {
                foreach ($context->localizeField($field) as $f) {
                    $index_fields[] = $f;
                }
                foreach ($context->truncationField($field) as $f) {
                    $index_fields[] = $f;
                }
            }
            if (!$index_fields) {
                return null;
            }
            $query = [
                'multi_match' => [
                    'fields'   => $index_fields,
                    'query'    => $this->text,
                    'type'     => 'cross_fields',
                    'operator' => 'and',
                    'lenient'  => true,
                ]
            ];
            // Thesaurus
            $concept_queries = $this->buildConceptQueries($fields);
            foreach ($concept_queries as $concept_query) {
                $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
            }
            return $query;
        };

        // Unrestricted fields
        $query = $query_builder($context->getUnrestrictedFields());

        // Private fields
        $private_fields = $context->getPrivateFields();
        foreach (QueryHelper::wrapPrivateFieldQueries($private_fields, $query_builder) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        return $query;
    }

    public function __toString()
    {
        return sprintf('<text:%s>', Term::dump($this));
    }
}
