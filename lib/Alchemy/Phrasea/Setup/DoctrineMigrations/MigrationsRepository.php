<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration as BaseMigration;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsSet;
use Doctrine\Migrations\MigrationsRepository as MigrationsRepositoryInterface;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;

/**
 * Migrations identified by the version strings stored in the migrations table
 * (e.g. "20131118000001") instead of their class names.
 *
 * Migrations are the explicitly listed ones plus the `Version<version>` classes
 * found in the migrations directory.
 */
class MigrationsRepository implements MigrationsRepositoryInterface
{
    /** @var array<string, AvailableMigration>|null */
    private $migrations;

    /**
     * @param array<string, class-string<BaseMigration>> $classes   Migration classes indexed by version
     * @param string                                     $directory Directory of the `Version<version>` migrations
     * @param string                                     $namespace Namespace of the `Version<version>` migrations
     */
    public function __construct(
        private readonly array $classes,
        private readonly string $directory,
        private readonly string $namespace,
        private readonly MigrationFactory $factory
    ) {
    }

    public function hasMigration(string $version): bool
    {
        return isset($this->loadMigrations()[$version]);
    }

    public function getMigration(Version $version): AvailableMigration
    {
        $migrations = $this->loadMigrations();

        if (!isset($migrations[(string) $version])) {
            throw MigrationClassNotFound::new((string) $version);
        }

        return $migrations[(string) $version];
    }

    public function getMigrations(): AvailableMigrationsSet
    {
        return new AvailableMigrationsSet($this->loadMigrations());
    }

    /**
     * @return array<string, AvailableMigration>
     */
    private function loadMigrations()
    {
        if (null !== $this->migrations) {
            return $this->migrations;
        }

        $classes = $this->classes;

        foreach (glob(rtrim($this->directory, '/') . '/Version*.php') ?: [] as $file) {
            $version = substr(basename($file, '.php'), strlen('Version'));
            $classes[$version] = $this->namespace . '\\Version' . $version;
        }

        $migrations = [];

        foreach ($classes as $version => $class) {
            $version = (string) $version;

            if (!class_exists($class)) {
                throw MigrationClassNotFound::new($class);
            }

            $migrations[$version] = new AvailableMigration(new Version($version), $this->factory->createVersion($class));
        }

        return $this->migrations = $migrations;
    }
}
