<?php

require_once dirname(__FILE__) . '/../../../../vendor/sphinx/sphinxapi.php';
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
class searchEngine_adapter_sphinx_engine extends searchEngine_adapter_abstract implements searchEngine_adapter_interface
{

  /**
   *
   * @var sphinxClient
   */
  protected $sphinx;

  /**
   *
   * @var array
   */
  protected $distinct_sbas = array();

  /**
   *
   * @var boolean
   */
  protected $search_in_field = false;

  /**
   *
   * @var searchEngine_options
   */
  protected $options;

  /**
   *
   * @var boolean
   */
  protected $search_unique_record = false;

  /**
   *
   * @return searchEngine_adapter_sphinx_engine
   */
  public function __construct()
  {
    $registry = registry::get_instance();

    $this->sphinx = new SphinxClient ();
    $this->sphinx->SetArrayResult(true);

    $this->sphinx->SetServer($registry->get('GV_sphinx_host'), (int) $registry->get('GV_sphinx_port'));
    $this->sphinx->SetConnectTimeout(1);

    return $this;
  }

  /**
   *
   * @param searchEngine_options $options
   * @return searchEngine_adapter_sphinx_engine
   */
  public function set_options(searchEngine_options $options)
  {
    $this->options = $options;

    $filters = array();

    $sbas_ids = array();

    $this->use_stemming = $options->get_use_stemming();
    $this->locale = $options->get_locale();

    foreach ($options->get_bases() as $bas)
    {
      $this->distinct_sbas[phrasea::sbasFromBas($bas)] = true;
      $key = phrasea::sbasFromBas($bas) . '_' . phrasea::collFromBas($bas);
      $sbas_id = phrasea::sbasFromBas($bas);
      $sbas_ids[$sbas_id] = $sbas_id;
      $filters[] = crc32($key);
    }

    if ($filters)
    {
      $this->sphinx->SetFilter('crc_sbas_coll', $filters);
    }

    $this->sphinx->SetFilter('deleted', array(0));

    $filters = array();

    foreach ($sbas_ids as $sbas_id)
    {
      $databox = databox::get_instance($sbas_id);
      $fields = $databox->get_meta_structure();

      foreach ($fields as $field)
      {
        if (!in_array($field->get_id(), $options->get_fields()))
          continue;

        $key = $sbas_id . '_' . $field->get_id();
        $filters[] = crc32($key);
        $this->search_in_field = true;
      }
    }

    if ($filters)
    {
      $this->sphinx->SetFilter('crc_struct_id', $filters);
    }

    /**
     * @todo : enhance : check status better
     */
    foreach ($sbas_ids as $sbas_id)
    {
      $databox = databox::get_instance($sbas_id);
      $s_status = $databox->get_statusbits();
      $status_opts = $options->get_status();
      foreach ($s_status as $n => $status)
      {
        if (!array_key_exists($n, $status_opts))
          continue;
        if (!array_key_exists($sbas_id, $status_opts[$n]))
          continue;
        $crc = crc32($sbas_id . '_' . $n);
        $this->sphinx->SetFilter('status', array($crc), ($status_opts[$n][$sbas_id] == '0'));
      }
    }

    $this->sphinx->SetFilter('parent_record_id', array($options->get_search_type()));

    $filters = array();

    if ($options->get_record_type() != '')
    {
      $filters[] = crc32($options->get_record_type());
    }

    if ($filters)
    {
      $this->sphinx->SetFilter('crc_type', $filters);
    }

    $ord = '';
    switch ($options->get_sortord())
    {
      case searchEngine_options::SORT_MODE_ASC:
        $ord = 'ASC';
        break;
      case searchEngine_options::SORT_MODE_DESC:
      default:
        $ord = 'DESC';
        break;
    }

    switch ($options->get_sortby())
    {
      case searchEngine_options::SORT_RANDOM:
        $sort = '@random';
        break;
      case searchEngine_options::SORT_RELEVANCE:
      default:
        $sort = '@relevance ' . $ord . ', created_on ' . $ord;
        break;
      case searchEngine_options::SORT_CREATED_ON:
        $sort = 'created_on ' . $ord;
        break;
    }

    $this->sphinx->SetGroupBy('crc_sbas_record', SPH_GROUPBY_ATTR, $sort);

    return $this;
  }

