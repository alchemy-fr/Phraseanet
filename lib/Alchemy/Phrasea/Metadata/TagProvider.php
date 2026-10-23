<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use PHPExiftool\PHPExiftool;

class TagProvider
{
    /**
     * Lists the known tags, without instantiating them.
     *
     * @return array [lowercased namespace => [lowercased tag name => ['tagname' => ..., 'classname' => ..., 'namespace' => ...]]]
     */
    public function getLookupTable()
    {
        $table = [];

        foreach (TagFactory::getExiftoolIndex() as $id => $classname) {
            $parts = explode(':', $id, 2);
            if (count($parts) !== 2) {
                continue;
            }

            list($namespace, $tagname) = $parts;

            $table[strtolower($namespace)][strtolower($tagname)] = [
                'tagname'   => $tagname,
                'classname' => '\\' . PHPExiftool::ROOT_NAMESPACE . '\\' . PHPExiftool::SUBDIR . '\\' . $classname,
                'namespace' => $namespace,
            ];
        }

        foreach (TagFactory::getPhraseanetClasses() as $tagname => $classname) {
            if ('no-source' === $tagname) {
                continue;
            }

            /** @var TagInterface $tag */
            $tag = new $classname();

            $table['phraseanet'][$tagname] = [
                'tagname'   => $tag->getName(),
                'classname' => '\\' . $classname,
                'namespace' => 'Phraseanet',
            ];
        }

        return $table;
    }
}
