<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Entities\Basket;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecordsRequest extends ArrayCollection
{
    protected $isSingleStory = false;
    protected $received;
    protected $basket;
    protected $databoxes;
    protected $collections;

    const FLATTEN_NO = false;
    const FLATTEN_YES = true;
    const FLATTEN_YES_PRESERVE_STORIES = 'preserve';

    /**
     * Constructor
     *
     * @param array           $elements
     * @param ArrayCollection $received
     * @param Basket          $basket
     * @param Boolean         $flatten
     */
    public function __construct(array $elements, ArrayCollection $received, Basket $basket = null, $flatten = self::FLATTEN_NO)
    {
        parent::__construct($elements);
        $this->received = $received;
        $this->basket = $basket;
        $this->isSingleStory = ($flatten !== self::FLATTEN_YES && 1 === count($this) && $this->first()->is_grouping());

        if (self::FLATTEN_NO !== $flatten) {
            $to_remove = array();
            foreach ($this as $key => $record) {
                if ($record->is_grouping()) {
                    if (self::FLATTEN_YES === $flatten) {
                        $to_remove[] = $key;
                    }
                    foreach ($record->get_children() as $child) {
                        $this->set($child->get_serialize_key(), $child);
                    }
                }
            }

            foreach ($to_remove as $key) {
                $this->remove($key);
            }
        }

        $i = 1;
        $records = $this->toArray();
        array_walk($records, function ($record) use (&$i) {
            $record->set_number($i++);
        });
    }

    /**
     * Return all distinct databoxes related to the contained records
     *
     * @return array
     */
    public function databoxes()
    {
        if (!$this->databoxes) {
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

    /**
     * Return all distinct collections related to the contained records
     *
     * @return array
     */
    public function collections()
    {
        if (!$this->collections) {
            $this->collections = array();

            foreach ($this as $record) {
                if (false === array_key_exists($record->get_base_id(), $this->collections)) {
                    $this->collections[$record->get_base_id()] = $record->get_collection();
                }
            }

            $this->collections = array_values($this->collections);
        }

        return $this->collections;
    }

    /**
     * Return all received records
     *
     * @return ArrayCollection
     */
    public function received()
    {
        return $this->received;
    }

    /**
     * Return basket entity if provided, null otherwise
     *
     * @return Basket|null
     */
    public function basket()
    {
        return $this->basket;
    }

    /**
     * Filter contents and return only stories
     *
     * @return ArrayCollection
     */
    public function stories()
    {
        return new ArrayCollection(
                array_filter($this->toArray(), function(\record_adapter $record) {
                        return $record->is_grouping();
                    })
        );
    }

    /**
     * Return true if the request contains a single story
     *
     * @return Boolean
     */
    public function isSingleStory()
    {
        return $this->isSingleStory;
    }

    /**
     * Return the first story if a single story is contained, null otherwise
     *
     * @return record_adapter|null
     */
    public function singleStory()
    {
        if ($this->isSingleStory()) {
            return $this->first();
        }

        return null;
    }

    /**
     * Return a serialized list of elements
     *
     * @return string
     */
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
     * Create a new RecordRequest from current request
     *
     * @param  Application    $app
     * @param  Request        $request
     * @param  boolean        $flattenStories
     * @param  array          $rightsColl
     * @param  array          $rightsDatabox
     * @return RecordsRequest
     */
    public static function fromRequest(Application $app, Request $request, $flattenStories = self::FLATTEN_NO, array $rightsColl = array(), array $rightsDatabox = array())
    {
        $elements = $received = array();
        $basket = null;

        if ($request->get('ssel')) {
            $repository = $app['EM']->getRepository('\Entities\Basket');

            $basket = $repository->findUserBasket($app, $request->get('ssel'), $app['authentication']->getUser(), false);

            foreach ($basket->getElements() as $basket_element) {
                $received[$basket_element->getRecord($app)->get_serialize_key()] = $basket_element->getRecord($app);
            }
        } elseif ($request->get('story')) {
            $repository = $app['EM']->getRepository('\Entities\StoryWZ');

            $storyWZ = $repository->findByUserAndId(
                $app, $app['authentication']->getUser()
                , $request->get('story')
            );

            $received[$storyWZ->getRecord($app)->get_serialize_key()] = $storyWZ->getRecord($app);
        } else {
            foreach (explode(";", $request->get('lst')) as $bas_rec) {
                $basrec = explode('_', $bas_rec);
                if (count($basrec) != 2) {
                    continue;
                }
                try {
                    $record = new \record_adapter($app, (int) $basrec[0], (int) $basrec[1]);
                    $received[$record->get_serialize_key()] = $record;
                    unset($record);
                } catch (NotFoundHttpException $e) {
                    continue;
                }
            }
        }

        $elements = $received;

        $to_remove = array();

        foreach ($elements as $id => $record) {

            if (!$app['authentication']->getUser()->ACL()->has_access_to_record($record)) {
                $to_remove[] = $id;
                continue;
            }

            foreach ($rightsColl as $right) {
                if (!$app['authentication']->getUser()->ACL()->has_right_on_base($record->get_base_id(), $right)) {
                    $to_remove[] = $id;
                    continue;
                }
            }

            foreach ($rightsDatabox as $right) {
                if (!$app['authentication']->getUser()->ACL()->has_right_on_sbas($record->get_sbas_id(), $right)) {
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
