<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class Escaper
{
    public function escapeWord($value)
    {
        // Strip double quotes from values to prevent broken queries
        // TODO escape double quotes when it will be supported in query parser
        $value = str_replace('/["\(\)\[\]]+/u', ' ', $value);

        if (preg_match('/[\s\(\)\[\]]/u', $value)) {
            return sprintf('"%s"', $value);
        }

        return $value;
    }
}
