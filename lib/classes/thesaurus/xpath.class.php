<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class thesaurus_xpath extends DOMXPath
{
    /**
     *
     * @var array
     */
    protected static $r = array();

    /**
     *
     * @param  string      $xquery
     * @param  DOMNode     $context_node
     * @param  string      $context_path
     * @return DOMNodeList
     */
    public function cache_query($xquery, DOMNode $context_node = NULL, $context_path = '')
    {
        $context_path .= $xquery;

        if ( ! array_key_exists($context_path, self::$r)) {
            self::$r[$context_path] = $context_node ?
                parent::query($xquery, $context_node) : parent::query($xquery);
        }

        return(self::$r[$context_path]);
    }
}

