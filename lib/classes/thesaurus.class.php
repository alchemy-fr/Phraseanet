<?php

class thesaurus
{

  public static function xquery_escape($s)
  {
    return(str_replace(array("&", "\"", "'"), array("&amp;", "&quot;", "&apos;"), $s));
  }

}
