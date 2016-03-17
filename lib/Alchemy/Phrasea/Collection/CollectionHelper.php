<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection;

use Assert\Assertion;

class CollectionHelper
{
    private function __construct()
    {
    }

    /**
     * @param \collection[] $collections
     * @return \collection[]
     */
    public static function sort($collections)
    {
        Assertion::allIsInstanceOf($collections, \collection::class);

        if ($collections instanceof \Traversable) {
            $collections = iterator_to_array($collections);
        }

        usort($collections, function (\collection $left, \collection $right) {
            return ($left->get_ord() < $right->get_ord()) ? -1 : (($left->get_ord() < $right->get_ord()) ? 1 : 0);
        });

        return $collections;
    }
}
