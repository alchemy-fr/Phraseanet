<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper;
use Symfony\Component\HttpFoundation\Request;

class Printer extends RecordHelper
{
    protected $flatten_groupings = true;

    /**
     *
     * @param Application $app
     * @param Request     $Request
     *
     * @return Helper
     */
    public function __construct(Application $app, Request $Request)
    {
        parent::__construct($app, $Request);

        $grep = function (\record_adapter $record) {
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
