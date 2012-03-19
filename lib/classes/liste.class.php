<?php

class liste
{

  public static function filter($lst)
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $session = $appbox->get_session();

    if (!is_array($lst))
      explode(';', $lst);

    $okbrec = array();

    $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

    foreach ($lst as $basrec)
    {
      $basrec = explode("_", $basrec);
      if (!$basrec || count($basrec) != 2)
      {
        continue;
      }
      try
      {
        $record = new record_adapter($basrec[0], $basrec[1]);
      }
      catch(Exception $e)
      {
        continue;
      }

      if ($user->ACL()->has_hd_grant($record))
      {
        $okbrec[] = implode('_', $basrec);;
        continue;
      }
      if ($user->ACL()->has_preview_grant($record))
      {
        $okbrec[] = implode('_', $basrec);;
        continue;
      }

      if (!$user->ACL()->has_access_to_base($record->get_base_id()))
        continue;

      try
      {
        $connsbas = connection::getPDOConnection($basrec[0]);

        $sql = 'SELECT record_id FROM record WHERE ((status ^ ' . $user->ACL()->get_mask_xor($record->get_base_id()) . ')
                    & ' . $user->ACL()->get_mask_and($record->get_base_id()) . ')=0' .
                ' AND record_id = :record_id';

        $stmt = $connsbas->prepare($sql);
        $stmt->execute(array(':record_id' => $basrec[1]));

        if ($stmt->rowCount() > 0)
          $okbrec[] = implode('_', $basrec);

        $stmt->closeCursor();
      }
      catch (Exception $e)
      {

      }
    }

    return $okbrec;
  }

}
