<?php

namespace Alchemy\Phrasea\Controller;

use Entities\Basket;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;

class RecordsRequest extends ArrayCollection
{
    protected $received;
    protected $basket;
    protected $databoxes;

    public function __construct(array $elements, ArrayCollection $received, Basket $basket = null, $flatten = false)
    {
        parent::__construct($elements);
        $this->received = $received;
        $this->basket = $basket;

        if ($flatten) {
            $to_remove = array();
            foreach ($this as $key => $record) {
                if ($record->is_grouping()) {
                    $to_remove[] = $key;
                    foreach ($record->get_children() as $child) {
                        $this->set($child->get_serialize_key(), $child);
                    }
                }
            }

            foreach ($to_remove as $key) {
                $this->remove($key);
            }
        }
    }

    public function databoxes()
    {
        if ( ! $this->databoxes) {
            $this->databoxes = array();

            foreach ($this as $record) {
                if (false === array_key_exists($record->get_databox()->get_sbas_id(), $this->databoxes)) {
                    $this->databoxes[$record->get_databox()->get_sbas_id()] = $record->get_databox();
                }
            }

            $this->databoxes = array_values($this->databoxes);
        }

        return $this->databoxes;
    }

    public function received()
    {
        return $this->received;
    }

    public function basket()
    {
        return $this->basket;
    }

    public function stories()
    {
        return new ArrayCollection(
                array_filter($this->toArray(), function(\record_adapter $record) {
                        return $record->is_grouping();
                    })
        );
    }

    public function isSingleStory()
    {
        if ($this->count() === 1) {
            if ($this->first()->is_grouping()) {
                return true;
            }
        }

        return false;
    }

    public function singleStory()
    {
        if ($this->isSingleStory()) {
            return $this->first();
        }

        return null;
    }

    public function serializedList()
    {
        if ($this->isSingleStory()) {
            return $this->singleStory()->get_serialize_key();
        }

        $basrec = array();
        foreach ($this as $record) {
            $basrec[] = $record->get_serialize_key();
        }

        return implode(';', $basrec);
    }

    /**
     *
     * @param \Alchemy\Phrasea\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type $flattenStories
     * @param array $rightsColl
     * @param array $rightsDatabox
     * @return RecordsRequest
     */
    public static function fromRequest(Application $app, Request $request, $flattenStories = false, array $rightsColl = array(), array $rightsDatabox = array())
    {
        $elements = $received = array();
        $basket = null;

        if ($request->get('ssel')) {
            echo "looking for a basket\n";
            $repository = $app['phraseanet.core']['EM']->getRepository('\Entities\Basket');

            echo "found a repo\n";
            $basket = $repository->findUserBasket($request->get('ssel'), $app['phraseanet.core']->getAuthenticatedUser(), false);

            echo "found a basket\n";
            echo "found ".count($basket->getElements())." elements\n";
            foreach ($basket->getElements() as $basket_element) {
                echo "adding ".$basket_element->getRecord()->get_serialize_key()."\n";
                $received[$basket_element->getRecord()->get_serialize_key()] = $basket_element->getRecord();
            }
        } elseif ($request->get('story')) {
            $repository = $app['phraseanet.core']['EM']->getRepository('\Entities\StoryWZ');

            $storyWZ = $repository->findByUserAndId(
                $app['phraseanet.core']->getAuthenticatedUser()
                , $request->get('story')
            );

            $received[$storyWZ->getRecord()->get_serialize_key()] = $storyWZ->getRecord();
        } else {
            foreach (explode(";", $request->get('lst')) as $bas_rec) {
                $basrec = explode('_', $bas_rec);
                if (count($basrec) != 2) {
                    continue;
                }
                try {
                    $record = new \record_adapter((int) $basrec[0], (int) $basrec[1]);
                    $received[$record->get_serialize_key()] = $record;
                    unset($record);
                } catch (\Exception_NotFound $e) {
                    continue;
                }
            }
        }

        $elements = $received;

        $to_remove = array();

        $user = $app['phraseanet.core']->getAuthenticatedUser();

        foreach ($elements as $id => $record) {

            if ( ! $user->ACL()->has_access_to_record($record)) {
                $to_remove[] = $id;
                continue;
            }
            foreach ($rightsColl as $right) {
                if ( ! $user->ACL()->has_right_on_base($record->get_base_id(), $right)) {
                    $to_remove[] = $id;
                    continue;
                }
            }
            foreach ($rightsDatabox as $right) {
                if ( ! $user->ACL()->has_right_on_sbas($record->get_sbas_id(), $right)) {
                    $to_remove[] = $id;
                    continue;
                }
            }
        }

        foreach ($to_remove as $id) {
            unset($elements[$id]);
        }

        return new static($elements, new ArrayCollection($received), $basket, $flattenStories);
    }
}
