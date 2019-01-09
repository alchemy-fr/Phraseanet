<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Collection;

use Assert\Assertion;

final class CollectionHelper
{
    /**
     * @param \App\Utils\collection[] $collections
     * @return \App\Utils\collection[]
     */
    public static function sort($collections)
    {
        Assertion::allIsInstanceOf($collections, \App\Utils\collection::class);

        if ($collections instanceof \Traversable) {
            $collections = iterator_to_array($collections);
        }

        usort($collections, function (\App\Utils\collection $left, \App\Utils\collection $right) {
            if ($left->get_ord() === $right->get_ord()) {
                return 0;
            }

            return $left->get_ord() < $right->get_ord() ? -1 : 1;
        });

        return $collections;
    }
}
