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
class databox_subdef_document extends databox_subdefAbstract implements databox_subdefInterface
{

  /**
   *
   * @var array
   */
  protected $available_mediatypes = array('image', 'flexpaper');

  /**
   *
   * @param record_Interface $record
   * @param string $dest_dir
   * @param registry $registry
   * @return system_file
   */
  protected function generator_switcher(record_Interface &$record, $dest_dir, registry &$registry)
  {
    switch ($this->current_mediatype)
    {
      case 'flexpaper':
        $generated = $this->generate_flexpaper($record, $dest_dir, $registry);
        break;
      case 'image':
      default:
        $generated = $this->generate_image($record, $dest_dir, $registry);
        break;
    }

    return $generated;
  }

  /**
   *
   * @param record_Interface $record
   * @param string $dest_dir
   * @param registry $registry
   * @return system_file
   */
  protected function generate_flexpaper(record_Interface &$record, $dest_dir, registry &$registry)
  {
    $dest_dir = $this->get_newpathfile_name($record, $dest_dir, 'swf');
    $tmp_file = $registry->get('GV_RootPath') . 'tmp/tmp_file-doc2pdf' . time() . '.pdf';

    $swf = $exception = false;

    try
    {
      $pdf = $this->generate_pdf_from_doc($record, $tmp_file, $registry);
      $swf = $this->generate_swf_from_pdf($pdf, $dest_dir, $registry);
    }
    catch (Exception $e)
    {
      $exception = $e;
    }

    if (is_file($tmp_file))
      unlink($tmp_file);

    if ($swf instanceof system_file)
    {
      return $swf;
    }
    throw new Exception('Unable to convert document to swf : '
            . $exception->getMessage());
  }

  /**
   *
   * @param system_file $pdf
   * @param string $pathdest
   * @param registry $registry
   * @return system_file
   */
  protected function generate_swf_from_pdf(system_file $pdf, $pathdest, registry &$registry)
  {
    $adapter = new binaryAdapter_document_toFlexpaperSwf($registry);
    $swf = $adapter->execute($pdf, $pathdest, $this->get_options());

    if ($swf instanceof system_file)
    {
      return $swf;
    }
    throw new Exception('Unable to convert pdf to swf');
  }

  /**
   *
   * @param record_Interface $record
   * @param string $pathdest
   * @param registry $registry
   * @return system_file
   */
  protected function generate_image(record_Interface &$record, $pathdest, registry &$registry)
  {
    $pathdest = $this->get_newpathfile_name($record, $pathdest, 'jpg');
    $tmp_file = $registry->get('GV_RootPath') . 'tmp/tmp_file-doc2pdf' . time() . '.pdf';

    $image = $exception = false;

    try
    {
      $pdf = $this->generate_pdf_from_doc($record, $tmp_file, $registry);
      $image = $this->generate_image_from_pdf($pdf, $pathdest, $registry);
    }
    catch (Exception $e)
    {
      $exception = $e;
    }

    if (is_file($tmp_file))
      unlink($tmp_file);

    if ($image instanceof system_file)
    {
      return $image;
    }
    throw new Exception('Unable to convert document to image : '
            . $exception->getMessage());
  }

  /**
   *
   * @param record_Interface $record
   * @param string $tmp_file
   * @param registry $registry
   * @return system_file
   */
  protected function generate_pdf_from_doc(record_Interface &$record, $tmp_file, registry &$registry)
  {
    $origine = $record->get_hd_file();

    if ($origine->get_mime() == 'application/pdf')

      return $origine;

    $adapter = new binaryAdapter_document_toPDF($registry);
    $converted = $adapter->execute($origine, $tmp_file, $this->get_options());

    if ($converted instanceof system_file)
    {
      return $converted;
    }
    throw new Exception('Unable to convert document');
  }

  /**
   *
   * @param system_file $pdf
   * @param string $pathdest
   * @param registry $registry
   * @return system_file
   */
  protected function generate_image_from_pdf(system_file $pdf, $pathdest, registry &$registry)
  {
    $adapter = new binaryAdapter_image_resize($registry);
    $image = $adapter->execute($pdf, $pathdest, $this->get_options());

    if ($image instanceof system_file)
    {
      return $image;
    }
    throw new Exception('Unable to convert pdf to image');
  }

}