  /**
   *
   * @return array
   */
  public function get_status()
  {
    $status = $this->sphinx->Status();
    if (false === $status)
      throw new Exception(_('Sphinx server is offline'));

    return $status;
  }

  /**
   *
   * @return searchEngine_adapter_sphinx_engine
   */
  protected function parse_query()
  {
    $this->query = trim($this->query);

    while (substr($this->query, 0, 1) === '(' && substr($this->query, -1) === ')')
      $this->query = substr($this->query, 1, (mb_strlen($this->query) - 2));

    if ($this->query == 'all')
      $this->query = '';

    while (mb_strpos($this->query, '  ') !== false)
    {
      $this->query = str_replace('  ', ' ', $this->query);
    }

    $preg = preg_match('/\s?recordid\s?=\s?([0-9]+)/i', $this->query, $matches, 0, 0);

    if ($preg > 0)
    {
      $this->sphinx->SetFilter('record_id', array($matches[1]));
      $this->query = '';
      $this->search_unique_record = true;
    }
    else
    {
      $offset = 0;
      while (($pos = mb_strpos($this->query, '-', $offset)) !== false)
      {
        $offset = $pos + 1;
        if ($pos === 0)
        {
          continue;
        }
        if (mb_substr($this->query, ($pos - 1), 1) !== ' ')
        {
          $this->query = mb_substr($this->query, 0, ($pos)) . ' ' . mb_substr($this->query, $pos + 1);
        }
      }

      $this->query = str_ireplace(array(' ou ', ' or '), '|', $this->query);
      $this->query = str_ireplace(array(' sauf ', ' except '), ' -', $this->query);
      $this->query = str_ireplace(array(' and ', ' et '), ' +', $this->query);
    }

    return $this;
  }

  /**
   *
   * @param string $query
   * @param int $offset
   * @param int $perPage
   * @return searchEngine_results
   */
  public function results($query, $offset, $perPage)
  {

    assert(is_int($offset));
    assert($offset >= 0);
    assert(is_int($perPage));
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    $page = ceil($offset / $perPage) + 1;

    $this->current_page = $page;
    $this->perPage = $perPage;
    $this->offset_start = $offset;
    $this->query = $query;

    $this->sphinx->SetLimits($offset, $this->perPage);
    $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
    $this->parse_query();


    $index = '*';

    $params = phrasea::sbas_params();

    $index_keys = array();
    foreach ($params as $sbas_id => $params)
    {
      if (!array_key_exists($sbas_id, $this->distinct_sbas))
        continue;
      $index_keys[] = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
    }

    if (count($index_keys) > 0)
    {
      if ($this->search_in_field === false)
      {
        $index = '';
        $found = false;
        if ($this->query !== '' && $this->options->get_use_stemming())
        {
          if ($session->get_I18n() == 'fr')
          {
            $index .= ', documents' . implode('_stemmed_fr, documents', $index_keys) . '_stemmed_fr';
            $found = true;
          }
          elseif ($session->get_I18n() == 'en')
          {
            $index .= ', documents' . implode('_stemmed_en, documents', $index_keys) . '_stemmed_en';
            $found = true;
          }
        }
        if (!$found)
          $index .= 'documents' . implode(', documents', $index_keys);
        $index .= ', docs_realtime' . implode(', docs_realtime', $index_keys);
      }
      else
      {
        $index = '';
        $found = false;
        if ($this->query !== '' && $this->options->get_use_stemming() && $session->get_I18n() == 'fr')
        {
          if ($session->get_I18n() == 'fr')
          {
            $index .= ', metadatas' . implode('_stemmed_fr, metadatas', $index_keys) . '_stemmed_fr';
            $found = true;
          }
          elseif ($session->get_I18n() == 'en')
          {
            $index .= ', metadatas' . implode('_stemmed_en, metadatas', $index_keys) . '_stemmed_en';
            $found = true;
          }
        }
        if (!$found)
          $index = 'metadatas' . implode(',metadatas', $index_keys);
        $index .= ', metas_realtime' . implode(', metas_realtime', $index_keys);
      }
    }

    $this->current_index = $index;

    $res = $this->sphinx->Query($this->query, $this->current_index);
    $results = new set_result();

    if ($res === false)
    {
      if ($this->sphinx->IsConnectError() === true)
      {
        $this->error = _('Sphinx server is offline');
      }
      else
      {
        $this->error = $this->sphinx->GetLastError();
      }
      $this->warning = $this->sphinx->GetLastWarning();
    }
    else
    {
      $this->error = $res['error'];
      $this->warning = $res['warning'];

      $this->total_time = $res['time'];
      $this->total_results = $res['total_found'];
      $this->total_available = $res['total'];

      $courcahnum = $this->offset_start;

      if (isset($res['matches']))
      {
        foreach ($res['matches'] as $record_id => $match)
        {
          try
          {
            $record =
                    new record_adapter(
                            $match['attrs']['sbas_id']
                            , $match['attrs']['record_id']
                            , $courcahnum
            );

            $results->add_element($record);
          }
          catch (Exception $e)
          {

          }
          $courcahnum++;
        }
      }
    }

    return new searchEngine_results($results, $this);
  }

