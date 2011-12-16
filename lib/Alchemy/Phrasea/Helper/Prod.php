<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Alchemy\Phrasea\Kernel;

/**
 * 
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Prod extends \Alchemy\Phrasea\Helper\Helper
{
  
  public function get_search_datas()
  {
    $search_datas = array(
        'bases' => array(),
        'dates' => array(),
        'fields' => array()
    );

    $bases = $fields = $dates = array();
    $appbox = \appbox::get_instance();
    $session = $appbox->get_session();
    $user = $this->getKernel()->getAuthenticatedUser();
    
    $searchSet = $user->getPrefs('search');

    foreach ($user->ACL()->get_granted_sbas() as $databox)
    {
      $sbas_id = $databox->get_sbas_id();

      $bases[$sbas_id] = array(
          'thesaurus' => (trim($databox->get_thesaurus()) != ""),
          'cterms' => false,
          'collections' => array(),
          'sbas_id' => $sbas_id
      );

      foreach ($user->ACL()->get_granted_base(array(), array($databox->get_sbas_id())) as $coll)
      {
        $selected = ($searchSet &&
                isset($searchSet->bases) &&
                isset($searchSet->bases->$sbas_id)) ? (in_array($coll->get_base_id(), $searchSet->bases->$sbas_id)) : true;
        $bases[$sbas_id]['collections'][] =
                array(
                    'selected' => $selected,
                    'base_id' => $coll->get_base_id()
        );
      }

      $meta_struct = $databox->get_meta_structure();
      foreach ($meta_struct as $meta)
      {
        if (!$meta->is_indexable())
          continue;
        $id = $meta->get_id();
        $name = $meta->get_name();
        if ($meta->get_type() == 'date')
        {
          if (isset($dates[$id]))
            $dates[$id]['sbas'][] = $sbas_id;
          else
            $dates[$id] = array('sbas' => array($sbas_id), 'fieldname' => $name);
        }
        
        if (isset($fields[$name]))
        {
          $fields[$name]['sbas'][] = $sbas_id;
        }
        else
        {
          $fields[$name] = array(
              'sbas' => array($sbas_id)
              , 'fieldname' => $name
              , 'type' => $meta->get_type()
              , 'id' => $id
          );
        }
      }

      if (!$bases[$sbas_id]['thesaurus'])
        continue;
      if (!$user->ACL()->has_right_on_sbas($sbas_id, 'bas_modif_th'))
        continue;

      if (simplexml_load_string($databox->get_cterms()))
      {
        $bases[$sbas_id]['cterms'] = true;
      }
    }

    $search_datas['fields'] = $fields;
    $search_datas['dates'] = $dates;
    $search_datas['bases'] = $bases;

    return $search_datas;
  }
  
  public function getRandom()
  {
    return md5(time() . mt_rand(100000, 999999));
  }
}