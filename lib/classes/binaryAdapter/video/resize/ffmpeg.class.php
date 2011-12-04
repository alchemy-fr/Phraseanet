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
 * FFMPEG video resize processor for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_video_resize_ffmpeg extends binaryAdapter_video_processorAbstract implements binaryAdapter_processorInterface
{

  /**
   *
   * @var array
   */
  protected $options = array(
      'size' => 400,
      'fps' => 25,
      'threads' => 1,
      'bitrate' => 1000,
      'v_codec' => 'libx264',
      'a_codec' => 'libfaac'
  );
  /**
   *
   * @var string
   */
  protected $binary_name = 'GV_ffmpeg';

  /**
   *
   * @param string $name
   * @param string $value
   * @return binaryAdapter_video_resize_ffmpeg
   */
  protected function set_option($name, $value)
  {
    switch ($name)
    {
      case 'fps':
        $value = (int) $value;
        $value = ($value <= 1 || $value > 200) ? 25 : $value;
        break;
      case 'threads':
        $value = (int) $value;
        $value = ($value <= 1 || $value > 32) ? 1 : $value;
        break;
      case 'bitrate':
        $value = (int) $value;
        $value = ($value <= 100 || $value > 50000) ? 1000 : $value;
        break;
      case 'v_codec':
        $v_codecs = array(
            'x264' => 'libx264',
            'h264' => 'libx264',
            'libx264' => 'libx264',
            'libh264' => 'h264',
            'flv' => 'flv',
            'flash' => 'flv'
        );
        $value = array_key_exists($value, $v_codecs) ? $v_codecs[$value] : 'libx264';
        break;
      case 'a_codec':

        $a_codecs = array(
            'faac' => 'libfaac',
            'libfaac' => 'libfaac',
            'mp3' => 'libmp3lame'
        );
        $value = array_key_exists($value, $a_codecs) ? $a_codecs[$value] : 'libfaac';
        break;
    }

    parent::set_option($name, $value);

    return $this;
  }

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @return system_file
   */
  protected function process(system_file $origine, $dest)
  {

    $tech_datas = $origine->get_technical_datas();

    $srcWidth = $tech_datas[system_file::TC_DATAS_WIDTH];
    $srcHeight = $tech_datas[system_file::TC_DATAS_HEIGHT];
    $srcFPS = $tech_datas[system_file::TC_DATAS_FRAMERATE];
    $srcAR = isset($tech_datas[system_file::TC_DATAS_AUDIOSAMPLERATE]) ?
            $tech_datas[system_file::TC_DATAS_AUDIOSAMPLERATE] : null;
    $srcAB = isset($tech_datas[system_file::TC_DATAS_AUDIOBITRATE]) ?
            intval($tech_datas[system_file::TC_DATAS_AUDIOBITRATE] / 1000) : null;

    $dimensions = $this->get_dimensions($origine, $this->options['size']);

    $newHeight = $dimensions['height'];
    $newWidth = $dimensions['width'];

    $cwd = getcwd();
    chdir($this->registry->get('GV_RootPath') . 'tmp/');

    if ($this->debug)
      $this->log("GENERATING video ");

    if ($this->options['v_codec'] == 'flv')
      $dest = $this->set_extension($dest, 'flv');
    else
      $dest = $this->set_extension($dest, 'mp4');

    $dest_pass1 = $dest . '-tmp.mp4';

    $system = system_server::get_platform();

    if ($system == 'WINDOWS')
    {
      $cmd_part1 = $this->binary->getPathname()
              . ' -y -i \''
              . str_replace('/', "\\", $origine->getPathname()) . '\' ';
    }
    else
    {
      $cmd_part1 = $this->binary->getPathname()
              . ' -y -i '
              . $this->escapeshellargs($origine->getPathname()) . ' ';
    }

    if (in_array($this->options['v_codec'], array('libx264', 'h264')))
    {

      $cmd_part2 = ' -s ' . $newWidth . 'x' . $newHeight
              . ' -r ' . $this->options['fps']
              . ' -vcodec ' . trim($this->options['v_codec'])
              . '  -b ' . trim($this->options['bitrate']) . 'k -g 25 -bf 3'
              . ' -threads ' . $this->options['threads']
              . ' -refs 6 -b_strategy 1 -coder 1 -qmin 10 -qmax 51 '
              . ' -sc_threshold 40 -flags +loop -cmp +chroma'
              . ' -me_range 16 -subq 7 -i_qfactor 0.71 -qcomp 0.6 -qdiff 4 '
              . ' -directpred 3 -flags2 +dct8x8+wpred+bpyramid+mixed_refs'
              . ' -trellis 1 '
              . ' -partitions +parti8x8+parti4x4+partp8x8+partp4x4+partb8x8 '
              . '-acodec ' . trim($this->options['a_codec']) . ' -ab 92k ';

      $cmd_pass1 = $cmd_part1 . ' -pass 1 ' . $cmd_part2
              . ' -an ' . $this->escapeshellargs($dest_pass1);
      $cmd_pass2 = $cmd_part1 . ' -pass 2 ' . $cmd_part2
              . ' -ac 2 -ar 44100 ' . $this->escapeshellargs($dest);

      if (is_file($dest))
        unlink($dest);

      $this->shell_cmd($cmd_pass1);

      $this->shell_cmd($cmd_pass2);

      $files = array(
          'ffmpeg2pass-0.log',
          'x264_2pass.log',
          'x264_2pass.log.mbtree'
      );
      foreach ($files as $file)
      {
        if (is_file($file))
          unlink($file);
      }

      if (is_file($dest_pass1))
        unlink($dest_pass1);
    }
    else
    {

      $audioEnc = '';
      if (trim($srcAB) != '' && trim($srcAB) != '')
      {
        $okMp3BR = array('44100' => true, '22050' => true, '11025' => true);
        if (!isset($srcAR)
                || trim($srcAR) == ''
                || !array_key_exists($srcAR, $okMp3BR))
        {
          $srcAR = '44100';
        }

        if ($srcAB == '0' || trim($srcAB) == '')
          $srcAB = '0';

        $audioEnc = ' -ar ' . $srcAR
                . ' -ab ' . $srcAB . 'k -acodec libmp3lame ';
      }

      if ($system == 'WINDOWS')
      {
        $cmd = $this->binary->getPathname()
                . ' -y -i \''
                . str_replace('/', "\\", $origine->getPathname()) . '\' ';
      }
      else
      {
        $cmd = $this->binary->getPathname()
                . ' -y -i '
                . $this->escapeshellargs($origine->getPathname()) . ' ';
      }

      $cmd .= $audioEnc .
              ' -f flv -nr 500 -s ' . $newWidth . 'x' . $newHeight . '' .
              ' -r ' . $this->options['fps'] .
              ' -b 270k -me_range ' . $srcFPS
              . ' -i_qfactor 0.71 -g 500 ' . $this->escapeshellargs($dest);


      if ($this->debug)
        $this->log("execution commande : " . $cmd_pass2);

      $this->shell_cmd($cmd);
    }

    chdir($cwd);

    return $this;
  }

}
