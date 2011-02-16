<?php

class patch_311 implements patch
{

  private $release = '3.1.1';
  private $concern = array('data_box');

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

    $connbas = connection::getInstance($id);

    if (!$connbas || !$connbas->isok())
      return false;

    $sql = 'UPDATE record SET jeton=' . (JETON_WRITE_META_DOC | JETON_WRITE_META_SUBDEF) . '';
    $connbas->query($sql);
    return true;
  }

}

