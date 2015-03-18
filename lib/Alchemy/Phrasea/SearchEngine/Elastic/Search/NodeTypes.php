<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class NodeTypes
{
    // Tree node types
    const QUERY         = '#query';
    const GROUP         = '#group';
    const IN_EXPR       = '#in';
    const AND_EXPR      = '#and';
    const OR_EXPR       = '#or';
    const EXCEPT_EXPR   = '#except';
    const FIELD         = '#field';
    const TERM          = '#thesaurus_term';
    const TEXT          = '#text';
    const CONTEXT       = '#context';
    const COLLECTION    = '#collection';
    // Token types for leaf nodes
    const TOKEN_WORD    = 'word';
    const TOKEN_STRING  = 'string';
}
