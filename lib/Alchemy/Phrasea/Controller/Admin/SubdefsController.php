<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Media\Subdef\Provider;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Phrasea\Media\Type\Type;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\Video;
use Alchemy\Phrasea\Media\Subdef\Audio;
use Alchemy\Phrasea\Media\Subdef\Gif;

class SubdefsController extends Controller
{
    /**
     * @param int $sbas_id
     * @return Response
     */
    function indexAction($sbas_id) {
        $databox = $this->findDataboxById((int) $sbas_id);
        $config = $this->getConfiguration();
        $subviews_mapping = $this->getSubviewsMapping();

        return $this->render('admin/subdefs.html.twig', [
            'databox' => $databox,
            'subdefs' => $databox->get_subdef_structure(),
            'config' => $config,
            'subviews_mapping' => $subviews_mapping
        ]);
    }

    /**
     * @param Request $request
     * @param int     $sbas_id
     * @return Response
     * @throws \Exception
     */
    function changeSubdefsAction(Request $request, $sbas_id) {
        $delete_subdef = $request->request->get('delete_subdef');
        $toadd_subdef = $request->request->get('add_subdef');
        $Parmsubdefs = $request->request->get('subdefs', []);

        $databox = $this->findDataboxById((int) $sbas_id);

        $add_subdef = ['class' => null, 'name'  => null, 'group' => null, 'mediaType' => null, 'presets' => null];
        foreach ($add_subdef as $k => $v) {
            if (!isset($toadd_subdef[$k]) || trim($toadd_subdef[$k]) === '') {
                unset($add_subdef[$k]);
            } else {
                $add_subdef[$k] = $toadd_subdef[$k];
            }
        }

        if ($delete_subdef) {
            $delete_subef = explode('_', $delete_subdef, 2);
            $group = $delete_subef[0];
            $name = $delete_subef[1];
            $subdefs = $databox->get_subdef_structure();
            $subdefs->delete_subdef($group, $name);
        } elseif (count($add_subdef) === 5) {
            $subdefs = $databox->get_subdef_structure();

            $group = $add_subdef['group'];
            /** @var \unicode $unicode */
            $unicode = $this->app['unicode'];
            $name = $unicode->remove_nonazAZ09($add_subdef['name'], false);
            $class = $add_subdef['class'];
            $preset = $add_subdef['presets'];
            $mediatype = $add_subdef['mediaType'];

            $subdefs->add_subdef($group, $name, $class, $mediatype, $preset);

            if ($preset !== "Choose") {
                $options = [];

                $config = $this->getConfiguration();

                //On applique directement les valeurs du preset à la sous def
                switch($mediatype) {
                    case Subdef::TYPE_IMAGE :
                        $options["path"] = "";
                        $options["meta"] = true;
                        $options["mediatype"] = $mediatype;
                        $options[Image::OPTION_SIZE] = $config["image"]["definitions"][$preset][Image::OPTION_SIZE];
                        $options["dpi"] = $config["image"]["definitions"][$preset][Image::OPTION_RESOLUTION];
                        $options[Image::OPTION_STRIP] = $config["image"]["definitions"][$preset][Image::OPTION_STRIP];
                        $options[Image::OPTION_FLATTEN] = $config["image"]["definitions"][$preset][Image::OPTION_FLATTEN];
                        $options[Image::OPTION_QUALITY] = $config["image"]["definitions"][$preset][Image::OPTION_QUALITY];
                        $options[Image::OPTION_ICODEC] = $config["image"]["definitions"][$preset][Image::OPTION_ICODEC];
                        foreach($config["image"]["definitions"][$preset][Subdef::OPTION_DEVICE] as $devices) {
                            $options[Subdef::OPTION_DEVICE][] = $devices;
                        }
                        break;
                    case Subdef::TYPE_VIDEO :
                        $options["path"] = "";
                        $options["meta"] = true;
                        $options["mediatype"] = $mediatype;
                        $options[Video::OPTION_AUDIOBITRATE] = $config["video"]["definitions"][$preset][Video::OPTION_AUDIOBITRATE];
                        $options[Video::OPTION_AUDIOSAMPLERATE] = $config["video"]["definitions"][$preset][Video::OPTION_AUDIOSAMPLERATE];
                        $options[Video::OPTION_BITRATE] = $config["video"]["definitions"][$preset][Video::OPTION_BITRATE];
                        $options[Video::OPTION_GOPSIZE] = $config["video"]["definitions"][$preset][Video::OPTION_GOPSIZE];
                        $options[Video::OPTION_SIZE] = $config["video"]["definitions"][$preset][Video::OPTION_SIZE];
                        $options[Video::OPTION_FRAMERATE] = $config["video"]["definitions"][$preset][Video::OPTION_FRAMERATE];
                        $options[Video::OPTION_VCODEC] = $config["video"]["definitions"][$preset][Video::OPTION_VCODEC];
                        $options[Video::OPTION_ACODEC] = $config["video"]["definitions"][$preset][Video::OPTION_ACODEC];
                        foreach($config["video"]["definitions"][$preset][Subdef::OPTION_DEVICE] as $devices) {
                            $options[Subdef::OPTION_DEVICE][] = $devices;
                        }
                        break;
                    case Subdef::TYPE_FLEXPAPER :
                        $options["path"] = "";
                        $options["meta"] = true;
                        $options["mediatype"] = $mediatype;
                        foreach($config["document"]["definitions"][$preset]["devices"] as $devices) {
                            $options["devices"][] = $devices;
                        }
                        break;
                    case Subdef::TYPE_ANIMATION :
                        $options["path"] = "";
                        $options["meta"] = true;
                        $options["mediatype"] = $mediatype;
                        $options[Gif::OPTION_SIZE] = $config["gif"]["definitions"][$preset][Gif::OPTION_SIZE];
                        $options[Gif::OPTION_DELAY] = $config["gif"]["definitions"][$preset][Gif::OPTION_DELAY];
                        foreach($config["gif"]["definitions"][$preset][Subdef::OPTION_DEVICE] as $devices) {
                            $options[Subdef::OPTION_DEVICE][] = $devices;
                        }
                        break;
                    case Subdef::TYPE_AUDIO :
                        $options["path"] = "";
                        $options["meta"] = true;
                        $options["mediatype"] = $mediatype;
                        $options[Audio::OPTION_AUDIOBITRATE] = $config["audio"]["definitions"][$preset][Audio::OPTION_AUDIOBITRATE];
                        $options[Audio::OPTION_AUDIOSAMPLERATE] = $config["audio"]["definitions"][$preset][Audio::OPTION_AUDIOSAMPLERATE];
                        $options[Audio::OPTION_ACODEC] = $config["audio"]["definitions"][$preset][Audio::OPTION_ACODEC];
                        foreach($config["audio"]["definitions"][$preset][Subdef::OPTION_DEVICE] as $devices) {
                            $options[Subdef::OPTION_DEVICE][] = $devices;
                        }
                        break;
                }

                $subdefs->set_subdef($group, $name, $class, $preset, false, $options, []);
            }

        } else {
            $subdefs = $databox->get_subdef_structure();

            foreach ($Parmsubdefs as $post_sub) {
                $options = [];

                $post_sub_ex = explode('_', $post_sub, 2);

                $group = $post_sub_ex[0];
                $name = $post_sub_ex[1];

                $preset = $request->request->get($post_sub . '_presets');
                $class = $request->request->get($post_sub . '_class');
                $downloadable = $request->request->get($post_sub . '_downloadable');

                $defaults = ['path', 'meta', 'mediatype'];

                foreach ($defaults as $def) {
                    $parm_loc = $request->request->get($post_sub . '_' . $def);

                    if ($def == 'meta' && !$parm_loc) {
                        $parm_loc = "no";
                    }

                    $options[$def] = $parm_loc;
                }
                $mediatype = $request->request->get($post_sub . '_mediatype');
                $media = $request->request->get($post_sub . '_' . $mediatype, []);

                foreach ($media as $option => $value) {

                    if ($option == 'resolution' && $mediatype == 'image') {
                        $option = 'dpi';
                    }

                    $options[$option] = $value;
                }

                $labels = $request->request->get($post_sub . '_label', []);
                $subdefs->set_subdef($group, $name, $class, $preset, $downloadable, $options, $labels);
            }
        }

        return $this->app->redirectPath('admin_subdefs_subdef', [
            'sbas_id' => $databox->get_sbas_id(),
        ]);
    }

