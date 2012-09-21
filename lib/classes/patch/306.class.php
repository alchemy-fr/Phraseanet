<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_306 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.0.6';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$databox, Application $app)
    {
        $dom = $databox->get_dom_structure();
        $xpath = $databox->get_xpath_structure();
        $res = $xpath->query('/record/subdefs/preview/type');

        foreach ($res as $type) {
            if ($type->nodeValue == 'video') {
                $preview = $type->parentNode;

                $to_add = array(
                    'acodec'  => 'faac',
                    'vcodec'  => 'libx264',
                    'bitrate' => '700'
                );
                foreach ($to_add as $k => $v) {
                    $el = $dom->createElement($k);
                    $el->appendChild($dom->createTextNode($v));
                    $preview->appendChild($el);
                }
            }
        }

        $databox->saveStructure($dom);

        return true;
    }
}
