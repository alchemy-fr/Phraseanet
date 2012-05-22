<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper,
    Symfony\Component\HttpFoundation\Request;

/**
 * Edit Record Helper
 * This object handles /edit/ request and filters records that user can edit
 *
 * It prepares metadatas, databases structures.
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Printer extends RecordHelper
{
    protected $flatten_groupings = true;

    /**
     *
     * @param  \Alchemy\Phrasea\Core $core
     * @return Printer
     */
    public function __construct(Core $core, Request $Request)
    {
        parent::__construct($core, $Request);

        $grep = function(\record_adapter $record) {

                try {
                    return $record->get_thumbnail()->get_type() == \media_subdef::TYPE_IMAGE ||
                        $record->get_preview()->get_type() == \media_subdef::TYPE_IMAGE;
                } catch (\Exception $e) {
                    return false;
                }
            };

        $this->grep_records($grep);
    }

    public function get_count_preview()
    {
        $n = 0;
        foreach ($this->get_elements() as $element) {
            try {
                $element->get_preview()->get_type() == \media_subdef::TYPE_IMAGE;
                $n ++;
            } catch (\Exception $e) {

            }
        }

        return $n;
    }

    public function get_count_thumbnail()
    {
        $n = 0;
        foreach ($this->get_elements() as $element) {
            try {
                $element->get_thumbnail()->get_type() == \media_subdef::TYPE_IMAGE;
                $n ++;
            } catch (\Exception $e) {

            }
        }

        return $n;
    }
}
