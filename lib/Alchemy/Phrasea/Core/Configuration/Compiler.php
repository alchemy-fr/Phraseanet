<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

class Compiler
{
    private $line;

    public function __construct($line = PHP_EOL)
    {
        $this->line = $line;
    }

    /**
     * Compiles data to PHP code
     *
     * @param array $data
     *
     * @return string
     */
    public function compile(array $data)
    {
        return '<?php' . $this->addLine() . 'return ' . $this->doCompile($data) . ';' . $this->addLine();
    }

    private function doCompile(array $data, $offset = 0)
    {
        $out = '[' . $this->addLine();

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $value = get_object_vars($value);
            }

            $assoc = $this->addIndentation($offset + 1) . (is_int($key) ? '' : $this->quote($key) . ' => ');

            if (is_array($value)) {
                $out .= $assoc . $this->doCompile($value, $offset + 1) . ',' . $this->addLine();
            } else {
                $out .= $assoc . $this->quoteValue($value) . ',' . $this->addLine();
            }
        }

        $out .= $this->addIndentation($offset) . ']';

        return $out;
    }

    private function quote($string)
    {
        return "'".str_replace("'", "\'", $string)."'";
    }

    private function quoteValue($string)
    {
        if (is_int($string)) {
            return $string;
        }
        if (true === $string) {
            return 'true';
        }
        if (false === $string) {
            return 'false';
        }
        if (null === $string) {
            return 'null';
        }

        return $this->quote($string);
    }

    private function addLine()
    {
        return $this->line;
    }

    private function addIndentation($quantity)
    {
        return str_repeat(' ', $quantity * 4);
    }
}
