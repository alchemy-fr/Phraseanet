<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Model\Converter\BasketConverter;
use Alchemy\Phrasea\Model\Entities\Basket;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Application;
use record_adapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RecordsRequest extends ArrayCollection
{
    protected $isSingleStory = false;
    protected $rejected;
    protected $received;
    protected $basket;
    protected $databoxes;
    protected $collections;

    const FLATTEN_NO = false;
    const FLATTEN_YES = true;
    const FLATTEN_YES_PRESERVE_STORIES = 'preserve';

    /**
     * RecordsRequest Constructor
     *
     * @param array           $elements
     * @param ArrayCollection $rejected
     * @param ArrayCollection $received
     * @param Basket|null     $basket
     * @param Boolean         $flatten
     */
    public function __construct(array $elements, ArrayCollection $rejected, ArrayCollection $received, Basket $basket = null, $flatten = self::FLATTEN_NO)
    {
        parent::__construct($elements);
        $this->received = $received;
        $this->rejected = $rejected;
        $this->basket = $basket;
        // since stories are already flattened by "fromRequest" (to apply rights on children),
        //    flagging "isSingleStory" is a bit more difficult than checking the first item...
        // $this->isSingleStory = ($flatten !== self::FLATTEN_YES && count($this) === 1 && $this->first()->isStory());

        if ($flatten !== self::FLATTEN_NO) {
            $to_remove = [];
            /** @var record_adapter $record */
            foreach ($this as $key => $record) {
                if ($record->isStory()) {
                    if ($flatten === self::FLATTEN_YES) {
                        // simple flatten : remove the story
                        $to_remove[] = $key;
                    }

                    try {
                        foreach ($record->getChildren() as $child) {
                            $this->set($child->getId(), $child);
                        }
                    } catch (\Exception $e) {
                        // getChildren will no fail since record IS a story
                    }
                }
            }

            foreach ($to_remove as $key) {
                $this->remove($key);
            }
        }

// We check that the list contains only one story, and that every other items (records) are children of this story
        // Too bad : there is no "isChildOf" method :(
        $rec = [];
        $children = [];
        $this->isSingleStory = false;

        $i = 0;
        foreach ($this as $key => $record) {
            if($record->isStory()) {
                if($this->isSingleStory) {
                    // we already found a story, we cannot have 2, game over
                    $this->isSingleStory = false;
                    break;
                }
                $this->isSingleStory = true;
                foreach ($record->getChildren() as $child) {
                    $children[$child->getId()] = 1; // to later find by key
                }
            }
            else {
                $rec[] = $record->getId();
            }
            $record->setNumber($i++);
        }
        if($this->isSingleStory) {
            foreach ($rec as $rid) {
                if(!array_key_exists($rid, $children)) {
                    // one record is not a child, game over
                    $this->isSingleStory = false;
                    break;
                }
            }
        }
    }

    /**
     * Return all distinct databoxes related to the contained records
     *
     * @return \databox[]
     */
    public function databoxes()
    {
        if (!$this->databoxes) {
            $this->databoxes = [];

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
     * @return \collection[]
     */
    public function collections()
    {
        if (!$this->collections) {
            $this->collections = [];

            /** @var \record_adapter $record */
            foreach ($this as $record) {
                if (! isset($this->collections[$record->getBaseId()])) {
                    $this->collections[$record->getBaseId()] = $record->getCollection();
                }
            }

            $this->collections = array_values($this->collections);
        }

        return $this->collections;
    }

    /**
     * Return all received records
     *
     * @return \record_adapter[]|ArrayCollection
     */
    public function received()
    {
        return $this->received;
    }

    /**
     * Return all rejected records
     *
     * @return \record_adapter[]|ArrayCollection
     */
    public function rejected()
    {
        return $this->rejected;
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
                array_filter($this->toArray(), function (\record_adapter $record) {
                        return $record->isStory();
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
     * @return \record_adapter|null
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
     * this param to true when wanting to include
     * elements on FLATTEN_YES or FLATTEN_YES_PRESERVE_STORIES on a singleStory case
     *
     * @param  $canIncludeSingleStoryElements
     *
     * @return string
     */
    public function serializedList($canIncludeSingleStoryElements = false)
    {
        if ($this->isSingleStory() && !$canIncludeSingleStoryElements) {
            return $this->singleStory()->getId();
        }

        $basrec = [];
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
     * @throws \Alchemy\Phrasea\Cache\Exception
     */
    public static function fromRequest(Application $app, Request $request, $flattenStories = self::FLATTEN_NO, array $rightsColl = [], array $rightsDatabox = [])
    {
        $received = [];
        $basket = null;

        if ($request->get('ssel')) {
            /** @var BasketConverter $basketConverter */
            $basketConverter = $app['converter.basket'];
            $basket = $basketConverter->convert($request->get('ssel'));
            $app['acl.basket']->hasAccess($basket, $app->getAuthenticatedUser());

            foreach ($basket->getElements() as $basket_element) {
                $received[$basket_element->getRecord($app)->getId()] = $basket_element->getRecord($app);
            }
        } elseif ($request->get('story')) {
            $repository = $app['repo.story-wz'];

            $storyWZ = $repository->findByUserAndId(
                $app, $app->getAuthenticatedUser(),
                $request->get('story')
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
                    $received[$record->getId()] = $record;
                    unset($record);
                } catch (NotFoundHttpException $e) {
                    continue;
                }
            }
        }

        // fill an array with records from flattened stories
        $elements = $received;

        if ($flattenStories !== self::FLATTEN_NO) {
            /** @var record_adapter $record */
            foreach ($received as $key => $record) {
                if ($record->isStory()) {
                    if ($flattenStories === self::FLATTEN_YES) {
                        // simple flatten : remove the story from elements
                        unset($elements[$key]);
                    }
                    foreach ($record->getChildren() as $child) {
                        $elements[$child->getId()] = $child;
                    }
                }
            }
        }

        // apply rights filter, remove from elements if no rights
        $rejected = [];
        $acl = $app->getAclForUser($app->getAuthenticatedUser());
        foreach ($elements as $key => $record) {
            // any false or unknown right will throw exception and the record will be rejected
            try {
                if (!$acl->has_access_to_record($record)) {
                    throw new \Exception();
                }

                foreach ($rightsColl as $right) {
                    if (!$acl->has_right_on_base($record->getBaseId(), $right)) {
                        throw new \Exception();
                    }
                }

                foreach ($rightsDatabox as $right) {
                    if (!$acl->has_right_on_sbas($record->getDataboxId(), $right)) {
                        throw new \Exception();
                    }
                }
            }
            catch (\Exception $e) {
                $rejected[$key] = $record;
            }
        }
        // remove rejected from elements
        foreach ($rejected as $key => $record) {
            unset($elements[$key]);
        }

        // flattening is already done
        return new static($elements, new ArrayCollection($rejected), new ArrayCollection($received), $basket, self::FLATTEN_NO);
    }
}
