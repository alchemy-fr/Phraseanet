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
class patch_310 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.1.0';

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

    public function apply(base $databox, Application $app)
    {
        $dom_structure = $databox->get_dom_structure();
        $sx_structure = $databox->get_sxml_structure();

        $subdefgroups = $sx_structure->xpath('//subdefgroup');

        if (count($subdefgroups) > 0) {
            return;
        }

        $subdefs = $sx_structure->xpath('/record/subdefs');

        if (count($subdefs) > 1)
            exit('La structure semble erronnée, veuillez la corriger');

        $new_subefs_node = $dom_structure->createElement('subdefs');

        $subdefs_groups = array();

        foreach ($subdefs[0] as $k => $v) {
            $type = isset($v->type) ? (string) $v->type : 'image';

            if ($type == 'image')
                $media = 'image';
            elseif ($type == 'audio') {
                if ($v->method == 'MP3')
                    $media = "audio";
                else
                    $media = "image";
            } elseif ($type == 'video') {
                if ($v->method == 'AnimGIF')
                    $media = "gif";
                elseif ($v->method == 'JPG')
                    $media = "image";
                else
                    $media = 'video';
            }

            if ( ! isset($subdefs_groups[$type])) {
                $subdefs_groups[$type] = $dom_structure->createElement('subdefgroup');
                $subdefs_groups[$type]->setAttribute('name', $type);
            }

            $dom_subdef = $dom_structure->createElement('subdef');
            $class = ($k == 'preview' ? 'preview' : 'thumbnail');
            $dom_subdef->setAttribute('class', $class);
            $dom_subdef->setAttribute('name', $k);
            $dom_subdef->setAttribute('downloadable', 'true');

            foreach ($v as $tag => $value) {
                if (in_array($tag, array('type', 'name')))
                    continue;

                $dom_element = $dom_structure->createElement($tag, $value);
                $dom_subdef->appendChild($dom_element);
            }
            $dom_element = $dom_structure->createElement('mediatype', $media);
            $dom_subdef->appendChild($dom_element);

            if ($media == 'video') {
                $dom_element = $dom_structure->createElement('threads', '1');
                $dom_subdef->appendChild($dom_element);
            }

            //preview, thumbnail et thumbnailGIF
            if ($k == 'preview') {
                $dom_element =
                    $dom_structure->createElement('label', 'Prévisualisation');
                $dom_element->setAttribute('xml:lang', 'fr');
                $dom_subdef->appendChild($dom_element);
                $dom_element = $dom_structure->createElement('label', 'Preview');
                $dom_element->setAttribute('lang', 'en');
                $dom_subdef->appendChild($dom_element);
            } elseif ($k == 'thumbnailGIF') {
                $dom_element = $dom_structure->createElement('label', 'Animation GIF');
                $dom_element->setAttribute('lang', 'fr');
                $dom_subdef->appendChild($dom_element);
                $dom_element = $dom_structure->createElement('label', 'GIF animation');
                $dom_element->setAttribute('lang', 'en');
                $dom_subdef->appendChild($dom_element);
            } else {
                $dom_element = $dom_structure->createElement('label', 'Imagette');
                $dom_element->setAttribute('lang', 'fr');
                $dom_subdef->appendChild($dom_element);
                $dom_element = $dom_structure->createElement('label', 'Thumbnail');
                $dom_element->setAttribute('lang', 'en');
                $dom_subdef->appendChild($dom_element);
            }

            $subdefs_groups[$type]->appendChild($dom_subdef);
        }

        foreach ($subdefs_groups as $type => $node)
            $new_subefs_node->appendChild($node);

        $record = $dom_structure->documentElement;

        $record->replaceChild(
            $new_subefs_node, $record->getElementsByTagName('subdefs')->item(0)
        );

        $databox->saveStructure($dom_structure);

        return true;
    }
}

