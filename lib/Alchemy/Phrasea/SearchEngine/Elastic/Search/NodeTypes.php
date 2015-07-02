<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class NodeTypes
{
    // Tree node types
    const QUERY               = '#query';
    const GROUP               = '#group';
    const IN_EXPR             = '#in';
    const AND_EXPR            = '#and';
    const OR_EXPR             = '#or';
    const EXCEPT_EXPR         = '#except';
    const LT_EXPR             = '#less_than';
    const GT_EXPR             = '#greater_than';
    const LTE_EXPR            = '#less_than_or_equal_to';
    const GTE_EXPR            = '#greater_than_or_equal_to';
    const EQUAL_EXPR          = '#equal_to';
    const FIELD               = '#field';
    const VALUE               = '#value';
    const TERM                = '#thesaurus_term';
    const TEXT                = '#text';
    const CONTEXT             = '#context';
    const COLLECTION          = '#collection';
    const DATABASE            = '#database';
    const IDENTIFIER          = '#id';
    // Token types for leaf nodes
    const TOKEN_WORD          = 'word';
    const TOKEN_QUOTED_STRING = 'quoted';
    const TOKEN_RAW_STRING    = 'raw_quoted';
}
