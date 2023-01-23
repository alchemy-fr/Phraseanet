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
    const MATCH_EXPR          = '#match_expression';
    const FIELD_STATEMENT     = '#field_statement';
    const FIELD               = '#field';
    const FIELD_KEY           = '#field_key';
    const VALUE               = '#value';
    const TERM                = '#thesaurus_term';
    const TEXT                = '#text';
    const CONTEXT             = '#context';
    const METADATA_KEY        = '#meta_key';
    const FLAG_STATEMENT      = '#flag_statement';
    const FLAG                = '#flag';
    const NATIVE_KEY          = '#native_key';
    const TIMESTAMP_KEY       = '#timestamp_key';
    const GEOLOCATION_KEY     = '#geolocation_key';
    // Token types for leaf nodes
    const TOKEN_WORD          = 'word';
    const TOKEN_QUOTED_STRING = 'quoted';
    const TOKEN_RAW_STRING    = 'raw_quoted';
    const TOKEN_DATABASE      = 'database';
    const TOKEN_COLLECTION    = 'collection';
    const TOKEN_SHA256        = 'sha256';
    const TOKEN_UUID          = 'uuid';
    const TOKEN_MEDIA_TYPE    = 'type';
    const TOKEN_RECORD_ID     = 'id';
    const TOKEN_CREATED_ON    = 'created_on';
    const TOKEN_UPDATED_ON    = 'updated_on';
    const TOKEN_TRUE          = 'true';
    const TOKEN_FALSE         = 'false';
}
