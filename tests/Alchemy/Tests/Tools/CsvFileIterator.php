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

    public function __construct($file, $delimiter = ',', $enclosure = '"', $escape = '\\') {
        $this->file = new SplFileObject($file, 'r');
        $this->file->setFlags(SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $this->file->setCsvControl($delimiter, $enclosure, $escape);
    }

    public function rewind() {
        $this->file->rewind();
        $this->current = $this->fgetcsv();
        $this->key = 0;
    }

    public function valid() {
        return !$this->file->eof();
    }

    public function key() {
        return $this->key;
    }

    public function current() {
        return $this->current;
    }

    public function next() {
        $this->current = $this->fgetcsv();
        $this->key++;
    }

    private function fgetcsv()
    {
        do {
            $line = $this->file->fgetcsv();
        }
        while (isset($line[0]) && '#' === substr($line[0], 0, 1));

        return $line;
    }
}