    /**
     * @return array
     */
    protected function getSubviewsMapping()
    {
        $mapping = array(
            Type::TYPE_IMAGE => array(Subdef::TYPE_IMAGE),
            Type::TYPE_VIDEO => array(Subdef::TYPE_IMAGE, Subdef::TYPE_VIDEO, Subdef::TYPE_ANIMATION),
            Type::TYPE_AUDIO => array(Subdef::TYPE_IMAGE, Subdef::TYPE_AUDIO),
            Type::TYPE_DOCUMENT => array(Subdef::TYPE_IMAGE, Subdef::TYPE_FLEXPAPER),
            Type::TYPE_FLASH => array(Subdef::TYPE_IMAGE)
        );

        return $mapping;
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        $config = array(
            Subdef::TYPE_IMAGE => array(
                "definitions" => array(
                    "JPG" => null,
                    "160px JPG" => array(
                        Image::OPTION_SIZE       => "160",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "jpeg",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "320 px JPG (thumbnail Phraseanet)" => array(
                        Image::OPTION_SIZE       => "320",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "jpeg",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "640px JPG" => array(
                        Image::OPTION_SIZE       => "640",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "jpeg",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "1280px JPG (preview Phraseanet)" => array(
                        Image::OPTION_SIZE       => "1280",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "jpeg",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "2560px JPG" => array(
                        Image::OPTION_SIZE       => "2560",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "jpeg",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "PNG" => null,
                    "160px PNG 8 bits" => array(
                        Image::OPTION_SIZE       => "160",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "png",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "320px PNG 8 bits" => array(
                        Image::OPTION_SIZE       => "320",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "png",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "640px PNG 8 bits" => array(
                        Image::OPTION_SIZE       => "640",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "png",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "1280px PNG 8 bits" => array(
                        Image::OPTION_SIZE       => "1280",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "png",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "2560px PNG 8 bits" => array(
                        Image::OPTION_SIZE       => "2560",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "png",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "TIFF" => null,
                    "1280 TIFF" => array(
                        Image::OPTION_SIZE       => "1280",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "tiff",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                    "2560px TIFF" => array(
                        Image::OPTION_SIZE       => "2560",
                        Image::OPTION_RESOLUTION => "75",
                        Image::OPTION_STRIP      => "yes",
                        Image::OPTION_FLATTEN    => "yes",
                        Image::OPTION_QUALITY    => "75",
                        Image::OPTION_ICODEC     => "tiff",
                        Subdef::OPTION_DEVICE     => ["all"]
                    ),
                ),
                "form" => array(
                    Image::OPTION_SIZE       => "slide",
                    Image::OPTION_RESOLUTION => "slide",
                    Image::OPTION_STRIP      => "radio",
                    Image::OPTION_FLATTEN    => "radio",
                    Image::OPTION_QUALITY    => "slide",
                    Image::OPTION_ICODEC     => "select",
                    Subdef::OPTION_DEVICE     => "checkbox",
                ),
            ),
            Subdef::TYPE_VIDEO => array(
                "definitions" => array(
                    "video codec H264" => null,
                    "144P H264 128 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "256",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "128",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libx264",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "240P H264 256 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "426",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "256",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libx264",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "360P H264 576 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "480",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "576",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libtheora",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "480P H264 750 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "854",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "750",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libx264",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "720P H264 1492 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "1280",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "1492",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libx264",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "1080P H264 2420 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "1920",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "2420",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libx264",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "video codec libvpx" => null,
                    "144P webm 128 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "256",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "128",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "240P webm 256 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "426",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "256",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "360P webm 576 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "480",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "576",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "480P webm 750 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "854",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "750",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "720P webm 1492 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "1280",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "1492",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                    "1080P webm 2420 kbps ACC 128kbps" => array(
                        Video::OPTION_AUDIOBITRATE    => "128",
                        Video::OPTION_AUDIOSAMPLERATE => "44100",
                        Video::OPTION_BITRATE         => "1920",
                        Video::OPTION_GOPSIZE         => "25",
                        Video::OPTION_SIZE            => "2420",
                        Video::OPTION_FRAMERATE       => "25",
                        Video::OPTION_VCODEC          => "libvpx",
                        Video::OPTION_ACODEC          => "libfaac",
                        Subdef::OPTION_DEVICE          => ["all"]
                    ),
                ),
                "form" => array(
                    Video::OPTION_AUDIOBITRATE    => "slide",
                    Video::OPTION_AUDIOSAMPLERATE => "select",
                    Video::OPTION_BITRATE         => "slide",
                    Video::OPTION_GOPSIZE         => "slide",
                    Video::OPTION_SIZE            => "slide",
                    Video::OPTION_FRAMERATE       => "slide",
                    Video::OPTION_VCODEC          => "select",
                    Video::OPTION_ACODEC          => "select",
                    Subdef::OPTION_DEVICE          => "checkbox",
                ),
            ),
            Subdef::TYPE_ANIMATION => array(
                "definitions" => array(
                    "256 px fast 200 ms" => array(
                        Gif::OPTION_SIZE    => "256",
                        Gif::OPTION_DELAY   => "200",
                        Subdef::OPTION_DEVICE  => ["all"]
                    ),
                    "256 px very fast 120 ms" => array(
                        Gif::OPTION_SIZE    => "256",
                        Gif::OPTION_DELAY   => "120",
                        Subdef::OPTION_DEVICE  => ["all"]
                    ),
                    "320 px fast 200 ms" => array(
                        Gif::OPTION_SIZE    => "320",
                        Gif::OPTION_DELAY   => "200",
                        Subdef::OPTION_DEVICE  => ["all"]
                    ),
                ),
                "form" => array(
                    Gif::OPTION_SIZE        => "slide",
                    Gif::OPTION_DELAY       => "slide",
                    Subdef::OPTION_DEVICE      => "checkbox",
                ),
            ),
            Subdef::TYPE_AUDIO => array(
                "definitions" => array(
                    "Low AAC 96 kbit/s" => array(
                        Audio::OPTION_AUDIOBITRATE      => "100",
                        Audio::OPTION_AUDIOSAMPLERATE   => "8000",
                        Audio::OPTION_ACODEC            => "libmp3lame",
                        Subdef::OPTION_DEVICE            => ["all"]
                    ),
                    "Normal AAC 128 kbit/s" => array(
                        Audio::OPTION_AUDIOBITRATE      => "180",
                        Audio::OPTION_AUDIOSAMPLERATE   => "44100",
                        Audio::OPTION_ACODEC            => "libmp3lame",
                        Subdef::OPTION_DEVICE            => ["all"]
                    ),
                    "High AAC 320 kbit/s" => array(
                        Audio::OPTION_AUDIOBITRATE      => "230",
                        Audio::OPTION_AUDIOSAMPLERATE   => "50000",
                        Audio::OPTION_ACODEC            => "libmp3lame",
                        Subdef::OPTION_DEVICE            => ["all"]
                    ),
                ),
                "form" => array(
                    Audio::OPTION_AUDIOBITRATE      => "slide",
                    Audio::OPTION_AUDIOSAMPLERATE   => "select",
                    Audio::OPTION_ACODEC            => "select",
                    Subdef::OPTION_DEVICE            => "checkbox",
                ),
            ),
            Subdef::TYPE_FLEXPAPER => array(
                "definitions" => array(
                ),
                "form" => array(),
            ),
        );

        return $config;
    }
}
