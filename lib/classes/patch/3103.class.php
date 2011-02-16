<?php

class patch_3103 implements patch
{

  private $release = '3.1.0';
  private $concern = array('application_box');

  function get_release()
  {
    return $this->release;
  }

  function concern()
  {
    return $this->concern;
  }

  function apply($id)
  {
    $conn = connection::getInstance();

    if (!$conn || !$conn->isok())
      return true;

    $validate_process = array();

    $sql = 'SELECT id, ssel_id, usr_id FROM validate';

    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        $validate_process[$row['ssel_id']][$row['usr_id']] = $row['id'];
      }
      $conn->free_result($rs);
    }

    $sql = 'SELECT u.*, s.ssel_id FROM sselcontusr u, sselcont c, ssel s' .
            ' WHERE s.ssel_id = c.ssel_id AND u.sselcont_id = c.sselcont_id' .
            ' AND s.deleted="0" ' .
            ' ORDER BY s.ssel_id ASC, c.sselcont_id ASC';

    if ($rs = $conn->query($sql))
    {
      while ($row = $conn->fetch_assoc($rs))
      {
        if (!isset($validate_process[$row['ssel_id']]) || !array_key_exists($row['usr_id'], $validate_process[$row['ssel_id']]))
        {
          //insert ligne de process

          $expire = new DateTime($row['dateFin']);
          $expire = $expire->format('u') == 0 ? null : phraseadate::format_mysql($expire);


          $sql = 'INSERT INTO validate
					(id, ssel_id, created_on, updated_on, expires_on, last_reminder, usr_id, confirmed, can_agree, can_see_others, can_hd) VALUES 
					(null, "' . $conn->escape_string($row['ssel_id']) . '", "' . $conn->escape_string($row['date_maj']) . '", "' . $conn->escape_string($row['date_maj']) . '", ' . ($expire == null ? 'null' : '"' . $conn->escape_string($expire) . '"') . ',
					null, "' . $conn->escape_string($row['usr_id']) . '", "0", "' . $conn->escape_string($row['canAgree']) . '", "' . $conn->escape_string($row['canSeeOther']) . '", "' . $conn->escape_string($row['canHD']) . '")';

          if ($conn->query($sql))
          {
            $validate_process[$row['ssel_id']][$row['usr_id']] = $conn->insert_id();
          }
        }

        //insert ligne d'avis

        $sql = 'INSERT INTO validate_datas
				(id, validate_id, sselcont_id, updated_on, agreement) 
				VALUES (null, "' . $conn->escape_string($validate_process[$row['ssel_id']][$row['usr_id']]) . '", "' . $conn->escape_string($row['sselcont_id']) . '", "' . $conn->escape_string($row['date_maj']) . '", "' . $conn->escape_string($row['agree']) . '")';
        $conn->query($sql);
      }
      $conn->free_result($rs);
    }

    return true;
  }

}
