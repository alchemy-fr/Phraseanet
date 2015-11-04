<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class NodeTypes
{
    // Tree node types
    const QUERY               = '#query';
    const GROUP               = '#group';
    const AND_EXPR            = '#and';
    const OR_EXPR             = '#or';
    const EXCEPT_EXPR         = '#except';
    const LT_EXPR             = '#less_than';
    const GT_EXPR             = '#greater_than';
    const LTE_EXPR            = '#less_than_or_equal_to';
    const GTE_EXPR            = '#greater_than_or_equal_to';
    const EQUAL_EXPR          = '#equal_to';
    const FIELD_STATEMENT     = '#field_statement';
    const FIELD               = '#field';
    const VALUE               = '#value';
    const TERM                = '#thesaurus_term';
    const TEXT                = '#text';
    const CONTEXT             = '#context';
    const FLAG_STATEMENT      = '#flag_statement';
    const FLAG                = '#flag';
    const NATIVE_KEY_VALUE    = '#native_key_value';
    const NATIVE_KEY          = '#native_key';
    // Token types for leaf nodes
    const TOKEN_WORD          = 'word';
    const TOKEN_QUOTED_STRING = 'quoted';
    const TOKEN_RAW_STRING    = 'raw_quoted';
    const TOKEN_DATABASE      = 'database';
    const TOKEN_COLLECTION    = 'collection';
    const TOKEN_MEDIA_TYPE    = 'type';
    const TOKEN_RECORD_ID     = 'id';
    const TOKEN_TRUE          = 'true';
    const TOKEN_FALSE         = 'false';
}