  /**
   *
   * @param string $keyword
   * @return string
   */
  function BuildTrigrams($keyword)
  {
    $t = "__" . $keyword . "__";

    $trigrams = "";
    for ($i = 0; $i < strlen($t) - 2; $i++)
      $trigrams .= substr($t, $i, 3) . " ";

    return $trigrams;
  }

//  public function get_index_suggestions($keyword)
//  {
//    $trigrams = $this->BuildTrigrams($keyword);
//    $query = "\"$trigrams\"/1";
//    $len = strlen($keyword);
//
//    $this->sphinx->SetArrayResult(true);
//
//    $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
//    $this->sphinx->SetRankingMode(SPH_RANK_WORDCOUNT);
//    $this->sphinx->SetFilterRange("len", $len - 2, $len + 2);
//    $this->sphinx->SetSelect("*, @weight+2-abs(len-$len) AS myrank");
//    $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, "myrank DESC, freq DESC");
//    $this->sphinx->SetLimits(0, 10);
//
//    $params = phrasea::sbas_params();
//
//    $index_keys = array();
//    foreach ($params as $sbas_id => $p)
//    {
//      $index_keys[] = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $p['host'], $p['port'], $p['user'], $p['dbname'])));
//    }
//    $index = 'suggest' . implode(',suggest', $index_keys);
//
//    $res = $this->sphinx->Query($query, $index);
//
//    if ($this->sphinx->Status() === false)
//    {
//      return array();
//    }
//
//    if (!$res || !isset($res["matches"]))
//    {
//      return array();
//    }
//
//    $ret = array();
//    foreach ($res["matches"] as $match)
//      $ret[] = $match['attrs']['keyword'];
//
//    return $ret;
//  }

