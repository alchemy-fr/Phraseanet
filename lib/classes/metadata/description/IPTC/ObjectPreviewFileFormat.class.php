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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class metadata_description_IPTC_ObjectPreviewFileFormat extends metadata_Abstract implements metadata_Interface
{

  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:ObjectPreviewFileFormat';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'ObjectPreviewFileFormat';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT16U;
  const MULTI = false;

  public static function available_values()
  {
    return array(
          '0' => 'No ObjectData'
          ,'1' => 'IPTC-NAA Digital Newsphoto Parameter Record'
          ,'2' => 'IPTC7901 Recommended Message Format'
          ,'3' => 'Tagged Image File Format (Adobe/Aldus Image data)'
          ,'4' => 'Illustrator (Adobe Graphics data)'
          ,'5' => 'AppleSingle (Apple Computer Inc)'
          ,'6' => 'NAA 89-3 (ANPA 1312)'
          ,'7' => 'MacBinary II'
          ,'8' => 'IPTC Unstructured Character Oriented File Format (UCOFF)'
          ,'9' => 'United Press International ANPA 1312 variant'
          ,'10' => 'United Press International Down-Load Message'
          ,'11' => 'JPEG File Interchange (JFIF)'
          ,'12' => 'Photo-CD Image-Pac (Eastman Kodak)'
          ,'13' => 'Bit Mapped Graphics File [.BMP] (Microsoft)'
          ,'14' => 'Digital Audio File [.WAV] (Microsoft & Creative Labs)'
          ,'15' => 'Audio plus Moving Video [.AVI] (Microsoft)'
          ,'16' => 'PC DOS/Windows Executable Files [.COM][.EXE]'
          ,'17' => 'Compressed Binary File [.ZIP] (PKWare Inc)'
          ,'18' => 'Audio Interchange File Format AIFF (Apple Computer Inc)'
          ,'19' => 'RIFF Wave (Microsoft Corporation)'
          ,'20' => 'Freehand (Macromedia/Aldus)'
          ,'21' => 'Hypertext Markup Language [.HTML] (The Internet Society)'
          ,'22' => 'MPEG 2 Audio Layer 2 (Musicom), ISO/IEC'
          ,'23' => 'MPEG 2 Audio Layer 3, ISO/IEC'
          ,'24' => 'Portable Document File [.PDF] Adobe'
          ,'25' => 'News Industry Text Format (NITF)'
          ,'26' => 'Tape Archive [.TAR]'
          ,'27' => 'Tidningarnas Telegrambyra NITF version (TTNITF DTD)'
          ,'28' => 'Ritzaus Bureau NITF version (RBNITF DTD)'
          ,'29' => 'Corel Draw [.CDR]'
  );
  }
}


