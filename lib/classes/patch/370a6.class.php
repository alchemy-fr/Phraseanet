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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_370a6 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.0a6';

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
        $structure = $databox->get_structure();

        $DOM = new DOMDocument();
        $DOM->loadXML($structure);

        $xpath = new DOMXpath($DOM);

        foreach ($xpath->query('/record/subdefs/subdefgroup[@name="video"]/subdef[@name="preview"]/acodec') as $node) {
                $node->nodeValue = 'libfaac';
        }

        foreach ($xpath->query('/record/subdefs/subdefgroup[@name="video"]/subdef[@name="preview"]/vcodec') as $node) {
                $node->nodeValue = 'libx264';
        }

        $databox->saveStructure($DOM);

        $subdefgroups = $databox->get_subdef_structure();

        foreach ($subdefgroups as $groupname => $subdefs) {

            foreach ($subdefs as $name => $subdef) {

                $this->addScreenDeviceOption($subdefgroups, $subdef, $groupname);

                if (in_array($name, array('preview', 'thumbnail'))) {

                    if ($name == 'thumbnail' || $subdef->getSubdefType()->getType() != \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_VIDEO) {
                        $this->addMobileSubdefImage($subdefgroups, $subdef, $groupname);
                    } else {
                        $this->addMobileSubdefVideo($subdefgroups, $subdef, $groupname);
                    }
                }

                if ($subdef->getSubdefType()->getType() != \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_VIDEO) {
                    continue;
                }

                $this->addHtml5Video($subdefgroups, $subdef, $groupname);
            }
        }

        return true;
    }

    protected function addScreenDeviceOption($root, $subdef, $groupname)
    {
        $optionsSubdef = $subdef->getOptions();

        $options = array();

        foreach ($optionsSubdef as $optname => $option) {
            $options[$optname] = $option->getValue();
        }

        $options['path'] = $subdef->get_path();
        $options['mediatype'] = $subdef->getSubdefType()->getType();
        $options['meta'] = $subdef->meta_writeable() ? 'yes' : 'no';
        $options['devices'] = array(databox_subdef::DEVICE_SCREEN);

        $root->set_subdef($groupname, $subdef->get_name(), $subdef->get_class(), $subdef->is_downloadable(), $options);
    }

    protected function addMobileSubdefVideo($root, $baseSubdef, $groupname)
    {
        $newSubdefOptionsWebM = $newSubdefOptionsOgg = $newSubdefOptionsX264 = array(
            'path'      => $baseSubdef->get_path(),
            'mediatype' => \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_VIDEO
        );

        $options = array(
            'path'      => $baseSubdef->get_path(),
            'mediatype' => \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_VIDEO,
            'bitrate'   => '300',
            'threads'   => '2',
            'GOPsize'   => '25',
            'size'      => '480',
            'fps'       => '15',
            'devices'   => array(databox_subdef::DEVICE_HANDHELD),
        );

        foreach ($options as $name => $value) {
            $newSubdefOptionsWebM[$name] = $value;
            $newSubdefOptionsOgg[$name] = $value;
            $newSubdefOptionsX264[$name] = $value;
        }

        $newSubdefOptionsWebM['vcodec'] = 'libvpx';
        $newSubdefOptionsWebM['acodec'] = 'libvorbis';

        $newSubdefOptionsOgg['vcodec'] = 'libtheora';
        $newSubdefOptionsOgg['acodec'] = 'libvorbis';

        $newSubdefOptionsX264['acodec'] = 'libfaac';
        $newSubdefOptionsX264['vcodec'] = 'libx264';

        $root->set_subdef($groupname, $baseSubdef->get_name() . '_mobile_webM', $baseSubdef->get_class(), false, $newSubdefOptionsWebM);
        $root->set_subdef($groupname, $baseSubdef->get_name() . '_mobile_OGG', $baseSubdef->get_class(), false, $newSubdefOptionsOgg);
        $root->set_subdef($groupname, $baseSubdef->get_name() . '_mobile_X264', $baseSubdef->get_class(), false, $newSubdefOptionsX264);
    }

    protected function addMobileSubdefImage($root, $baseSubdef, $groupname)
    {
        $optionMobile = array();

        $optionMobile['size'] = $baseSubdef->get_name() == 'thumbnail' ? '150' : '480';
        $optionMobile['resolution'] = '72';
        $optionMobile['strip'] = 'yes';
        $optionMobile['quality'] = '75';
        $optionMobile['path'] = $baseSubdef->get_path();
        $optionMobile['mediatype'] = \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_IMAGE;
        $optionMobile['meta'] = 'no';

        $optionMobile['devices'] = array(databox_subdef::DEVICE_HANDHELD);

        $root->set_subdef($groupname, $baseSubdef->get_name() . '_mobile', $baseSubdef->get_class(), false, $optionMobile);
    }

    protected function addHtml5Video($root, $baseSubdef, $groupname)
    {
        $newSubdefOptionsWebM = $newSubdefOptionsOgg = array(
            'path'      => $baseSubdef->get_path(),
            'mediatype' => \Alchemy\Phrasea\Media\Subdef\Subdef::TYPE_VIDEO,
            'devices' => array(\databox_subdef::DEVICE_SCREEN)
        );

        foreach ($baseSubdef->getOptions() as $optionname => $option) {
            $newSubdefOptionsWebM[$optionname] = $option->getValue();
            $newSubdefOptionsOgg[$optionname] = $option->getValue();
        }

        $newSubdefOptionsWebM['vcodec'] = 'libvpx';
        $newSubdefOptionsWebM['acodec'] = 'libvorbis';

        $newSubdefOptionsOgg['vcodec'] = 'libtheora';
        $newSubdefOptionsOgg['acodec'] = 'libvorbis';

        $root->set_subdef($groupname, $baseSubdef->get_name() . '_webM', $baseSubdef->get_class(), false, $newSubdefOptionsWebM);
        $root->set_subdef($groupname, $baseSubdef->get_name() . '_OGG', $baseSubdef->get_class(), false, $newSubdefOptionsOgg);
    }
}

