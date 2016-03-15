<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Cocur\Slugify\Slugify;
use Cocur\Slugify\SlugifyInterface;
use Transliterator;

class StringUtils
{
    /**
     * @var SlugifyInterface|null
     */
    private static $slugifier;

    private static $transliterator;

    /**
     * Prevent instantiation of the class
     */
    private function __construct()
    {
    }

    public static function setSlugify(SlugifyInterface $slugify = null)
    {
        self::$slugifier = $slugify;
    }

    /**
     * @return SlugifyInterface
     */
    private static function getSlugifier()
    {
        if (null === self::$slugifier) {
            self::$slugifier = new Slugify();
        }

        return self::$slugifier;
    }

    public static function slugify($string, $separator = '-')
    {
        return self::getSlugifier()->slugify($string, $separator);
    }

    public static function asciiLowerFold($string)
    {
        // '北京' -> 'bei jing'

        if (!self::$transliterator) {
            $id = 'Any-Latin; Latin-ASCII; Any-Lower';
            self::$transliterator = Transliterator::create($id);
        }

        return self::$transliterator->transliterate($string);
    }
}
