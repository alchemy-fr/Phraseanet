<?php

namespace Alchemy\Tests\Tools;

use Iterator;
use RuntimeException;
use SplFileObject;

class CsvFileIterator implements Iterator
{
    private $file;
    private $key = 0;
    private $current;
    private $control;

    public function __construct($file, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        $this->file = new SplFileObject($file, 'r');
        $this->file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $this->control = [$delimiter, $enclosure, $escape];
    }

    public function rewind(): void {
        $this->file->rewind();
        $this->current = $this->fgetcsv();
        $this->key = 0;
    }

    public function valid(): bool {
        return is_array($this->current);
    }

    public function key(): mixed {
        return $this->key;
    }

    public function current(): mixed {
        return $this->current;
    }

    public function next(): void {
        $this->current = $this->fgetcsv();
        $this->key++;
    }

    private function fgetcsv()
    {
        do {
            $line = $this->file->fgetcsv(...$this->control);
        }
        while ($line === [null] || (isset($line[0]) && '#' === substr($line[0], 0, 1)));

        return $line;
    }
}
