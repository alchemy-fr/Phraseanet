<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class Key
{
    const TYPE_DATABASE = 'database';
    const TYPE_COLLECTION = 'collection';
    const TYPE_MEDIA_TYPE = 'media_type';
    const TYPE_RECORD_IDENTIFIER = 'record_identifier';

    private $type;
    private $key;

    public static function database()
    {
        return new self(self::TYPE_DATABASE, 'databox_name');
    }

    public static function collection()
    {
        return new self(self::TYPE_COLLECTION, 'collection_name');
    }

    public static function mediaType()
    {
        return new self(self::TYPE_MEDIA_TYPE, 'type');
    }

    public static function recordIdentifier()
    {
        return new self(self::TYPE_RECORD_IDENTIFIER, 'record_id');
    }

    private function __construct($type, $key)
    {
        $this->type = $type;
        $this->key = $key;
    }

    public function getIndexField()
    {
        return $this->key;
    }

    public function __toString()
    {
        return $this->type;
    }
}
