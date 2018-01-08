<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Databox\SubdefGroup;
use Assert\Assertion;
use Alchemy\Phrasea\Media\MediaTypeFactory;
use Symfony\Component\Translation\TranslatorInterface;

class databox_subdefsStructure implements IteratorAggregate, Countable
{
    /**
     * @var SubdefGroup[]
     */
    protected $subdefGroups = [];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var databox
     */
    private $databox;

    /**
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->subdefGroups);
    }

    public function count()
    {
        $n = 0;

        foreach ($this->subdefGroups as $subdefs) {
            $n += count($subdefs);
        }

        return $n;
    }

    public function __construct(databox $databox, TranslatorInterface $translator)
    {
        $this->databox = $databox;
        $this->translator = $translator;

        $this->load_subdefs();
    }

    /**
     * @param string $searchGroup
     * @return SubdefGroup|databox_subdef[]
     */
    public function getSubdefGroup($searchGroup)
    {
        $searchGroup = strtolower($searchGroup);

        if (isset($this->subdefGroups[$searchGroup])) {
            return $this->subdefGroups[$searchGroup];
        }

        return null;
    }

    protected function load_subdefs()
    {
        $sx_struct = $this->databox->get_sxml_structure();

        if (! $sx_struct) {
            return;
        }

        $subdefgroup = $sx_struct->subdefs[0];

        $mediaTypeFactory = new MediaTypeFactory();

        foreach ($subdefgroup as $k => $subdefs) {
            $subdefgroup_name = strtolower($subdefs->attributes()->name);
            $isDocumentOrderable = isset($subdefs->attributes()->document_orderable)
                ? p4field::isyes($subdefs->attributes()->document_orderable) : true;

            if (! isset($this->subdefGroups[$subdefgroup_name])) {
                try {
                    $type = $mediaTypeFactory->createMediaType($subdefgroup_name);
                } catch (RuntimeException $exception) {
                    // Skip undefined media type group
                    continue;
                }

                $this->subdefGroups[$subdefgroup_name] = new SubdefGroup($subdefgroup_name, $type, $isDocumentOrderable);
            }

            $group = $this->getSubdefGroup($subdefgroup_name);

            foreach ($subdefs as $sd) {
                $group->addSubdef(new databox_subdef($group->getType(), $sd, $this->translator));
            }
        }
    }

    /**
     * @param string $subdef_type
     * @param string $subdef_name
     *
     * @return bool
     */
    public function hasSubdef($subdef_type, $subdef_name)
    {
        $group = $this->getSubdefGroup(strtolower($subdef_type));

        if ($group) {
            return $group->hasSubdef(strtolower($subdef_name));
        }

        return false;
    }

    /**
     * @param string $subdef_type
     * @param string $subdef_name
     *
     * @return databox_subdef
     * @throws Exception_Databox_SubdefNotFound
     */
    public function get_subdef($subdef_type, $subdef_name)
    {
        $type = strtolower($subdef_type);
        $name = strtolower($subdef_name);

        $group = $this->getSubdefGroup(strtolower($subdef_type));

        if (!$group) {
            throw new Exception_Databox_SubdefNotFound(sprintf('Databox subdef name `%s` of type `%s` not found', $name, $type));
        }

        try {
            return $group->getSubdef($name);
        } catch (RuntimeException $exception) {
            throw new Exception_Databox_SubdefNotFound(sprintf('Databox subdef name `%s` of type `%s` not found', $name, $type), $exception);
        }
    }

    /**
     * @param string $group
     * @param string $name
     * @return self
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

        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            $parent = $node->parentNode;
            $parent->removeChild($node);
        }

        if ($this->hasSubdef($group, $name)) {
            $this->getSubdefGroup($group)->removeSubdef($name);
        }

        $this->databox->saveStructure($dom_struct);

        return $this;
    }

    /**
     * @param  string $groupname
     * @param  string $name
     * @param  string $class
     * @param  string $mediatype
     * @param  string $preset
     * @return databox_subdefsStructure
     */
    public function add_subdef($groupname, $name, $class, $mediatype, $preset)
    {
        $dom_struct = $this->databox->get_dom_structure();

        $subdef = $dom_struct->createElement('subdef');
        $subdef->setAttribute('class', $class);
        $subdef->setAttribute('name', mb_strtolower($name));
        $subdef->setAttribute('presets', $preset);
        $subdef->setAttribute('mediaType', $mediatype);

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
     * @param string $group
     * @param string $name
     * @param string $class
     * @param boolean $downloadable
     * @param array $options
     * @param array $labels
     * @param boolean $orderable
     * @param string $preset
     * @return databox_subdefsStructure
     * @throws Exception
     */
    public function set_subdef($group, $name, $class, $downloadable, $options, $labels, $orderable = true, $preset = "Custom")
    {
        $dom_struct = $this->databox->get_dom_structure();

        $subdef = $dom_struct->createElement('subdef');
        $subdef->setAttribute('class', $class);
        $subdef->setAttribute('name', mb_strtolower($name));
        $subdef->setAttribute('downloadable', ($downloadable ? 'true' : 'false'));
        $subdef->setAttribute('orderable', ($orderable ? 'true' : 'false'));
        $subdef->setAttribute('presets', $preset);

        foreach ($labels as $code => $label) {
            $child = $dom_struct->createElement('label');
            $child->appendChild($dom_struct->createTextNode($label));
            $lang = $child->appendChild($dom_struct->createAttribute('lang'));
            $lang->value = $code;
            $subdef->appendChild($child);
        }

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

        $refNode = null;
        if ($nodes->length > 0) {
            for ($i = 0; $i < $nodes->length; $i ++) {
                $refNode = $nodes->item($i)->nextSibling;
                $dom_group->removeChild($nodes->item($i));
            }
        }

        $dom_group->insertBefore($subdef, $refNode);

        $this->databox->saveStructure($dom_struct);

        $this->load_subdefs();

        return $this;
    }
}