  protected function get_sugg_trigrams($word)
  {

    $trigrams = $this->BuildTrigrams($word);
    $query = "\"$trigrams\"/1";
    $len = strlen($word);

    $this->sphinx->ResetGroupBy();
    $this->sphinx->ResetFilters();

    $this->sphinx->SetMatchMode(SPH_MATCH_EXTENDED2);
    $this->sphinx->SetRankingMode(SPH_RANK_WORDCOUNT);
    $this->sphinx->SetFilterRange("len", $len - 2, $len + 4);

    $this->sphinx->SetSortMode(SPH_SORT_EXTENDED, "@weight DESC");
    $this->sphinx->SetLimits(0, 10);

    $params = phrasea::sbas_params();

    $index_keys = array();
    foreach ($params as $sbas_id => $p)
    {
      if (!array_key_exists($sbas_id, $this->distinct_sbas))
        continue;
      $index_keys[] = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $p['host'], $p['port'], $p['user'], $p['dbname'])));
    }
    $index = 'suggest' . implode(',suggest', $index_keys);

    $res = $this->sphinx->Query($query, $index);

    if ($this->sphinx->Status() === false)
    {
      return array();
    }

    if (!$res || !isset($res["matches"]))
    {
      return array();
    }

    $this->sphinx->ResetGroupBy();
    $this->sphinx->ResetFilters();

    $this->set_options($this->options);

    $ret = array();
    foreach ($res["matches"] as $match)
      $ret[] = $match['attrs']['keyword'];

    return $ret;
  }

  /**
   *
   * @param Session_Handler $session
   * @return array
   */
  public function get_suggestions(Session_Handler $session, $only_last_word = false)
  {
    if (!$this->current_index)
      $this->current_index = '*';

    $appbox = appbox::get_instance();
    $supposed_qry = mb_strtolower($this->query);
    $pieces = explode(" ", str_replace(array("all", "last", "et", "ou", "sauf", "and", "or", "except", "in", "dans", "'", '"', "(", ")", "_", "-"), ' ', $supposed_qry));

    $clef = 'sph_sugg_' . crc32(serialize($this->options) . ' ' . $this->current_index . implode(' ', $pieces) . ' ' . ($only_last_word ? '1' : '0'));

    try
    {
      return $appbox->get_data_from_cache($clef);
    }
    catch (Exception $e)
    {

    }

    $potential_queries = array();

    $n = 0;

    if ($only_last_word)
    {
      $pieces = array(array_pop($pieces));
    }

    $tag = $session->get_I18n();

    $suggestions = array();

    $total_chaines = 0;
    $propal_n = $this->get_total_results();

    if (function_exists('enchant_broker_init'))
    {
      $r = enchant_broker_init();
      if (enchant_broker_dict_exists($r, $tag))
      {
        $d = enchant_broker_request_dict($r, $tag);

        foreach ($pieces as $piece)
        {
          if (trim($piece) === '')
            continue;

          $found = false;
          $suggs = array($piece);
          if (enchant_dict_check($d, $piece) == false)
          {
            $suggs = array_unique(array_merge($suggs, enchant_dict_suggest($d, $piece)));
          }

          $suggestions[$n] = array('original' => $piece, 'suggs' => $suggs);

          $n++;
        }
        enchant_broker_free_dict($d);
      }
      enchant_broker_free($r);
    }

    if ($only_last_word)
    {
      foreach ($pieces as $piece)
      {
        foreach ($this->get_sugg_trigrams($piece) as $tri_sugg)
        {
          $suggestions[$n] = array('original' => $piece, 'suggs' => array($tri_sugg));
          $n++;
        }
      }
    }

    $q_todo = array($supposed_qry);
    $n = 0;

    foreach ($suggestions as $suggestion)
    {
      $tmp_qq = array();
      foreach ($suggestion['suggs'] as $sugg)
      {
        foreach ($q_todo as $q_td)
        {
          $tmp_qq[] = $q_td;
          $tmp_data = str_replace($suggestion['original'], $sugg, $q_td);
          $tmp_qq[] = $tmp_data;
        }
        $tmp_qq[] = str_replace($suggestion['original'], $sugg, $supposed_qry);
      }
      $q_todo = array_unique(array_merge($tmp_qq, array($supposed_qry)));

      $n++;
    }

    $propals = array(array('value' => $supposed_qry, 'current' => true, 'hits' => $this->get_total_results()));

    foreach ($q_todo as $f)
    {
      if ($f == $supposed_qry)
        continue;

      $clef_unique_datas = 'sph_sugg_' . crc32(serialize($this->options) . $this->current_index . $f);

      try
      {
        $datas = $appbox->get_data_from_cache($clef_unique_datas);
      }
      catch (Exception $e)
      {
        $datas = false;
      }
      if (is_int($datas))
      {
        $found = $datas;
        $cache = true;
      }
      else
      {
        $cache = false;
        $found = 0;

        $tmp_res = $this->sphinx->Query($f, $this->current_index);

        if ($tmp_res !== false && isset($tmp_res['total_found']))
        {
          $found = (int) $tmp_res['total_found'];
        }
        $appbox->set_data_to_cache($found, $clef_unique_datas, 3600);
      }

      if ($found > 0)
      {
        $propals[] = array('value' => $f, 'current' => false, 'hits' => $found, 'cache' => $cache);
      }
    }

    usort($propals, array('self', 'suggestions_hit_sorter'));

    $max = 0;

    foreach ($propals as $key => $prop)
    {
      $max = max($max, $prop['hits'] * 1 / 100);
      if ($prop['hits'] < $max)
        unset($propals[$key]);
    }

    $appbox->set_data_to_cache($propals, $clef, 3600);

    return $propals;
  }

  protected static function suggestions_hit_sorter($a, $b)
  {
    if ($a['hits'] == $b['hits'])
    {
      return 0;
    }

    return ($a['hits'] > $b['hits']) ? -1 : 1;
  }

  /**
   *
   * @return string
   */
  public function get_parsed_query()
  {
    return $this->query;
  }

  /**
   *
   * @param string $query
   * @param array $fields
   * @param int $selected_sbas_id
   * @return array
   */
  public function build_excerpt($query, array $fields, record_adapter $record)
  {
    $appbox = appbox::get_instance();
    $session = $appbox->get_session();
    $selected_sbas_id = $record->get_sbas_id();

    $index = '';

    $params = phrasea::sbas_params();

    $index_keys = array();
    foreach ($params as $sbas_id => $params)
    {
      if ($sbas_id != $selected_sbas_id)
        continue;
      $index_keys[] = crc32(str_replace(array('.', '%'), '_', sprintf('%s_%s_%s_%s', $params['host'], $params['port'], $params['user'], $params['dbname'])));
    }

    if (count($index_keys) > 0)
    {
      if ($this->search_in_field === false)
      {
        $index = '';
        $found = false;
        if ($this->options->get_use_stemming())
        {
          if ($session->get_I18n() == 'fr')
          {
            $index .= 'documents' . implode('_stemmed_fr, documents', $index_keys) . '_stemmed_fr';
            $found = true;
          }
          elseif ($session->get_I18n() == 'en')
          {
            $index .= 'documents' . implode('_stemmed_en, documents', $index_keys) . '_stemmed_en';
            $found = true;
          }
        }
        if (!$found)
          $index .= 'documents' . implode(', documents', $index_keys);
      }
      else
      {
        $index = '';
        $found = false;
        if ($this->options->get_use_stemming() && $session->get_I18n() == 'fr')
        {
          if ($session->get_I18n() == 'fr')
          {
            $index .= 'metadatas' . implode('_stemmed_fr, metadatas', $index_keys) . '_stemmed_fr';
            $found = true;
          }
          elseif ($session->get_I18n() == 'en')
          {
            $index .= 'metadatas' . implode('_stemmed_en, metadatas', $index_keys) . '_stemmed_en';
            $found = true;
          }
        }
        if (!$found)
          $index = 'metadatas' . implode(',metadatas', $index_keys);
      }
    }
    $opts = array(
        'before_match' => "<em>",
        'after_match' => "</em>"
    );

    $fields_to_send = array();

    foreach($fields as $k=>$f)
    {
      $fields_to_send[$k] = $f['value'];
    }

    return $this->sphinx->BuildExcerpts($fields_to_send, $index, $query, $opts);
  }

}
