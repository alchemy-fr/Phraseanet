<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager;

use Alchemy\TaskManager\Exception\LockFailureException;

class LockFile
{
    private $file;
    private $handle;

    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Unlocks the lock file.
     *
     * @return LockFile
     */
    public function unlock()
    {
        if (is_resource($this->handle)) {
            flock($this->handle, LOCK_UN | LOCK_NB);
            ftruncate($this->handle, 0);
            fclose($this->handle);
        }

        if (is_file($this->file)) {
            unlink($this->file);
        }

        return $this;
    }

    /**
     * Locks the lock file.
     *
     * @return LockFile
     */
    public function lock()
    {
        $this->handle = fopen($this->file, 'a+');

        if (!is_resource($this->handle)) {
            throw new LockFailureException(sprintf('Unable to fopen %s.', $this->file));
        }

        $locker = true;

        if (false === flock($this->handle, LOCK_EX | LOCK_NB, $locker)) {
            fclose($this->handle);
            throw new LockFailureException(sprintf('Unable to lock %s.', $this->file));
        }

        ftruncate($this->handle, 0);
        fwrite($this->handle, (string) getmypid());
        fflush($this->handle);

        // For windows : unlock then lock shared to allow OTHER processes to read the file
        flock($this->handle, LOCK_UN);
        flock($this->handle, LOCK_SH);

        return $this;
    }
}
