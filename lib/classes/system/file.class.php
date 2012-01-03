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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class system_file extends SplFileObject
{

  /**
   *
   * @var string
   */
  protected $mime;

  protected $technical_datas;

  /**
   *
   * @var Array
   */
  protected static $mimeTypes = array(
      'ai' => 'application/postscript'
      , '3gp' => 'video/3gpp'
      , 'aif' => 'audio/aiff'
      , 'aiff' => 'audio/aiff'
      , 'asf' => 'video/x-ms-asf'
      , 'asx' => 'video/x-ms-asf'
      , 'avi' => 'video/avi'
      , 'bmp' => 'image/bmp'
      , 'bz2' => 'application/x-bzip'
      , '3fr' => 'image/x-tika-hasselblad'
      , 'arw' => 'image/x-tika-sony'
      , 'bay' => 'image/x-tika-casio'
      , 'cap' => 'image/x-tika-phaseone'
      , 'cr2' => 'image/x-tika-canon'
      , 'crw' => 'image/x-tika-canon'
      , 'dcs' => 'image/x-tika-kodak'
      , 'dcr' => 'image/x-tika-kodak'
      , 'dng' => 'image/x-tika-dng'
      , 'drf' => 'image/x-tika-kodak'
      , 'erf' => 'image/x-tika-epson'
      , 'fff' => 'image/x-tika-imacon'
      , 'iiq' => 'image/x-tika-phaseone'
      , 'kdc' => 'image/x-tika-kodak'
      , 'k25' => 'image/x-tika-kodak'
      , 'mef' => 'image/x-tika-mamiya'
      , 'mos' => 'image/x-tika-leaf'
      , 'mrw' => 'image/x-tika-minolta'
      , 'nef' => 'image/x-tika-nikon'
      , 'nrw' => 'image/x-tika-nikon'
      , 'orf' => 'image/x-tika-olympus'
      , 'pef' => 'image/x-tika-pentax'
      , 'ppm' => 'image/x-portable-pixmap'
      , 'ptx' => 'image/x-tika-pentax'
      , 'pxn' => 'image/x-tika-logitech'
      , 'raf' => 'image/x-tika-fuji'
      , 'raw' => 'image/x-tika-panasonic'
      , 'r3d' => 'image/x-tika-red'
      , 'rw2' => 'image/x-tika-panasonic'
      , 'rwz' => 'image/x-tika-rawzor'
      , 'sr2' => 'image/x-tika-sony'
      , 'srf' => 'image/x-tika-sony'
      , 'x3f' => 'image/x-tika-sigma'
      , 'css' => 'text/css'
      , 'doc' => 'application/msword'
      , 'docx' => 'application/msword'
      , 'eps' => 'application/postscript'
      , 'exe' => 'application/x-msdownload'
      , 'flv' => 'video/x-flv'
      , 'gif' => 'image/gif'
      , 'gz' => 'application/x-gzip'
      , 'htm' => 'text/html'
      , 'html' => 'text/html'
      , 'jpeg' => 'image/jpeg'
      , 'jpg' => 'image/jpeg'
      , 'm3u' => 'audio/x-mpegurl'
      , 'mid' => 'audio/mid'
      , 'midi' => 'audio/mid'
      , 'mkv' => 'video/matroska'
      , 'mp3' => 'audio/mpeg'
      , 'mp4' => 'video/mp4'
      , 'vob' => 'video/mpeg'
      , 'mp2p' => 'video/mpeg'
      , 'mpeg' => 'video/mpeg'
      , 'mpg' => 'video/mpeg'
      , 'mov' => 'video/quicktime'
      , 'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
      , 'odt' => 'application/vnd.oasis.opendocument.text'
      , 'odp' => 'application/vnd.oasis.opendocument.presentation'
      , 'ogg' => 'audio/ogg'
      , 'pdf' => 'application/pdf'
      , 'pls' => 'audio/scpls'
      , 'png' => 'image/png'
      , 'pps' => 'application/vnd.ms-powerpoint'
      , 'ppt' => 'application/vnd.ms-powerpoint'
      , 'pptx' => 'application/vnd.ms-powerpoint'
      , 'psd' => 'image/psd'
      , 'ra' => 'audio/x-pn-realaudio'
      , 'ram' => 'audio/x-pn-realaudio'
      , 'rm' => 'application/vnd.rn-realmedia'
      , 'rtf' => 'application/msword'
      , 'rv' => 'video/vnd.rn-realvideo'
      , 'swf' => 'application/x-shockwave-flash'
      , 'tar' => 'application/x-tar'
      , 'tif' => 'image/tiff'
      , 'txt' => 'text/plain'
      , 'wav' => 'audio/wav'
      , 'wma' => 'audio/x-ms-wma'
      , 'wmv' => 'video/x-ms-wmv'
      , 'wmx' => 'video/x-ms-wmx'
      , 'xls' => 'application/excel'
      , 'xlsx' => 'application/excel'
      , 'xml' => 'text/xml'
      , 'xsl' => 'text/xsl'
      , 'zip' => 'application/zip'
  );

  protected $sha256;
  protected $uuid;

  const TC_DATAS_WIDTH = 'Width';
  const TC_DATAS_HEIGHT = 'Height';
  const TC_DATAS_COLORSPACE = 'ColorSpace';
  const TC_DATAS_CHANNELS = 'Channels';
  const TC_DATAS_ORIENTATION = 'Orientation';
  const TC_DATAS_COLORDEPTH = 'ColorDepth';
  const TC_DATAS_DURATION = 'Duration';
  const TC_DATAS_AUDIOCODEC = 'AudioCodec';
  const TC_DATAS_AUDIOSAMPLERATE = 'AudioSamplerate';
  const TC_DATAS_AUDIOBITRATE = 'AudioBitrate';
  const TC_DATAS_VIDEOBITRATE = 'VideoBitrate';
  const TC_DATAS_VIDEOCODEC = 'VideoCodec';
  const TC_DATAS_FRAMERATE = 'FrameRate';
  const TC_DATAS_MIMETYPE = 'MimeType';
  const TC_DATAS_FILESIZE = 'FileSize';

  /**
   *
   * @return string
   */
  public function get_mime()
  {
    if ($this->mime)
    {
      return $this->mime;
    }

    $registry = registry::get_instance();

    $mime = '';

    if (function_exists('finfo_open'))
    {
      $magicfile = NULL;
      if (is_file('/usr/share/misc/magic'))
      {
        $magicfile = '/usr/share/misc/magic';
      }
      elseif (is_file('/usr/share/misc/magic.mgc'))
      {
        $magicfile = '/usr/share/misc/magic.mgc';
      }
      elseif (is_file($registry->get('GV_RootPath') . 'www/include/magic'))
      {
        $magicfile = $registry->get('GV_RootPath') . 'www/include/magic';
      }

      if (($finfo = @finfo_open(FILEINFO_MIME, $magicfile)) !== false)
      {
        $mime = finfo_file($finfo, $this->getPathname());
        finfo_close($finfo);
      }
      elseif (($finfo = @finfo_open(FILEINFO_MIME, NULL)) !== false)
      {
        $mime = finfo_file($finfo, $this->getPathname());
        finfo_close($finfo);
      }
    }

    $extension = $this->get_extension(true);

    if (trim($mime) == '')
    {
      $gis = getimagesize($this->getPathname());
      if ($gis['mime'] != '')
        $mime = $gis['mime'];
    }

    if ($mime == '' || $mime == NULL)
      $mime = mime_content_type($this->getPathname());

    if (( $pos = strpos($mime, '; charset=')) !== false)
    {
      $mime = substr($mime, 0, $pos);
    }

    if ($mime == 'application/pdf' && $extension == 'ai')
      $mime = 'image/vnd.adobe.illustrator';
    elseif ($mime == 'text/plain' && $extension == 'mkv')
      $mime = 'video/matroska';
    elseif (in_array($mime, array(
                'application/octet-stream',
                'image/tiff',
                'application/vnd.ms-office',
                'application/zip'
                    )
            ) && isset(self::$mimeTypes[$extension]))
      $mime = self::$mimeTypes[$extension];
    elseif ($mime == '' && $extension == 'm4v')
      $mime = 'video/x-m4v';

    $this->mime = $mime;

    return $mime;
  }

  public function is_raw_image()
  {
    $raws = array(
        '3fr' => 'image/x-tika-hasselblad'
        , 'arw' => 'image/x-tika-sony'
        , 'bay' => 'image/x-tika-casio'
        , 'cap' => 'image/x-tika-phaseone'
        , 'cr2-' => 'image/x-canon-cr2'
        , 'cr2' => 'image/x-tika-canon'
        , 'crw' => 'image/x-tika-canon'
        , 'dcs' => 'image/x-tika-kodak'
        , 'dcr' => 'image/x-tika-kodak'
        , 'dng' => 'image/x-tika-dng'
        , 'drf' => 'image/x-tika-kodak'
        , 'erf' => 'image/x-tika-epson'
        , 'fff' => 'image/x-tika-imacon'
        , 'iiq' => 'image/x-tika-phaseone'
        , 'kdc' => 'image/x-tika-kodak'
        , 'k25' => 'image/x-tika-kodak'
        , 'mef' => 'image/x-tika-mamiya'
        , 'mos' => 'image/x-tika-leaf'
        , 'mrw' => 'image/x-tika-minolta'
        , 'nef' => 'image/x-tika-nikon'
        , 'nrw' => 'image/x-tika-nikon'
        , 'orf' => 'image/x-tika-olympus'
        , 'pef' => 'image/x-tika-pentax'
        , 'ppm' => 'image/x-portable-pixmap'
        , 'ptx' => 'image/x-tika-pentax'
        , 'pxn' => 'image/x-tika-logitech'
        , 'raf' => 'image/x-tika-fuji'
        , 'raw' => 'image/x-tika-panasonic'
        , 'r3d' => 'image/x-tika-red'
        , 'rw2' => 'image/x-tika-panasonic'
        , 'rwz' => 'image/x-tika-rawzor'
        , 'sr2' => 'image/x-tika-sony'
        , 'srf' => 'image/x-tika-sony'
        , 'x3f' => 'image/x-tika-sigma');


    if (in_array($this->get_mime(), $raws))

      return true;
    return false;
  }

  /**
   *
   * @param boolean $lowercase
   * @return string
   */
  public function get_extension($lowercase = false)
  {
    /**
     *
     * SplFileInfo::getExtension added in 5.3.6
     * @see https://bugs.php.net/bug.php?id=48767
     *
     */
    if (method_exists($this, 'getExtension'))
    {
      $extension = $this->getExtension();
    }
    else
    {
      $pi = pathinfo($this->getFilename());
      $extension = isset($pi['extension']) ? $pi['extension'] : '';
    }

    if ($lowercase)

      return mb_strtolower($extension);
    else

      return $extension;
  }

  public function get_sha256()
  {
    if (!$this->sha256)
      $this->sha256 = hash_file('sha256', $this->getPathname());

    return $this->sha256;
  }

  public function get_technical_datas()
  {
    if (!$this->technical_datas)
      $this->read_technical_datas();

    return $this->technical_datas;
  }

  protected function read_image_datas()
  {
    $this->technical_datas[self::TC_DATAS_ORIENTATION] = null;
    if (in_array(
                    $this->get_mime(), array(
                'image/tif', 'image/tiff',
                'image/jpg', 'image/pjpeg', 'image/pjpg', 'image/jpeg'
                    )
            )
    )
    {
      if ($ex = @exif_read_data($this->getPathname(), 'FILE'))
      {
        if (array_key_exists('Orientation', $ex))
          $this->technical_datas[self::TC_DATAS_ORIENTATION] = $ex['Orientation'];
      }
    }

    $datas = exiftool::extract_metadatas($this, exiftool::EXTRACT_XML_RDF);

    $domrdf = new DOMDocument();
    $domrdf->recover = true;
    $domrdf->preserveWhiteSpace = false;

    if ($domrdf->loadXML($datas))
    {
      $xptrdf = new DOMXPath($domrdf);
      $xptrdf->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

      $pattern = "(xmlns:([a-zA-Z-_0-9]+)=[']{1}(https?:[/{2,4}|\\{2,4}][\w:#%/;$()~_?/\-=\\\.&]*)[']{1})";
      preg_match_all($pattern, $datas, $matches, PREG_PATTERN_ORDER, 0);

      $xptrdf->registerNamespace('XMP-exif', 'http://ns.exiftool.ca/XMP/XMP-exif/1.0/');
      $xptrdf->registerNamespace('PHRASEANET', 'http://phraseanet.com/metas/PHRASEANET/1.0/');
      foreach ($matches[2] as $key => $value)
        $xptrdf->registerNamespace($matches[1][$key], $value);

      $descriptionNode = @$xptrdf->query('/rdf:RDF/rdf:Description')->item(0);
      if ($descriptionNode)
      {
        for ($x = $descriptionNode->firstChild; $x; $x = $x->nextSibling)
        {
          if ($x->nodeType !== XML_ELEMENT_NODE)
            continue;

          switch ($x->nodeName)
          {
            case 'Composite:ImageSize':
              if ((count($_t = explode('x', $x->textContent))) == 2)
              {
                $this->technical_datas[self::TC_DATAS_WIDTH] = 0 + $_t[0];
                $this->technical_datas[self::TC_DATAS_HEIGHT] = 0 + $_t[1];
              }
              break;
            case 'ExifIFD:ExifImageWidth':
              if (!array_key_exists('width', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_WIDTH] = 0 + $x->textContent;
              break;
            case 'ExifIFD:ExifImageHeight':
              if (!array_key_exists('height', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_HEIGHT] = 0 + $x->textContent;
              break;
            case 'File:ColorComponents':
            case 'IFD0:SamplesPerPixel':
              if (!array_key_exists('channels', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_CHANNELS] = 0 + $x->textContent;
              break;
            case 'File:BitsPerSample':
            case 'IFD0:BitsPerSample':
              if (!array_key_exists('bits', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_COLORDEPTH] = 0 + $x->textContent;
              break;
          }

          if (count($_t = explode(':', $x->nodeName)) !== 2)
            continue;

          switch ($_t[1])
          {
            case 'ImageWidth':
              if (!array_key_exists('width', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_WIDTH] = 0 + $x->textContent;
              break;
            case 'ImageHeight':
              if (!array_key_exists('height', $this->technical_datas))
                $this->technical_datas[self::TC_DATAS_HEIGHT] = 0 + $x->textContent;
              break;
          }
        }
      }
    }

    return $this;
  }

  protected function read_pdf_datas()
  {
    $system = system_server::get_platform();
    $registry = registry::get_instance();
    $cmd = $pdf_text = '';
    $tmpfile = $registry->get('GV_RootPath')
            . 'tmp/pdf-extract' . time() . mt_rand(00000, 99999);
    if ($system == 'DARWIN' || $system == 'LINUX')
    {
      $cmd = $registry->get('GV_pdftotext') . ' -f 1 -l '
              . $registry->get('GV_pdfmaxpages')
              . ' -raw -enc UTF-8 -eol unix -q '
              . str_replace(' ', '\ ', addslashes($this->getPathname()))
              . ' ' . $tmpfile;
    }
    else // WINDOWS
    {
      $cmd = $registry->get('GV_pdftotext') . ' -f 1 -l '
              . $registry->get('GV_pdfmaxpages')
              . ' -raw -enc UTF-8 -eol unix -q '
              . str_replace(' ', '\ ', addslashes($this->getPathname()))
              . ' ' . $tmpfile;
    }

    if ($cmd)
    {
      $s = shell_exec($cmd);

      if (file_exists($tmpfile))
      {
        $pdf_text = array(file_get_contents($tmpfile));
        unlink($tmpfile);
      }
    }

    return $pdf_text;
  }

  protected function read_video_datas()
  {
    $registry = registry::get_instance();

    $retour = array('width' => 0, 'height' => 0, 'CMYK' => false);

    $hdPath = $this->getPathname();

    $datas = exiftool::get_fields(
                    $hdPath, array('Duration', 'Image Width', 'Image Height')
    );
    $duration = 0;

    if ($datas['Duration'])
    {
      $data = explode('_', trim($datas['Duration']));
      $data = explode(':', $data[0]);

      $factor = 1;
      while ($segment = array_pop($data))
      {
        $duration += $segment * $factor;
        $factor *=60;
      }
    }
    $width = $height = false;
    if ($datas['Image Width'])
    {
      if ((int) $datas['Image Width'] > 0)
        $width = $datas['Image Width'];
    }
    if ($datas['Image Height'])
    {
      if ((int) $datas['Image Height'] > 0)
        $height = $datas['Image Height'];
    }

    $this->technical_datas = array();

    if (!is_executable($registry->get('GV_mplayer')))

      return $this;

    $cmd = $registry->get('GV_mplayer')
            . ' -identify '
            . escapeshellarg($hdPath)
            . '  -ao null -vo null -frames 0 | grep ^ID_';
    $docProps = array(
        'ID_VIDEO_WIDTH' => self::TC_DATAS_WIDTH,
        'ID_VIDEO_HEIGHT' => self::TC_DATAS_HEIGHT,
        'ID_VIDEO_FPS' => self::TC_DATAS_FRAMERATE,
        'ID_AUDIO_CODEC' => self::TC_DATAS_AUDIOCODEC,
        'ID_VIDEO_CODEC' => self::TC_DATAS_VIDEOCODEC,
        'ID_VIDEO_BITRATE' => self::TC_DATAS_VIDEOBITRATE,
        'ID_AUDIO_BITRATE' => self::TC_DATAS_AUDIOBITRATE,
        'ID_AUDIO_RATE' => self::TC_DATAS_AUDIOSAMPLERATE
    );

    $stdout = shell_exec($cmd);

    $this->technical_datas = array();

    $stdout = explode("\n", $stdout);
    foreach ($stdout as $property)
    {
      $props = explode('=', $property);


      if (array_key_exists($props[0], $docProps))
        $this->technical_datas[$docProps[$props[0]]] = $props[1];
    }

    $datas = exiftool::extract_metadatas($this, exiftool::EXTRACT_XML_RDF);
    $dom_document = new DOMDocument();
    if ($dom_document->loadXML($datas))
    {
      $xq = new DOMXPath($dom_document);
      $xq->registerNamespace('RIFF', 'http://ns.exiftool.ca/RIFF/RIFF/1.0/');
      $nodes_width = $xq->query('/rdf:RDF/rdf:Description/RIFF:ImageWidth');
      $nodes_height = $xq->query('/rdf:RDF/rdf:Description/RIFF:ImageHeight');

      if ($nodes_height->length > 0 && $nodes_width->length > 0)
      {
        $width = $nodes_width->item(0)->nodeValue;
        $height = $nodes_height->item(0)->nodeValue;
      }
    }

    $this->technical_datas[self::TC_DATAS_DURATION] = $duration;
    if ($width)
      $this->technical_datas[self::TC_DATAS_WIDTH] = $width;
    if ($height)
      $this->technical_datas[self::TC_DATAS_HEIGHT] = $height;

    return $this;
  }

  protected function read_audio_datas()
  {
    return $this->read_video_datas();
  }

  protected function read_technical_datas()
  {
    $this->technical_datas = array();

    switch ($this->get_phrasea_type())
    {
      case 'image' :
        $this->read_image_datas();
        break;
      case 'document';
        $this->read_image_datas();
//        $this->read_pdf_datas();
        break;
      case 'video':
        $this->read_video_datas();
        break;
      case 'audio':
        $this->read_audio_datas();
        break;
      default:
        break;
    }

    $this->technical_datas[self::TC_DATAS_MIMETYPE] = $this->get_mime();
    $this->technical_datas[self::TC_DATAS_FILESIZE] = $this->getSize();

    return;
  }

  /**
   *
   * @return string
   */
  public function get_phrasea_type()
  {
    switch ($this->get_mime())
    {
      case 'image/png':
      case 'image/gif':
      case 'image/bmp':
      case 'image/x-ms-bmp':
      case 'image/jpeg':
      case 'image/pjpeg':
      case 'image/psd':
      case 'image/photoshop':
      case 'image/vnd.adobe.photoshop':
      case 'image/ai':
      case 'image/illustrator':
      case 'image/vnd.adobe.illustrator':
      case 'image/tiff':
      case 'image/x-photoshop':
      case 'application/postscript':
      case 'image/x-tika-canon':
      case 'image/x-canon-cr2':
      case 'image/x-tika-casio':
      case 'image/x-tika-dng':
      case 'image/x-tika-epson':
      case 'image/x-tika-fuji':
      case 'image/x-tika-hasselblad':
      case 'image/x-tika-imacon':
      case 'image/x-tika-kodak':
      case 'image/x-tika-leaf':
      case 'image/x-tika-logitech':
      case 'image/x-tika-mamiya':
      case 'image/x-tika-minolta':
      case 'image/x-tika-nikon':
      case 'image/x-tika-olympus':
      case 'image/x-tika-panasonic':
      case 'image/x-tika-pentax':
      case 'image/x-tika-phaseone':
      case 'image/x-tika-rawzor':
      case 'image/x-tika-red':
      case 'image/x-tika-sigma':
      case 'image/x-tika-sony':
      case 'image/x-portable-pixmap':
        $type = 'image';
        break;

      case 'video/mpeg':
      case 'video/mp4':
      case 'video/x-ms-wmv':
      case 'video/x-ms-wmx':
      case 'video/avi':
      case 'video/mp2p':
      case 'video/mp4':
      case 'video/x-ms-asf':
      case 'video/quicktime':
      case 'video/matroska':
      case 'video/x-msvideo':
      case 'video/x-ms-video':
      case 'video/x-flv':
      case 'video/avi':
      case 'video/3gpp':
      case 'video/x-m4v':
      case 'application/vnd.rn-realmedia':
        $type = 'video';
        break;

      case 'audio/aiff':
      case 'audio/aiff':
      case 'audio/x-mpegurl':
      case 'audio/mid':
      case 'audio/mid':
      case 'audio/mpeg':
      case 'audio/ogg':
      case 'audio/mp4':
      case 'audio/scpls':
      case 'audio/vnd.rn-realaudio':
      case 'audio/x-pn-realaudio':
      case 'audio/wav':
      case 'audio/x-wav':
      case 'audio/x-ms-wma':
      case 'audio/x-flac':
        $type = 'audio';
        break;

      case 'text/plain':
      case 'application/msword':
      case 'application/access':
      case 'application/pdf':
      case 'application/excel':
      case 'application/vnd.ms-powerpoint':
      case 'application/vnd.oasis.opendocument.formula':
      case 'application/vnd.oasis.opendocument.text-master':
      case 'application/vnd.oasis.opendocument.database':
      case 'application/vnd.oasis.opendocument.formula':
      case 'application/vnd.oasis.opendocument.chart':
      case 'application/vnd.oasis.opendocument.graphics':
      case 'application/vnd.oasis.opendocument.presentation':
      case 'application/vnd.oasis.opendocument.speadsheet':
      case 'application/vnd.oasis.opendocument.text':
        $type = 'document';
        break;

      case 'application/x-shockwave-flash':
        $type = 'flash';
        break;

      default:
        $type = 'unknown';
        break;
    }

    return $type;
  }

  public function getPath()
  {
    return p4string::addEndSlash(parent::getPath());
  }

  public function has_uuid()
  {
    $this->read_uuid();

    return!!$this->uuid;
  }

  public function read_uuid()
  {
    $registry = registry::get_instance();

    if ($this->uuid)

      return $this->uuid;

    $datas = exiftool::extract_metadatas($this, exiftool::EXTRACT_XML_RDF);
    $domrdf = new DOMDocument();
    $domrdf->recover = true;
    $domrdf->preserveWhiteSpace = false;

    if ($domrdf->loadXML($datas))
    {
      $this->uuid = $this->test_rdf_fields($domrdf);
    }

    return $this->uuid;
  }

  public function write_uuid($uuid = false)
  {
    if ($uuid && uuid::is_valid($uuid))
    {
      $this->uuid = $uuid;
    }
    elseif ((($uuid = $this->read_uuid()) !== false) && uuid::is_valid($uuid))
    {
      $this->uuid = $uuid;
    }
    else
    {
      $this->uuid = uuid::generate_v4();
    }

    $this->write();

    return $this->uuid;
  }

  public function is_new_in_base($sbas_id)
  {
    if (!$this->uuid)

      return true;

    $connbas = connection::getPDOConnection($sbas_id);

    $sql = 'SELECT record_id FROM record WHERE uuid = :uuid';
    $stmt = $connbas->prepare($sql);
    $stmt->execute(array(':uuid' => $this->uuid));
    $num_rows = $stmt->rowCount();
    $stmt->closeCursor();

    return ($num_rows == 0);
  }

  public function generate_and_write()
  {
    $this->uuid = uuid::generate_v4();
    $this->write();

    return $this;
  }

  public function write()
  {
    $system = system_server::get_platform();
    $registry = registry::get_instance();

    if (in_array($system, array('DARWIN', 'LINUX')))
    {
      $cmd = $registry->get('GV_exiftool') . ' -m -overwrite_original -XMP-exif:ImageUniqueID=\'' . $this->uuid . '\' -IPTC:UniqueDocumentID=\'' . $this->uuid . '\' ' . escapeshellarg($this->getPathname()) . '';
    }
    else // WINDOWS
    {
      if (chdir($registry->get('GV_RootPath') . 'tmp/'))
      {
        $cmd = 'start /B /LOW ' . $registry->get('GV_exiftool') . ' -m -overwrite_original -XMP-exif:ImageUniqueID=\'' . $this->uuid . '\' -IPTC:UniqueDocumentID=\'' . $this->uuid . '\' ' . escapeshellarg($this->getPathname()) . '';
      }
    }
    if ($cmd)
    {
      $s = @shell_exec($cmd);
    }

    return $this;
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
          if (uuid::is_valid($value))

            return $value;
        }
      }
    }

    return false;
  }

  public static function mkdir($path, $depth=0)
  {
    clearstatcache();
    $registry = registry::get_instance();
    if (!is_dir($path))
    {
      $p = dirname($path);
      if ($p != "\\" && $p != "/" && $p != "." && $depth < 40)
        self::mkdir($p, $depth + 1);
      if (!is_dir($path))
      {
        mkdir($path);
        if (is_dir($path))
        {
          $group = trim($registry->get('GV_filesGroup'));

          if ($group !== '' && function_exists('chgrp'))
            chgrp($path, $group);

          $user = trim($registry->get('GV_filesOwner'));

          if ($user !== '' && function_exists('chown'))
            chown($path, $user);
          $system_file = new self($path);
          $system_file->chmod();
          unset($system_file);
        }
      }
    }

    return is_dir($path);
  }

  public function chmod()
  {
    if (function_exists('chmod'))
    {
      if (is_dir($this->getPathname()))
        chmod($this->getPathname(), 0755);
      if (is_file($this->getPathname()))
        chmod($this->getPathname(), 0766);
    }

    return true;
  }

  public function empty_directory()
  {
    $origine = p4string::addEndSlash($this->getPathname());

    $dirs = $files = array();

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($origine), RecursiveIteratorIterator::LEAVES_ONLY) as $file)
    {
      $pathfile = $file->getRealPath();
      if (substr($file->getFilename(), 0, 1) == '.' || strpos($pathfile, '.svn') !== false)
      {
        continue;
      }
      $path = p4string::addEndSlash($file->getPath());
      if ($path != $origine)
        $dirs[$path] = $path;
      $files[] = $pathfile;
    }

    foreach ($files as $file)
    {
      unlink($file);
    }

    arsort($dirs);

    foreach ($dirs as $dir)
    {
      rmdir($dir);
    }

    return $this;
  }

  protected $phrasea_tech_field = array();

  const TECH_FIELD_SUBPATH = 'subpath';
  const TECH_FIELD_PARENTDIRECTORY = 'parentdirectory';
  const TECH_FIELD_ORIGINALNAME = 'originalname';

  public function set_phrasea_tech_field($field, $value)
  {
    if (trim($field) === '')
      throw new Exception_InvalidArgument();
    $this->phrasea_tech_field[$field] = $value;
  }

  public function get_phrasea_tech_field($field)
  {
    if (isset($this->phrasea_tech_field[$field]))

      return $this->phrasea_tech_field[$field];
    return null;
  }

  public function extract_metadatas(databox_descriptionStructure $meta_struct, system_file $caption_file = null)
  {
    $ret = array();

    $tfields = array();

    $technical_datas = $this->get_technical_datas();

    $tfields[metadata_description_PHRASEANET_tfmimetype::get_source()]
            = array($this->get_mime());
    $tfields[metadata_description_PHRASEANET_tfsize::get_source()]
            = array($this->getSize());
    $tfields[metadata_description_PHRASEANET_tffilepath::get_source()]
            = array($this->get_phrasea_tech_field(self::TECH_FIELD_SUBPATH));
    $tfields[metadata_description_PHRASEANET_tfparentdir::get_source()]
            = array($this->get_phrasea_tech_field(self::TECH_FIELD_PARENTDIRECTORY));
    $tfields[metadata_description_PHRASEANET_tffilename::get_source()]
            = array($this->get_phrasea_tech_field(self::TECH_FIELD_ORIGINALNAME));

    $tfields[metadata_description_PHRASEANET_tfextension::get_source()]
            = array($this->get_extension());
    $tfields[metadata_description_PHRASEANET_tfwidth::get_source()]
            = isset($technical_datas[system_file::TC_DATAS_WIDTH]) ? array(($technical_datas[system_file::TC_DATAS_WIDTH])) : array();
    $tfields[metadata_description_PHRASEANET_tfheight::get_source()]
            = isset($technical_datas[system_file::TC_DATAS_HEIGHT]) ? array(($technical_datas[system_file::TC_DATAS_HEIGHT])) : array();
    $tfields[metadata_description_PHRASEANET_tfbits::get_source()]
            = isset($technical_datas[system_file::TC_DATAS_COLORDEPTH]) ? array(($technical_datas[system_file::TC_DATAS_COLORDEPTH])) : array();
    $tfields[metadata_description_PHRASEANET_tfchannels::get_source()]
            = isset($technical_datas[system_file::TC_DATAS_CHANNELS]) ? array(($technical_datas[system_file::TC_DATAS_CHANNELS])) : array();

    $tfields[metadata_description_PHRASEANET_tfctime::get_source()]
            = array(date('Y/m/d H:i:s', $this->getCTime()));
    $tfields[metadata_description_PHRASEANET_tfmtime::get_source()]
            = array(date('Y/m/d H:i:s', $this->getMTime()));
    $tfields[metadata_description_PHRASEANET_tfatime::get_source()]
            = array(date('Y/m/d H:i:s', $this->getATime()));

    $time = time();

    $tfields[metadata_description_PHRASEANET_tfarchivedate::get_source()]
            = array(date('Y/m/d H:i:s', $time));
    $tfields[metadata_description_PHRASEANET_tfeditdate::get_source()]
            = array(date('Y/m/d H:i:s', $time));
    $tfields[metadata_description_PHRASEANET_tfchgdocdate::get_source()]
            = array(date('Y/m/d H:i:s', $time));

    if ($this->get_mime() === 'application/pdf')
    {
      $tfields[metadata_description_PHRASEANET_pdftext::get_source()]
              = $this->read_pdf_datas();
    }

    $datas = exiftool::extract_metadatas($this, exiftool::EXTRACT_XML_RDF);
    $domrdf = new DOMDocument();
    $domrdf->recover = true;
    $domrdf->preserveWhiteSpace = false;

    if ($domrdf->loadXML($datas))
    {
      $xptrdf = new DOMXPath($domrdf);

      $defined_namespaces = array(
          'rdf' => true
          , 'XMP-exif' => true
          , 'PHRASEANET' => true
      );

      $xptrdf->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');

      $pattern = "(xmlns:([a-zA-Z-_0-9]+)=[']{1}(https?:[/{2,4}|\\{2,4}][\w:#%/;$()~_?/\-=\\\.&]*)[']{1})";
      preg_match_all($pattern, $datas, $matches, PREG_PATTERN_ORDER, 0);

      $xptrdf->registerNamespace('XMP-exif', 'http://ns.exiftool.ca/XMP/XMP-exif/1.0/');
      $xptrdf->registerNamespace('PHRASEANET', 'http://phraseanet.com/metas/PHRASEANET/1.0/');
      foreach ($matches[2] as $key => $value)
      {
        $defined_namespaces[$matches[1][$key]] = true;
        $xptrdf->registerNamespace($matches[1][$key], $value);
      }
      // tous les champs de la structure
      foreach ($meta_struct as $meta)
      {

        if (!array_key_exists($meta->get_metadata_namespace(), $defined_namespaces))
        {
          $xptrdf->registerNamespace($meta->get_metadata_namespace(), 'http://ns.exiftool.ca/' . $meta->get_metadata_namespace() . '/1.0/');
          $defined_namespaces[$meta->get_metadata_namespace()] = true;
        }
        $fname = $meta->get_name();

        $src = $meta->get_metadata_source();
        if (!$src)
          continue;

        $x = $xptrdf->query($src);
        if (!$x || $x->length != 1)
        {
          continue;
        }

        if (!isset($tfields[$src]))
        {
          $tfields[$src] = array();
        }
        $x = $x->item(0);

        //double check -- exiftool uses et:encoding in version prior 7.71
        $encoding = strtolower($x->getAttribute('rdf:datatype') . $x->getAttribute('et:encoding'));
        $base64_encoded = (strpos($encoding, 'base64') !== false);

        $bag = $xptrdf->query('rdf:Bag', $x);
        if ($bag && $bag->length == 1)
        {
          $li = $xptrdf->query('rdf:li', $bag->item(0));
          if ($li->length > 0)
          {
//            $tfields[$src] = array();
            for ($ili = 0; $ili < $li->length; $ili++)
            {
              $value = $base64_encoded ? base64_decode($li->item($ili)->nodeValue) : $li->item($ili)->nodeValue;
              $utf8value = trim($this->guessCharset($value));
              $tfields[$src][] = $utf8value;
            }
          }
        }
        else
        {
          if (($v = $x->firstChild) && $v->nodeType == XML_TEXT_NODE)
          {
            $value = $base64_encoded ? base64_decode($v->nodeValue) : $v->nodeValue;
            $utf8value = $this->guessCharset($value);
            $tfields[$src] = array($utf8value);
          }
        }
      }
    }


    foreach ($meta_struct as $meta)
    {
      $fname = $meta->get_name();
      $src = $meta->get_metadata_source();
      $typ = mb_strtolower($meta->get_type()); // l'attribut 'type' du champ
      $multi = $meta->is_multi();    // l'attribut 'multi' du champ

      if (trim($src) === '' || isset($tfields[$src]) === false)
        continue;

      if (trim(implode('', $tfields[$src])) === '')
        continue;

      // un champ iptc peut etre multi-value, on recoit donc toujours un tableau comme valeur
      $tmpval = array();
      foreach ($tfields[$src] as $val)
      {
        // on remplace les caracteres de controle (tous < 32 sauf 9,10,13)
        $val = $this->kill_ctrlchars($val);

        if ($typ == 'date')
        {
          $val = str_replace(array('-', ':', '/', '.'), array(' ', ' ', ' ', ' '), $val);
          $ip_date_yyyy = 0;
          $ip_date_mm = 0;
          $ip_date_dd = 0;
          $ip_date_hh = 0;
          $ip_date_nn = 0;
          $ip_date_ss = 0;
          switch (sscanf($val, '%d %d %d %d %d %d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss))
          {
            case 1:
              $val = sprintf('%04d/00/00 00:00:00', $ip_date_yyyy);
              break;
            case 2:
              $val = sprintf('%04d/%02d/00 00:00:00', $ip_date_yyyy, $ip_date_mm);
              break;
            case 3:
              $val = sprintf('%04d/%02d/%02d 00:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd);
              break;
            case 4:
              $val = sprintf('%04d/%02d/%02d %02d:00:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh);
              break;
            case 5:
              $val = sprintf('%04d/%02d/%02d %02d:%02d:00', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn);
              break;
            case 6:
              $val = sprintf('%04d/%02d/%02d %02d:%02d:%02d', $ip_date_yyyy, $ip_date_mm, $ip_date_dd, $ip_date_hh, $ip_date_nn, $ip_date_ss);
              break;
            default:
              $val = '0000/00/00 00:00:00';
          }
        }

        if (!in_array($val, $tmpval))
          $tmpval[] = $val;
      }

      foreach ($tmpval as $val)
      {
        $ret[$meta->get_id()] = array(
            'meta_struct_id' => $meta->get_id(),
            'meta_id' => null,
            'value' => array($val)
        );
      }
    }


    $statBit = null;
    $sxcaption = null;
    if (!is_null($caption_file))
    {
      // on a une description xml en plus a lire dans un fichier externe
      if ($domcaption = @DOMDocument::load($caption_file->getPathname()))
      {
        if ($domcaption->documentElement->tagName == 'description') // il manque 'record' (ca commence par 'description') : on repare
        {
          $newdomcaption = new DOMDocument('1.0', 'UTF-8');
          $newdomcaption->standalone = true;
          $newdomrec = $newdomcaption->appendChild($newdomcaption->createElement('record'));
          $newdomrec->appendChild($newdomcaption->importNode($domcaption->documentElement, true));
          $sxcaption = simplexml_load_string($newdomcaption->saveXML());
        }
        else
        {
          $sxcaption = simplexml_load_file($caption_file->getPathname());
        }
        if ($inStatus = $sxcaption->status)
        {
          if ($inStatus && $inStatus != '')
          {
            $statBit = $inStatus;
          }
        }
      }

      if ($sxcaption)
      {
        $ret = $this->meta_merge($meta_struct, $ret, $sxcaption);
      }
    }

    return(array('metadatas' => $ret, 'status' => $statBit));
  }

  protected function meta_merge(databox_descriptionStructure &$meta_struct, Array $metadatas, SimpleXMLElement $sxcaption)
  {
    foreach ($sxcaption->description->children() as $fn => $fld)
    {
      $fv = trim((string) $fld);

      $meta = $meta_struct->get_element_by_name($fn);

      if (!$meta)
      {
        continue;
      }

      if ($meta->get_type() == 'date')
      {
        if ($fld['format'])
        {
          $fv = phraseadate::dateToIsodate($fv, $fld['format']);
        }
      }

      if ($meta->is_multi())
      {
        $fv = caption_field::get_multi_values($fv, $meta->get_separator());
      }
      else
      {
        $fv = array($fv);
      }

      if (isset($metadatas[$meta->get_id()]) && $meta->is_multi() === true)
      {
        $fv = array_unique(array_merge($metadatas[$meta->get_id()], $fv));
      }

      $metadatas[$meta->get_id()] = array(
          'meta_struct_id' => $meta->get_id(),
          'meta_id' => null,
          'value' => $fv
      );

      unset($meta);
    }

    return $metadatas;
  }

  protected function guessCharset($s)
  {
    // (8x except 85, 8C) + (9x except 9C) + (BC, BD, BE)
    static $macchars = "\x81\x82\x83\x84\x86\x87\x88\x89\x8A\x8B\x8D\x8E\x8F\x90\x91\x92\x93\x94\x95\x96\x97\x98\x99\x9A\x9B\x9D\x9E\x9F\xBC\xBD\xBE";

    if (mb_convert_encoding(mb_convert_encoding($s, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32') == $s)
    {
      $mac = mb_convert_encoding($s, 'windows-1252', 'UTF-8');
      for ($i = strlen($mac); $i;)
      {
        if (strpos($macchars, $mac[--$i]) !== false)

          return(iconv('MACINTOSH', 'UTF-8', $mac));
      }

      return($s);
    }
    else
    {
      for ($i = strlen($s); $i;)
      {
        if (strpos($macchars, $s[--$i]) !== false)

          return(iconv('MACINTOSH', 'UTF-8', $s));
      }

      return(iconv('windows-1252', 'UTF-8', $s));
    }
  }

  protected function kill_ctrlchars($s)  // ok en utf8 !
  {
    static $a_in = null;
    static $a_out = null;
    if ($a_in === null)
    {
      $a_in = array();
      $a_out = array();
      for ($cc = 0; $cc < 32; $cc++)
      {
        if ($cc != 10 && $cc != 13 && $cc != 9)
        {
          $a_in[] = chr($cc);
          $a_out[] = '_';
        }
      }
    }

    return(str_replace($a_in, $a_out, $s));
  }

}

if (!function_exists('mime_content_type'))
{

  /**
   *
   * @param string $f
   * @return string
   */
  function mime_content_type($f)
  {
    $ext2mime = array(
        'dwg' => 'application/acad'  // Fichiers AutoCAD
        , 'ccad' => 'application/clariscad'  // Fichiers ClarisCAD
        , 'drw' => 'application/drafting'  // Fichiers MATRA Prelude drafting
        , 'dxf' => 'application/dxf'  // Fichiers AutoCAD
        , 'unv' => 'application/i-deas'  // Fichiers SDRC I-deas
        , 'igs' => 'application/iges'  // Format d'echange CAO IGES
        , 'iges' => 'application/iges'  // Format d'echange CAO IGES
        , 'bin' => 'application/octet-stream'  // Fichiers binaires non interpretes
        , 'oda' => 'application/oda'  // Fichiers ODA
        , 'pdf' => 'application/pdf'  // Fichiers Adobe Acrobat
        , 'ai' => 'application/postscript'  // Fichiers PostScript
        , 'eps' => 'application/postscript'  // Fichiers PostScript
        , 'ps' => 'application/postscript'  // Fichiers PostScript
        , 'prt' => 'application/pro_eng'  // Fichiers ProEngineer
        , 'rtf' => 'application/rtf'  // Format de texte enrichi
        , 'set' => 'application/set'  // Fichiers CAO SET
        , 'stl' => 'application/sla'  // Fichiers stereolithographie
        , 'dwg' => 'application/solids'  // Fichiers MATRA Solids
        , 'step' => 'application/step'  // Fichiers de donnees STEP
        , 'vda' => 'application/vda'  // Fichiers de surface
        , 'mif' => 'application/x-mif'  // Fichiers Framemaker
        , 'dwg' => 'application/x-csh'  // Script C-Shell (UNIX)
        , 'dvi' => 'application/x-dvi'  // Fichiers texte dvi
        , 'hdf' => 'application/hdf'  // Fichiers de donnees
        , 'latex' => 'application/x-latex'  // Fichiers LaTEX
        , 'nc' => 'application/x-netcdf'  // Fichiers netCDF
        , 'cdf' => 'application/x-netcdf'  // Fichiers netCDF
        , 'dwg' => 'application/x-sh'  // Script Bourne Shell
        , 'tcl' => 'application/x-tcl'  // Script Tcl
        , 'tex' => 'application/x-tex'  // fichiers Tex
        , 'texinfo' => 'application/x-texinfo'  // Fichiers eMacs
        , 'texi' => 'application/x-texinfo'  // Fichiers eMacs
        , 't' => 'application/x-troff'  // Fichiers Troff
        , 'tr' => 'application/x-troff'  // Fichiers Troff
        , 'troff' => 'application/x-troff'  // Fichiers Troff
        , 'man' => 'application/x-troff-man'  // Fichiers Troff/macro man
        , 'me' => 'application/x-troff-me'  // Fichiers Troff/macro ME
        , 'ms' => 'application/x-troff-ms'  // Fichiers Troff/macro MS
        , 'src' => 'application/x-wais-source'  // Source Wais
        , 'bcpio' => 'application/x-bcpio'  // CPIO binaire
        , 'cpio' => 'application/x-cpio'  // CPIO Posix
        , 'gtar' => 'application/x-gtar'  // Tar GNU
        , 'shar' => 'application/x-shar'  // Archives Shell
        , 'sv4cpio' => 'application/x-sv4cpio'  // CPIO SVR4n
        , 'sc4crc' => 'application/x-sv4crc'  // CPIO SVR4 avec CRC
        , 'tar' => 'application/x-tar'  // Fichiers compresses tar
        , 'man' => 'application/x-ustar'  // Fichiers compresses tar Posix
        , 'man' => 'application/zip'  // Fichiers compresses ZIP
        , 'au' => 'audio/basic'  // Fichiers audio basiques
        , 'snd' => 'audio/basic'  // Fichiers audio basiques
        , 'aif' => 'audio/x-aiff'  // Fichiers audio AIFF
        , 'aiff' => 'audio/x-aiff'  // Fichiers audio AIFF
        , 'aifc' => 'audio/x-aiff'  // Fichiers audio AIFF
        , 'wav' => 'audio/x-wav'  // Fichiers audio Wave
        , 'man' => 'image/gif'  // Images gif
        , 'ief' => 'image/ief'  // Images exchange format
        , 'jpg' => 'image/jpeg'  // Images Jpeg
        , 'jpeg' => 'image/jpeg'  // Images Jpeg
        , 'jpe' => 'image/jpeg'  // Images Jpeg
        , 'tiff' => 'image/tiff'  // Images Tiff
        , 'tif' => 'image/tiff'  // Images Tiff
        , 'cmu' => 'image/x-cmu-raster'  // Raster cmu
        , 'pnm' => 'image/x-portable-anymap'  // Fichiers Anymap PBM
        , 'pbm' => 'image/x-portable-bitmap'  // Fichiers Bitmap PBM
        , 'pgm' => 'image/x-portable-graymap'  // Fichiers Graymap PBM
        , 'ppm' => 'image/x-portable-pixmap'  // Fichiers Pixmap PBM
        , 'rgb' => 'image/x-rgb'  // Image RGB
        , 'xbm' => 'image/x-xbitmap'  // Images Bitmap X
        , 'xpm' => 'image/x-xpixmap'  // Images Pixmap X
        , 'man' => 'image/x-xwindowdump'  // Images dump X Window
        , 'zip' => 'multipart/x-zip'  // Fichiers archive zip
        , 'gz' => 'multipart/x-gzip'  // Fichiers archive GNU zip
        , 'gzip' => 'multipart/x-gzip'  // Fichiers archive GNU zip
        , 'htm' => 'text/html'  // Fichiers HTML
        , 'html' => 'text/html'  // Fichiers HTML
        , 'txt' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'g' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'h' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'c' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'cc' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'hh' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'm' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'f90' => 'text/plain'  // Fichiers texte sans mise en forme
        , 'rtx' => 'text/richtext'  // Fichiers texte enrichis
        , 'tsv' => 'text/tab-separated-value'  // Fichiers texte avec separation des valeurs
        , 'etx' => 'text/x-setext'  // Fichiers texte Struct
        , 'mpeg' => 'video/mpeg'  // Videos MPEG
        , 'mpg' => 'video/mpeg'  // Videos MPEG
        , 'mpe' => 'video/mpeg'  // Videos MPEG
        , 'qt' => 'video/quicktime'  // Videos QuickTime
        , 'mov' => 'video/quicktime'  // Videos QuickTime
        , 'avi' => 'video/msvideo'  // Videos Microsoft Windows
        , 'movie' => 'video/x-sgi-movie'  // Videos MoviePlayer
    );
    $fileinfo = new system_file($f);
    $extension = $fileinfo->get_extension(true);

    return array_key_exists($ext, $ext2mime) ?
            $ext2mime[$ext] : 'application/octet-stream';
  }

}
