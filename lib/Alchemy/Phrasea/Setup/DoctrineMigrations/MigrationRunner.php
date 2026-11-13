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
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\MigrationsRepository as MigrationsRepositoryInterface;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\DbalMigrationFactory;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManager;

/**
 * Looks up and runs Phraseanet Doctrine migrations one by one, by version.
 */
class MigrationRunner
{
    /**
     * Migrations that are not named after their version.
     */
    const MIGRATIONS = [
        '20131118000001' => FeedMigration::class,
        '20131118000002' => WorkzoneMigration::class,
        '20131118000003' => LazaretMigration::class,
        '20131118000004' => TaskMigration::class,
        '20131118000005' => AuthFailureMigration::class,
        '20131118000006' => SessionMigration::class,
        '20131118000007' => FtpCredentialMigration::class,
        '20131118000008' => OrderMigration::class,
        '20131118000009' => UserMigration::class,
        '20131118000010' => UserQueryMigration::class,
        '20131118000011' => UserSettingMigration::class,
        '20131118000012' => UserNotificationSettingMigration::class,
        '20131118000013' => FtpExportMigration::class,
        '20140219000001' => UserFieldMigration::class,
        '20140219000002' => DeleteUsrIdFieldMigration::class,
        '20140219000003' => UserListMigration::class,
        '20140219000004' => UserAuthProviderMigration::class,
        '20140226000001' => RegistrationMigration::class,
        '20140305000001' => TokenMigration::class,
        '20140324000001' => ApiMigration::class,
        '20140314000001' => AggregateTokenMigration::class,
        '20140314000002' => PresetMigration::class,
    ];

    /**
     * The migrations table created by Doctrine Migrations 1, whose `version` column
     * holds the versions above.
     */
    const TABLE_NAME = 'doctrine_migration_versions';
    const VERSION_COLUMN_NAME = 'version';
    const VERSION_COLUMN_LENGTH = 255;

    public function __construct(private readonly DependencyFactory $dependencyFactory)
    {
    }

    public static function fromEntityManager(EntityManager $em): self
    {
        $storage = new TableMetadataStorageConfiguration();
        $storage->setTableName(self::TABLE_NAME);
        $storage->setVersionColumnName(self::VERSION_COLUMN_NAME);
        $storage->setVersionColumnLength(self::VERSION_COLUMN_LENGTH);

        $configuration = new Configuration();
        $configuration->addMigrationsDirectory(__NAMESPACE__, __DIR__);
        $configuration->setMetadataStorageConfiguration($storage);
        $configuration->setAllOrNothing(false);

        $dependencyFactory = DependencyFactory::fromEntityManager(
            new ExistingConfiguration($configuration),
            new ExistingEntityManager($em)
        );
        $dependencyFactory->setDefinition(
            MigrationFactory::class,
            static fn (DependencyFactory $factory) => new class (new DbalMigrationFactory($factory->getConnection(), $factory->getLogger()), $em) implements MigrationFactory {
                public function __construct(private readonly MigrationFactory $factory, private readonly EntityManager $em)
                {
                }

                public function createVersion(string $migrationClassName): BaseMigration
                {
                    $migration = $this->factory->createVersion($migrationClassName);

                    if ($migration instanceof AbstractMigration) {
                        $migration->setEntityManager($this->em);
                    }

                    return $migration;
                }
            }
        );
        $dependencyFactory->setDefinition(
            MigrationsRepositoryInterface::class,
            static fn (DependencyFactory $factory) => new MigrationsRepository(
                self::MIGRATIONS,
                __DIR__,
                __NAMESPACE__,
                $factory->getMigrationFactory()
            )
        );

        return new self($dependencyFactory);
    }

    public function getDependencyFactory(): DependencyFactory
    {
        return $this->dependencyFactory;
    }

    /**
     * Returns the shared instance of the migration, which must not be executed:
     * an executed migration instance is frozen.
     */
    public function getMigration(string $version): BaseMigration
    {
        return $this->dependencyFactory->getMigrationRepository()->getMigration(new Version($version))->getMigration();
    }

    public function isMigrated(string $version): bool
    {
        $storage = $this->dependencyFactory->getMetadataStorage();
        $storage->ensureInitialized();

        return $storage->getExecutedMigrations()->hasMigration(new Version($version));
    }

    /**
     * Executes the migration in the given direction and updates the migrations table.
     *
     * @param string $direction Direction::UP or Direction::DOWN
     */
    public function execute(string $version, string $direction = Direction::UP): void
    {
        $this->dependencyFactory->getMetadataStorage()->ensureInitialized();

        $migration = $this->dependencyFactory->getMigrationFactory()->createVersion(get_class($this->getMigration($version)));

        $plan = new MigrationPlanList([
            new MigrationPlan(new Version($version), $migration, $direction),
        ], $direction);

        $this->dependencyFactory->getMigrator()->migrate($plan, new MigratorConfiguration());
    }

    /**
     * Records the migration as executed without executing it.
     */
    public function markMigrated(string $version): void
    {
        $storage = $this->dependencyFactory->getMetadataStorage();
        $storage->ensureInitialized();

        $storage->complete(new ExecutionResult(new Version($version), Direction::UP, new \DateTimeImmutable()));
    }
}
