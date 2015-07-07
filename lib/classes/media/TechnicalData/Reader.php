<?php

class media_TechnicalData_Reader
{
    /**
     * Technical datas types constants
     */
    const WIDTH = 'Width';
    const HEIGHT = 'Height';
    const COLORSPACE = 'ColorSpace';
    const CHANNELS = 'Channels';
    const ORIENTATION = 'Orientation';
    const COLORDEPTH = 'ColorDepth';
    const DURATION = 'Duration';
    const AUDIOCODEC = 'AudioCodec';
    const AUDIOSAMPLERATE = 'AudioSamplerate';
    const VIDEOCODEC = 'VideoCodec';
    const FRAMERATE = 'FrameRate';
    const MIMETYPE = 'MimeType';
    const FILESIZE = 'FileSize';
    const LONGITUDE = 'Longitude';
    const LATITUDE = 'Latitude';
    const FOCALLENGTH = 'FocalLength';
    const CAMERAMODEL = 'CameraModel';
    const FLASHFIRED = 'FlashFired';
    const APERTURE = 'Aperture';
    const SHUTTERSPEED = 'ShutterSpeed';
    const HYPERFOCALDISTANCE = 'HyperfocalDistance';
    const ISO = 'ISO';
    const LIGHTVALUE = 'LightValue';


    /**
     * Read the technical datas of the file.
     * Returns an empty array for non physical present files
     *
     * @param media_subdef $subdef
     * @param \MediaVorus\MediaVorus $mediavorus
     * @return array An array of technical datas Key/values
     */
    public function readTechnicalDatas(\media_subdef $subdef, \MediaVorus\MediaVorus $mediavorus)
    {
        if ( ! $subdef->is_physically_present()) {
            return array();
        }

        $media = $mediavorus->guess($subdef->get_pathfile());
        $datas = array();

        $methods = array(
            self::WIDTH              => 'getWidth',
            self::HEIGHT             => 'getHeight',
            self::FOCALLENGTH        => 'getFocalLength',
            self::CHANNELS           => 'getChannels',
            self::COLORDEPTH         => 'getColorDepth',
            self::CAMERAMODEL        => 'getCameraModel',
            self::FLASHFIRED         => 'getFlashFired',
            self::APERTURE           => 'getAperture',
            self::SHUTTERSPEED       => 'getShutterSpeed',
            self::HYPERFOCALDISTANCE => 'getHyperfocalDistance',
            self::ISO                => 'getISO',
            self::LIGHTVALUE         => 'getLightValue',
            self::COLORSPACE         => 'getColorSpace',
            self::DURATION           => 'getDuration',
            self::FRAMERATE          => 'getFrameRate',
            self::AUDIOSAMPLERATE    => 'getAudioSampleRate',
            self::VIDEOCODEC         => 'getVideoCodec',
            self::AUDIOCODEC         => 'getAudioCodec',
        );

        foreach ($methods as $tc_name => $method) {
            if (method_exists($media, $method)) {
                $result = call_user_func(array($media, $method));

                if (null !== $result) {
                    $datas[$tc_name] = $result;
                }
            }
        }

        $datas[self::LONGITUDE] = $media->getLongitude();
        $datas[self::LATITUDE] = $media->getLatitude();
        $datas[self::MIMETYPE] = $media->getFile()->getMimeType();
        $datas[self::FILESIZE] = $media->getFile()->getSize();

        unset($media);

        return $datas;
    }
}
