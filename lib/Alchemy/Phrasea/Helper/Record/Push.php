<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper,
    Symfony\Component\HttpFoundation\Request;

/**
 * Edit Record Helper
 * This object handles /edit/ request and filters records that user can edit
 * 
 * It prepares metadatas, databases structures.
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Push extends RecordHelper
{

  protected $flatten_groupings = true;
  
  protected $required_rights = array('canpush');
  
  public function search($search)
  {
    $query = new \User_Query(appbox::get_instance());
    
    $result = $query->on_bases_where_i_am($this->core->getAuthenticatedUser(), array('canpush'))
          ->like(\User_Query::LIKE_FIRSTNAME, $search)
          ->like(\User_Query::LIKE_LASTNAME, $search)
          ->like(\User_Query::LIKE_LOGIN, $search)
          ->like_match(\User_Query::LIKE_MATCH_OR)
                  ->include_phantoms()
            ->limit(0, 50)
          ->execute()->get_results();
    
    
  }
  
}
