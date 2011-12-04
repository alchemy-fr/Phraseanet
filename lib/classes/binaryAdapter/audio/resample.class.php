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
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_audio_resample extends binaryAdapter_adapterAbstract
{

  /**
   *
   * @var string
   */
  protected $processors = array(
      'binaryAdapter_audio_resample_ffmpeg'
  );

  public function get_name()
  {
    return 'Binary adapter audio resampler';
  }

}
