<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class NodeTypes
{
    // Tree node types
    const QUERY         = '#query';
    const IN_EXPR       = '#in';
    const AND_EXPR      = '#and';
    const OR_EXPR       = '#or';
    const EXCEPT_EXPR   = '#except';
    const FIELD         = '#field';
    const TEXT          = '#text';
    // Token types for leaf nodes
    const TOKEN_WORD    = 'word';
    const TOKEN_STRING  = 'string';
}
