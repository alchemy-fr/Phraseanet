<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_subdefsStructure implements IteratorAggregate
{
    /**
     *
     * @var Array
     */
    protected $AvSubdefs = array();

    /**
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->AvSubdefs);
    }

    /**
     *
     * @param  databox $databox
     * @return Array
     */
    public function __construct(databox &$databox)
    {
        $this->databox = $databox;

        $this->load_subdefs();

        return $this->AvSubdefs;
    }

    public function getSubdefGroup($searchGroup)
    {
        $searchGroup = strtolower($searchGroup);

        foreach ($this->AvSubdefs as $groupname => $subdefgroup) {
            if ($searchGroup == $groupname) {
                return $subdefgroup;
            }
        }

        return null;
    }

    /**
     *
     * @return databox_subdefsStructure
     */
    protected function load_subdefs()
    {
        $sx_struct = $this->databox->get_sxml_structure();

        $this->AvSubdefs = array(
            'image' => array(),
            'video' => array(),
            'audio' => array(),
            'document' => array(),
            'flash' => array()
        );

        if ( ! $sx_struct) {
            return $this;
        }

        $subdefgroup = $sx_struct->subdefs[0];


        foreach ($subdefgroup as $k => $subdefs) {
            $subdefgroup_name = strtolower($subdefs->attributes()->name);

            if ( ! isset($AvSubdefs[$subdefgroup_name])) {
                $AvSubdefs[$subdefgroup_name] = array();
            }

            foreach ($subdefs as $sd) {
                $subdef_name = strtolower($sd->attributes()->name);

                switch ($subdefgroup_name) {
                    case 'audio':
                        $type = new \Alchemy\Phrasea\Media\Type\Audio();
                        break;
                    case 'image':
                        $type = new \Alchemy\Phrasea\Media\Type\Image();
                        break;
                    case 'video':
                        $type = new \Alchemy\Phrasea\Media\Type\Video();
                        break;
                    case 'document':
                        $type = new \Alchemy\Phrasea\Media\Type\Document();
                        break;
                    case 'flash':
                        $type = new \Alchemy\Phrasea\Media\Type\Flash();
                        break;
                    default:
                        continue;
                        break;
                }

                $AvSubdefs[$subdefgroup_name][$subdef_name] = new databox_subdef($type, $sd);
            }
        }
        $this->AvSubdefs = $AvSubdefs;

        return $this;
    }

    /**
     *
     * @param  type           $subdef_type
     * @param  type           $subdef_name
     * @return databox_subdef
     */
    public function get_subdef($subdef_type, $subdef_name)
    {
        if (isset($this->AvSubdefs[$subdef_type]) && isset($this->AvSubdefs[$subdef_type][$subdef_name])) {
            return $this->AvSubdefs[$subdef_type][$subdef_name];
        }
        throw new Exception_Databox_SubdefNotFound(sprintf('Databox subdef name `%s` of type `%s` not found', $subdef_name, $subdef_type));
    }

    /**
     *
     * @param  string                   $group
     * @param  string                   $name
     * @return databox_subdefsStructure
     */
    public function delete_subdef($group, $name)
    {

        $dom_struct = $this->databox->get_dom_structure();
        $dom_xp = $this->databox->get_xpath_structure();
        $nodes = $dom_xp->query(
            '//record/subdefs/'
            . 'subdefgroup[@name="' . $group . '"]/'
            . 'subdef[@name="' . $name . '"]'
        );

        if ($nodes->length > 0) {
            $node = $nodes->item(0);
            $parent = $node->parentNode;
            $parent->removeChild($node);
        }

        if (isset($AvSubdefs[$group]) && isset($AvSubdefs[$group][$name])) {
            unset($AvSubdefs[$group][$name]);
        }

        $this->databox->saveStructure($dom_struct);

        return $this;
    }

    /**
     *
     * @param  string                   $group
     * @param  string                   $name
     * @param  string                   $class
     * @return databox_subdefsStructure
     */
    public function add_subdef($groupname, $name, $class)
    {
        $dom_struct = $this->databox->get_dom_structure();

        $subdef = $dom_struct->createElement('subdef');
        $subdef->setAttribute('class', $class);
        $subdef->setAttribute('name', mb_strtolower($name));

        $dom_xp = $this->databox->get_xpath_structure();
        $query = '//record/subdefs/subdefgroup[@name="' . $groupname . '"]';
        $groups = $dom_xp->query($query);

        if ($groups->length == 0) {
            $group = $dom_struct->createElement('subdefgroup');
            $group->setAttribute('name', $groupname);
            $dom_xp->query('/record/subdefs')->item(0)->appendChild($group);
        } else {
            $group = $groups->item(0);
        }

        $group->appendChild($subdef);

        $this->databox->saveStructure($dom_struct);

        $this->load_subdefs();

        return $this;
    }

    /**
     *
     * @param  string                   $group
     * @param  string                   $name
     * @param  string                   $class
     * @param  boolean                  $downloadable
     * @param  Array                    $options
     * @return databox_subdefsStructure
     */
    public function set_subdef($group, $name, $class, $downloadable, $options)
    {
        $dom_struct = $this->databox->get_dom_structure();

        $subdef = $dom_struct->createElement('subdef');
        $subdef->setAttribute('class', $class);
        $subdef->setAttribute('name', mb_strtolower($name));
        $subdef->setAttribute('downloadable', ($downloadable ? 'true' : 'false'));

        foreach ($options as $option => $value) {

            if (is_scalar($value)) {

                $child = $dom_struct->createElement($option);
                $child->appendChild($dom_struct->createTextNode($value));
                $subdef->appendChild($child);
            } elseif (is_array($value)) {

                foreach ($value as $v) {

                    $child = $dom_struct->createElement($option);
                    $child->appendChild($dom_struct->createTextNode($v));
                    $subdef->appendChild($child);
                }
            }
        }

        $dom_xp = $this->databox->get_xpath_structure();

        $nodes = $dom_xp->query('//record/subdefs/'
            . 'subdefgroup[@name="' . $group . '"]');
        if ($nodes->length > 0) {
            $dom_group = $nodes->item(0);
        } else {
            $dom_group = $dom_struct->createElement('subdefgroup');
            $dom_group->setAttribute('name', $group);

            $nodes = $dom_xp->query('//record/subdefs');
            if ($nodes->length > 0) {
                $nodes->item(0)->appendChild($dom_group);
            } else {
                throw new Exception('Unable to find /record/subdefs xquery');
            }
        }

        $nodes = $dom_xp->query(
            '//record/subdefs/'
            . 'subdefgroup[@name="' . $group . '"]/'
            . 'subdef[@name="' . $name . '"]'
        );

        if ($nodes->length > 0) {
            for ($i = 0; $i < $nodes->length; $i ++ ) {
                $dom_group->removeChild($nodes->item($i));
            }
        }

        $dom_group->appendChild($subdef);

        $this->databox->saveStructure($dom_struct);

        $this->load_subdefs();

        return $this;
    }
}
