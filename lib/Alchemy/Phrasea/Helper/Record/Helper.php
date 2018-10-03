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
use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\HttpFoundation\Request;

class Helper extends \Alchemy\Phrasea\Helper\Helper
{
    /**
     *
     * @var set_selection
     */
    protected $selection;

    /**
     *
     * @var boolean
     */
    protected $is_possible;

    /**
     *
     * @var Array
     */
    protected $elements_received;

    /**
     *
     * @var Array
     */
    protected $single_grouping;

    /**
     *
     * @var int
     */
    protected $sbas_id;

    /**
     *
     * @var boolean
     */
    protected $has_many_sbas;

    /**
     *
     * @var Array
     */
    protected $required_rights = [];

    /**
     *
     * @var Array
     */
    protected $required_sbas_rights = [];

    /**
     *
     * @var boolean
     */
    protected $works_on_unique_sbas = false;

    /**
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     *
     * @var boolean
     */
    protected $flatten_groupings = false;

    /**
     *
     * @var boolean
     */
    protected $is_basket = false;

    /**
     * @var Basket
     */
    protected $original_basket;

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

        $this->selection = new \set_selection($app);

        if (trim($Request->get('ssel')) !== '') {
            $Basket = $app['converter.basket']->convert($Request->get('ssel'));
            $app['acl.basket']->hasAccess($Basket, $app->getAuthenticatedUser());

            $this->selection->load_basket($Basket);

            $this->is_basket = true;
            $this->original_basket = $Basket;
        } elseif (trim($Request->get('story')) !== '') {
            $repository = $app['repo.story-wz'];

            $storyWZ = $repository->findByUserAndId($app, $app->getAuthenticatedUser(), $Request->get('story'));

            $this->selection->load_list([$storyWZ->getRecord($this->app)->get_serialize_key()], $this->flatten_groupings);
        } else {
            $this->selection->load_list(explode(";", $Request->get('lst')), $this->flatten_groupings);
        }
        $this->elements_received = $this->selection->get_count();

        $this->single_grouping = ($this->get_count_actionable() == 1 &&
            $this->get_count_actionable_groupings() == 1);

        $this->examinate_selection();

        return $this;
    }

    /**
     * Tells if the original selection was a basket
     *
     * @return boolean
     */
    public function is_basket()
    {
        return $this->is_basket;
    }

    /**
     * If the original selection was a basket, returns the basket object
     *
     * @return Basket
     */
    public function get_original_basket()
    {
        return $this->original_basket;
    }

    protected function examinate_selection()
    {
        $this->selection->grep_authorized($this->required_rights, $this->required_sbas_rights);

        if ($this->works_on_unique_sbas === true) {
            $this->sbas_ids = $this->selection->get_distinct_sbas_ids();

            $this->is_possible = count($this->sbas_ids) == 1;

            $this->has_many_sbas = count($this->sbas_ids) > 1;

            $this->sbas_id = $this->is_possible ? array_pop($this->sbas_ids) : false;
        }

        return $this;
    }

    /**
     * Is action applies on single grouping
     *
     * @return <type>
     */
    public function is_single_grouping()
    {
        return $this->single_grouping;
    }

    /**
     * When action on a single grouping, returns the image of himself
     *
     * @return \record_adapter
     */
    public function get_grouping_head()
    {
        if (!$this->is_single_grouping())
            throw new \Exception('Cannot use ' . __METHOD__ . ' here');
        foreach ($this->get_elements() as $record) {
            return $record;
        }
    }

    /**
     * Get elements for the action
     *
     * @return \record_adapter[]
     */
    public function get_elements()
    {
        return $this->selection->get_elements();
    }

    /**
     * Returns true if elements comes from many sbas
     *
     * @return boolean
     */
    public function has_many_sbas()
    {
        return $this->has_many_sbas;
    }

    /**
     * Returns true if the action is possible with the current elements
     * for the user
     *
     * @return boolean
     */
    public function is_possible()
    {
        return $this->is_possible;
    }

    /**
     * Returns the number of elements on which the action can not be done
     *
     * @return int
     */
    public function get_count_not_actionable()
    {
        return $this->get_count_element_received() - $this->get_count_actionable();
    }

    /**
     * Returns the number of elements on which the action can be done
     *
     * @return int
     */
    public function get_count_actionable()
    {
        return $this->selection->get_count();
    }

    /**
     * Returns the number of groupings on which the action can be done
     *
     * @return int
     */
    public function get_count_actionable_groupings()
    {
        return $this->selection->get_count_groupings();
    }

    /**
     * Return the number of elements receveid when starting action
     *
     * @return int
     */
    public function get_count_element_received()
    {
        return $this->elements_received;
    }

    /**
     * Return sbas_ids of the current selection
     *
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->sbas_id;
    }

    /**
     * Get the selection as a serialized string base_id"_"record_id
     *
     * @return string
     */
    public function get_serialize_list()
    {
        if ($this->is_single_grouping()) {
            return $this->get_grouping_head()->getId();
        } else {
            return $this->selection->serialize_list();
        }
    }

    public function grep_records(\Closure $closure)
    {
        foreach ($this->selection->get_elements() as $record) {
            if (!$closure($record)) {
                $this->selection->remove_element($record);
            }
        }

        return $this;
    }
}
