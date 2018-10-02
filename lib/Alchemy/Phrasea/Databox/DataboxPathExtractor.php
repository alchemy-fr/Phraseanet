<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox;

class DataboxPathExtractor
{
    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    /**
     * @param string $type
     * @return array
     */
    public function extractPaths($type = null)
    {
        $paths = [];

        foreach ($this->appbox->get_databoxes() as $databox) {
            if ($type === 'xsendfile') {
                $paths[] = (string) $databox->get_sxml_structure()->path;
            }

            foreach ($databox->get_subdef_structure()->getSubdefGroup('video') as $subdef) {
                $paths[] = $subdef->get_path();
            }
        }

        return array_filter(array_unique($paths));
    }
}
