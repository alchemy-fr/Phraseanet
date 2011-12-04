<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     searchEngine
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class searchEngine_adapter_abstract
{

  /**
   *
   * @var int
   */
  protected $current_page;
  /**
   *
   * @var int
   */
  protected $total_results;
  /**
   *
   * @var int
   */
  protected $perPage;
  /**
   *
   * @var string
   */
  protected $query;
  /**
   *
   * @var string
   */
  protected $error = '';
  /**
   *
   * @var string
   */
  protected $warning = '';
  /**
   *
   * @var int
   */
  protected $total_available;
  /**
   *
   * @var float
   */
  protected $total_time;
  /**
   *
   * @var int
   */
  protected $offset_start;
  /**
   *
   * @var boolean
   */
  protected $use_stemming = true;
  /**
   *
   * @var string
   */
  protected $locale;
  /**
   *
   * @var string
   */
  protected $current_index = '';

  /**
   *
   * @return int
   */
  public function get_available_results()
  {
    return $this->total_available;
  }

  /**
   *
   * @return float
   */
  public function get_time()
  {
    return $this->total_time;
  }

  /**
   *
   * @return string
   */
  public function get_error()
  {
    return $this->error;
  }

  /**
   *
   * @return string
   */
  public function get_warning()
  {
    return $this->warning;
  }

  /**
   *
   * @return string
   */
  public function get_propositions()
  {
    return null;
  }

  /**
   *
   * @return searchEngine_adapter_abstract
   */
  public function reset_cache()
  {
    return $this;
  }

  /**
   *
   * @return int
   */
  public function get_per_page()
  {
    return $this->perPage;
  }

  /**
   *
   * @return int
   */
  public function get_total_results()
  {
    return $this->total_results;
  }

  /**
   *
   * @return int
   */
  public function get_total_pages()
  {
    return (int) ceil($this->get_available_results() / $this->get_per_page());
  }

  /**
   *
   * @return int
   */
  public function get_current_page()
  {
    return $this->current_page;
  }

  /**
   *
   * @return int
   */
  public function get_offset_start()
  {
    return $this->offset_start;
  }

  /**
   *
   * @return string
   */
  public function get_current_indexes()
  {
    return $this->current_index;
  }

}
