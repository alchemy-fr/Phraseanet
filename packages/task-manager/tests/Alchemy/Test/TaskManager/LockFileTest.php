<?php

namespace Alchemy\Test\TaskManager;

use Alchemy\TaskManager\LockFile;

class LockFileTest extends TestCase
{
    private $lockfile;
    private $lockfile2;

    protected function setUp(): void
    {
        $this->lockfile = __DIR__ . '/lockfiletest';
        $this->lockfile2 = __DIR__ . '/lockfiletest2';

        if (is_file($this->lockfile)) {
            unlink($this->lockfile);
        }
        if (is_file($this->lockfile2)) {
            unlink($this->lockfile2);
        }
    }

    public function testLockALockedFileShouldThrowAnException()
    {
        $lock = new LockFile($this->lockfile);
        $lock->lock();
        $this->assertFileExists($this->lockfile);

        $lock2 = new LockFile($this->lockfile);
        $this->expectException(\Alchemy\TaskManager\Exception\LockFailureException::class);
        $this->expectExceptionMessage(sprintf('Unable to lock %s.', $this->lockfile));
        $lock2->lock();
    }

    public function testLockAnUnlockedFile()
    {
        $lock = new LockFile($this->lockfile);
        $lock->lock();
        $this->assertFileExists($this->lockfile);
        $lock->unlock();
        $this->assertFileDoesNotExist($this->lockfile);

        $lock2 = new LockFile($this->lockfile);
        $lock2->lock();
        $this->assertFileExists($this->lockfile);
        $lock->unlock();
        $this->assertFileDoesNotExist($this->lockfile);
    }

    public function testUnlockAnUnlockedFileMakesNoProblem()
    {
        $lock = new LockFile($this->lockfile);
        $lock->unlock();
        $this->assertFileDoesNotExist($this->lockfile);
    }

    public function testUnlockAnExistingUnlockedRemovesTheFile()
    {
        touch($this->lockfile);
        $lock = new LockFile($this->lockfile);
        $lock->unlock();
        $this->assertFileDoesNotExist($this->lockfile);
    }

    public function testLockTwoFilesInParallelMakesNoProblem()
    {
        $lock = new LockFile($this->lockfile);
        $lock2 = new LockFile($this->lockfile2);
        $lock->lock();
        $lock2->lock();
        $this->assertFileExists($this->lockfile);
        $this->assertFileExists($this->lockfile2);
        $lock->unlock();
        $lock2->unlock();
        $this->assertFileDoesNotExist($this->lockfile);
        $this->assertFileDoesNotExist($this->lockfile2);
    }
}
