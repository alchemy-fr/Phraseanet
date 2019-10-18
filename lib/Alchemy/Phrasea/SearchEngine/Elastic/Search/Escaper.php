<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class Escaper
{
    public function quoteWord($value)
    {
        return '"' . $this->escapeRaw($value) . '"';
    }

    public function escapeWord($value)
    {
        // Strip double quotes from values to prevent broken queries
        $value = str_replace("\"", "\\\"", $value);
        if (preg_match('/[\s\(\)\[\]]/u', $value)) {
            $value = sprintf('r"%s"', $value);
        }

        return $value;
    }

    public function escapeRaw($value)
    {
        return preg_replace('/"|\\\\/u', '\\\\$0', $value);
    }
}
