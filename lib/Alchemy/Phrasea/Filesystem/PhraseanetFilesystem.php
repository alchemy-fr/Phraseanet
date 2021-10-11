<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Filesystem;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class PhraseanetFilesystem extends Filesystem
{
    /**
     * Copies a file.
     *
     * Copied from \Symfony\Component\Filesystem\Filesystem::copy
     * - add : fflush
     * - add : copy to tmp then rename (atomic move)
     * - change : $overwriteNewerFiles useless ; target is always overwriten
     *
     * @param string $originFile          The original filename
     * @param string $targetFile          The target filename
     * @param bool   $overwriteNewerFiles __UNUSED__
     *
     * @throws FileNotFoundException When originFile doesn't exist
     * @throws IOException           When copy fails
     */
    public function copy($originFile, $targetFile, $overwriteNewerFiles = false)
    {
//        file_put_contents(dirname(__FILE__).'/../../../../logs/fs.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
//            sprintf("fs:copy(\"%s\", \"%s\")", $originFile, $targetFile)
//        ), FILE_APPEND | LOCK_EX);

        if (stream_is_local($originFile) && !is_file($originFile)) {
            throw new FileNotFoundException(sprintf('Failed to copy "%s" because file does not exist.', $originFile), 0, null, $originFile);
        }

        $tmpTarget = sprintf("%s.%s-%s", $targetFile, time(), rand(0, 9999));

        $this->mkdir(dirname($tmpTarget));

        // https://bugs.php.net/bug.php?id=64634
        if (false === $source = @fopen($originFile, 'r')) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because source file could not be opened for reading.', $originFile, $tmpTarget), 0, null, $originFile);
        }

        // Stream context created to allow files overwrite when using FTP stream wrapper - disabled by default
        if (false === $target = @fopen($tmpTarget, 'w', null, stream_context_create(array('ftp' => array('overwrite' => true))))) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s" because target file could not be opened for writing.', $originFile, $tmpTarget), 0, null, $originFile);
        }

        $bytesCopied = stream_copy_to_stream($source, $target);

        fclose($source);
        // fsync($target);   // only in php 8.1
        fflush($target);
        fclose($target);
        unset($source, $target);

//        file_put_contents(dirname(__FILE__).'/../../../../logs/fs.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
//            sprintf("   copied %g bytes to \"%s\")", $bytesCopied, $tmpTarget)
//        ), FILE_APPEND | LOCK_EX);

        if (!is_file($tmpTarget)) {
            throw new IOException(sprintf('Failed to copy "%s" to "%s".', $originFile, $tmpTarget), 0, null, $originFile);
        }

        // Like `cp`, preserve executable permission bits
        @chmod($tmpTarget, fileperms($tmpTarget) | (fileperms($originFile) & 0111));

        // move from tmp to final
        try {
            $this->rename($tmpTarget, $targetFile, true);
        }
        catch (\Exception $e) {
            $this->remove($tmpTarget);
            throw new IOException(sprintf('Failed to rename "%s" to "%s".', $tmpTarget, $targetFile), 0, null, $originFile);
        }

        if (stream_is_local($originFile) && $bytesCopied !== ($bytesOrigin = filesize($originFile))) {
            throw new IOException(sprintf('Failed to copy the whole content of "%s" to "%s" (%g of %g bytes copied).', $originFile, $targetFile, $bytesCopied, $bytesOrigin), 0, null, $originFile);
        }
    }

//    public function rename($origin, $target, $overwrite = false)
//    {
//        file_put_contents(dirname(__FILE__).'/../../../../logs/fs.txt', sprintf("\n%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
//            sprintf("fs:rename(\"%s\", \"%s\", %s)", $origin, $target, $overwrite?'true':'false')
//        ), FILE_APPEND | LOCK_EX);
//
//        parent::rename($origin, $target, $overwrite);
//    }
}

