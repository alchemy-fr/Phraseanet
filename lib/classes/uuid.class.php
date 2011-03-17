<?php

class uuid
{

  protected $storage = array();

  function __construct($filepath)
  {
    if (!(file_exists($filepath)))
    {
      throw new Exception('Le fichier n\'existe pas');
    }

    $this->filepath = $filepath;
  }

  public function has_uuid()
  {
    $this->read_uuid();
    return!!$this->uuid;
  }

  public function read_uuid()
  {
    require_once GV_RootPath . 'lib/index_utils2.php';
    $cmd = NULL;

    $system = p4utils::getSystem();

    if (in_array($system, array('DARWIN', 'LINUX')))
    {
      $cmd = GV_exiftool . ' -X -n -fast ' . escapeshellarg($this->filepath) . '';
    }
    else // WINDOWS
    {
      if (chdir(GV_RootPath . 'tmp/'))
      {
        $cmd = 'start /B /LOW ' . GV_exiftool . ' -X -n -fast ' . escapeshellarg($this->filepath) . '';
      }
    }
    if ($cmd)
    {
      $s = @shell_exec($cmd);
      if ($s != '')
      {

        $domrdf = new DOMDocument();
        $domrdf->recover = true;
        $domrdf->preserveWhiteSpace = false;

        if ($domrdf->loadXML($s))
        {
          $this->uuid = $this->test_rdf_fields($domrdf);
        }
      }
    }
    return $this->uuid;
  }

  public function write_uuid($uuid = false)
  {
    if ($uuid && self::uuid_is_valid($uuid))
    {
      $this->uuid = $uuid;
    }
    elseif ((($uuid = $this->read_uuid()) !== false) && self::uuid_is_valid($uuid))
    {
      $this->uuid = $uuid;
    }
    else
    {
      $this->uuid = self::generate_uuid();
    }

    $this->write();
    return $this->uuid;
  }

  public function is_new_in_base($sbas_id)
  {
    if (!$this->uuid)
      return true;

    $connbas = connection::getInstance($sbas_id);

    $sql = 'SELECT record_id FROM record WHERE uuid="' . $connbas->escape_string($this->uuid) . '"';
    if ($rs = $connbas->query($sql))
    {
      if ($connbas->num_rows($rs) > 0)
        return false;
      $connbas->free_result($rs);
    }

    return true;
  }

  public function generate_and_write()
  {
    $this->uuid = self::generate_uuid();
    $this->write();

    return $this;
  }

  public function write()
  {
    $system = p4utils::getSystem();

    if (in_array($system, array('DARWIN', 'LINUX')))
    {
      $cmd = GV_exiftool . ' -m -overwrite_original -XMP-exif:ImageUniqueID=\'' . $this->uuid . '\' -IPTC:UniqueDocumentID=\'' . $this->uuid . '\' ' . escapeshellarg($this->filepath) . '';
    }
    else // WINDOWS
    {
      if (chdir(GV_RootPath . 'tmp/'))
      {
        $cmd = 'start /B /LOW ' . GV_exiftool . ' -m -overwrite_original -XMP-exif:ImageUniqueID=\'' . $this->uuid . '\' -IPTC:UniqueDocumentID=\'' . $this->uuid . '\' ' . escapeshellarg($this->filepath) . '';
      }
    }
    if ($cmd)
    {
//			echo "mise ad jour : $cmd \n";
      $s = @shell_exec($cmd);
    }
    return $this;
  }

  public static function generate_uuid()
  {
    return phrasea_uuid_create();
  }

  private function test_rdf_fields($rdf_dom)
  {
    $xptrdf = new DOMXPath($rdf_dom);
    $xptrdf->registerNamespace('XMP-exif', 'http://ns.exiftool.ca/XMP/XMP-exif/1.0/');
    $xptrdf->registerNamespace('IPTC', 'http://ns.exiftool.ca/IPTC/IPTC/1.0/');

    $fields = array(
        '/rdf:RDF/rdf:Description/XMP-exif:ImageUniqueID',
        '/rdf:RDF/rdf:Description/IPTC:UniqueDocumentID'
    );

    foreach ($fields as $field)
    {
      $x = $xptrdf->query($field);

      if ($x->length > 0)
      {
        $x = $x->item(0);

        $encoding = strtolower($x->getAttribute('rdf:datatype') . $x->getAttribute('et:encoding'));
        $base64_encoded = (strpos($encoding, 'base64') !== false);

        if (($v = $x->firstChild) && $v->nodeType == XML_TEXT_NODE)
        {
          $value = $base64_encoded ? base64_decode($v->nodeValue) : $v->nodeValue;
          if (self::uuid_is_valid($value))
            return $value;
        }
      }
    }
    return false;
  }

  public static function uuid_is_valid($uuid)
  {
    return phrasea_uuid_is_valid($uuid);
  }

  public function __get($key)
  {
    if (isset($this->storage[$key]))
    {
      return $this->storage[$key];
    }
    return null;
  }

  public function __set($key, $value)
  {
    $this->storage[$key] = $value;

    return $this;
  }

  public function __isset($key)
  {
    if (isset($this->storage[$key]))
      return true;
    return false;
  }

}

