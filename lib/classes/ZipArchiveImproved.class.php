<?php

/**
 * ZipArchiveImproved extends ZipArchive to add some information about the zip file and some functionality.
 *
 *
 *
 * @author Farzad Ghanei
 * @uses ZipArchive
 * @version 1.0.0 2009-01-18
 */
class ZipArchiveImproved extends ZipArchive
{

  protected $_archiveFileName = null;
  protected $_newAddedFilesCounter = 0;
  protected $_newAddedFilesSize = 100;

  /**
   * returns the name of the archive file.
   *
   * @return string
   */
  public function getArchiveFileName()
  {
    return $this->_archiveFileName;
  }

  /**
   * returns the number of files that are going to be added to ZIP
   * without reopenning the stream to file.
   *
   * @return int
   */
  public function getNewAddedFilesSize()
  {
    return $this->_newAddedFilesSize;
  }

  /**
   * sets the number of files that are going to be added to ZIP
   * without reopenning the stream to file. if no size is specified, default is 100.
   *
   * @param int
   * @return ZipArchiveImproved self reference
   */
  public function setNewlAddedFilesSize($size=100)
  {
    if (empty($size) || !is_int($size) || $size < 1)
    {
      $size = 100;
    }
    $this->_newAddedFilesSize = $size;

    return $this;
  }

  /**
   * opens a stream to a ZIP archive file. calls the ZipArchive::open() internally.
   * overwrites ZipArchive::open() to add the archiveFileName functionality.
   *
   * @param string $fileName
   * @param int $flags
   * return mixed
   */
  public function open($fileName, $flags)
  {
    $this->_archiveFileName = $fileName;
    $this->_newAddedFilesCounter = 0;

    return parent::open($fileName, $flags);
  }

  /**
   * closes the stream to ZIP archive file. calls the ZipArchive::close() internally.
   * overwrites ZipArchive::close() to add the archiveFileName functionality.
   *
   * @return bool
   */
  public function close()
  {
    $this->_archiveFileName = null;
    $this->_newAddedFilesCounter = 0;

    return parent::close();
  }

  /**
   * closes the connection to ZIP file and openes the connection again.
   *
   * @return bool
   */
  public function reopen()
  {
    $archiveFileName = $this->_archiveFileName;
    if (!$this->close())
    {
      return false;
    }

    return $this->open($archiveFileName, self::CREATE);
  }

  /**
   * adds a file to a ZIP archive from the given path. calls the ZipArchive::addFile() internally.
   * overwrites ZipArchive::addFile() to handle maximum file connections in operating systems.
   *
   * @param string $fileName the path to file to be added to archive
   * @param string [optional] $localname the name of the file in the ZIP archive
   * @return bool
   */
  public function addFile($fileName)
  {
    if ($this->_newAddedFilesCounter >= $this->_newAddedFilesSize)
    {
      $this->reopen();
    }
    if (func_num_args() > 1)
    {
      $flags = func_get_arg(1);
      $added = parent::addFile($fileName, $flags);
      if ($added)
      {
        $this->_newAddedFilesCounter++;
      }

      return $added;
    }
    $added = parent::addFile($fileName);
    if ($added)
    {
      $this->_newAddedFilesCounter++;
    }

    return $added;
  }

// public function addFile()
}
