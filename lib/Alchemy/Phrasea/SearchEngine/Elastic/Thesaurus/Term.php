<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

class Term implements TermInterface
{
    // So, this is a huuuge regex to match a group of words eventually followed
    // by another group of words in parenthesis. It also takes care of trimming
    // spaces.
//    const TERM_REGEX = '/^\s*(\w[^\(\)]*\w|\w)\s*(?:\(\s*([^\(\)]*[^\s\(\)])\s*\))?/u';
    //                       [_____term______]       (   [_____context_____]    )

    private $value;
    private $context;

    public function __construct($value, $context = null)
    {
        $this->value = (string) $value;
        if ($context) {
            $this->context = (string) $context;
        }
    }

    public function getValue()
    {
        return $this->value;
    }

    public function hasContext()
    {
        return $this->context !== null;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function __toString()
    {
        return self::dump($this);
    }

    public static function parse($string)
    {
//        preg_match(self::TERM_REGEX, $string, $matches);
//
//        return new self(
//            isset($matches[1]) ? $matches[1] : null,
//            isset($matches[2]) ? $matches[2] : null
//        );

        $term = $string;
        $context = '';
        if( ($p0 = strpos($string, '(')) !== false) {
            $term = substr($term, 0, $p0);
            $context = substr($string, $p0+1);
            if( ($p1 = strpos($context, ')')) !== false) {
                $context = substr($context, 0, $p1);
            }
        }
        if(($term = trim($term)) === '') {
            $term = null;
        }
        if(($context = trim($context)) === '') {
            $context = null;
        }
        if($term === null && $context !== null) {
            // special case "(foo)"
            $term = $context;
            $context = null;
        }

        return new self($term, $context);
    }

    public static function dump(TermInterface $term)
    {
        if ($term->hasContext()) {
            return sprintf('"%s" context:"%s"', $term->getValue(), $term->getContext());
        }

        return sprintf('"%s"', $term->getValue());
    }
}
