<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_373 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * @param base $databox
     */
    public function apply(base &$appbox)
    {
        $registry = $appbox->get_registry();

        $sql = 'UPDATE registry SET type = :type, value = :value
                WHERE `key` = :key';

        $stmt = $appbox->get_connection()->prepare($sql);

        $binaries = array(
            'GV_cli',
            'GV_imagick',
            'GV_pathcomposite',
            'GV_swf_extract',
            'GV_pdf2swf',
            'GV_swf_render',
            'GV_unoconv',
            'GV_ffmpeg',
            'GV_ffprobe',
            'GV_mp4box',
            'GV_pdftotext',
            'GV_cli',
        );

        foreach ($binaries as $binary) {

            $value = is_executable($registry->get($binary)) ? $registry->get($binary) : '';

            $stmt->execute(array(
                ':type'  => \registry::TYPE_BINARY,
                ':key'   => $binary,
                ':value' => $value,
            ));
        }

        $stmt->closeCursor();

        return true;
    }
}

