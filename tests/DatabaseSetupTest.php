<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseSetupTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
    }

    public function testDatabaseSchemaCreation(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Erstelle Schema
        $schemaTool->createSchema($metadata);

        // Prüfe, dass Tabellen erstellt wurden
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains('user', $tables);
        $this->assertContains('founds_image', $tables);
    }

    public function testDatabaseSchemaUpdate(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Update Schema
        $schemaTool->updateSchema($metadata);

        // Prüfe, dass Schema aktuell ist
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains('user', $tables);
        $this->assertContains('founds_image', $tables);
    }

    public function testDatabaseSchemaDrop(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Drop Schema
        $schemaTool->dropSchema($metadata);

        // Prüfe, dass Tabellen gelöscht wurden
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertNotContains('user', $tables);
        $this->assertNotContains('founds_image', $tables);
    }

    public function testDatabaseConnectionParameters(): void
    {
        $connection = $this->entityManager->getConnection();
        $params = $connection->getParams();

        $this->assertEquals('sqlite', $params['driver']);
        $this->assertTrue($params['memory'] ?? false);
    }

    public function testEntityManagerConfiguration(): void
    {
        $this->assertNotNull($this->entityManager);
        $this->assertTrue($this->entityManager->isOpen());
    }

    public function testRepositoryRegistration(): void
    {
        $container = self::getContainer();
        
        $this->assertTrue($container->has('App\Repository\UserRepository'));
        $this->assertTrue($container->has('App\Repository\FoundsImageRepository'));
        
        $userRepository = $container->get('App\Repository\UserRepository');
        $foundsImageRepository = $container->get('App\Repository\FoundsImageRepository');
        
        $this->assertNotNull($userRepository);
        $this->assertNotNull($foundsImageRepository);
    }

    public function testDatabaseTransactionSupport(): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Test, dass Transaktionen unterstützt werden
        $this->assertTrue($connection->beginTransaction());
        $this->assertTrue($connection->rollBack());
    }

    public function testDatabaseQueryExecution(): void
    {
        $connection = $this->entityManager->getConnection();
        
        // Test einfache Query
        $stmt = $connection->executeQuery('SELECT 1 as test');
        $result = $stmt->fetchAssociative();
        
        $this->assertEquals(1, $result['test']);
    }

    public function testDatabaseSchemaValidation(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Erstelle Schema
        $schemaTool->createSchema($metadata);

        // Validiere Schema
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        
        // Prüfe User-Tabelle
        $userTable = $schemaManager->listTableDetails('user');
        $this->assertNotNull($userTable);
        $this->assertTrue($userTable->hasColumn('id'));
        $this->assertTrue($userTable->hasColumn('email'));
        $this->assertTrue($userTable->hasColumn('password'));
        $this->assertTrue($userTable->hasColumn('roles'));
        $this->assertTrue($userTable->hasColumn('is_active'));
        $this->assertTrue($userTable->hasColumn('uuid'));

        // Prüfe FoundsImage-Tabelle
        $foundsImageTable = $schemaManager->listTableDetails('founds_image');
        $this->assertNotNull($foundsImageTable);
        $this->assertTrue($foundsImageTable->hasColumn('id'));
        $this->assertTrue($foundsImageTable->hasColumn('name'));
        $this->assertTrue($foundsImageTable->hasColumn('file_path'));
        $this->assertTrue($foundsImageTable->hasColumn('user_uuid'));
        $this->assertTrue($foundsImageTable->hasColumn('username'));
        $this->assertTrue($foundsImageTable->hasColumn('created_at'));
        $this->assertTrue($foundsImageTable->hasColumn('latitude'));
        $this->assertTrue($foundsImageTable->hasColumn('longitude'));
        $this->assertTrue($foundsImageTable->hasColumn('is_public'));
    }

    public function testDatabaseIndexes(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Erstelle Schema
        $schemaTool->createSchema($metadata);

        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        
        // Prüfe User-Tabelle Indexes
        $userTable = $schemaManager->listTableDetails('user');
        $userIndexes = $userTable->getIndexes();
        
        // Prüfe, dass wichtige Indexes vorhanden sind
        $indexNames = array_keys($userIndexes);
        $this->assertContains('UNIQ_8D93D649E7927C74', $indexNames); // email unique index
        
        // Prüfe FoundsImage-Tabelle Indexes
        $foundsImageTable = $schemaManager->listTableDetails('founds_image');
        $foundsImageIndexes = $foundsImageTable->getIndexes();
        
        $indexNames = array_keys($foundsImageIndexes);
        $this->assertContains('IDX_FOUNDS_IMAGE_USER_UUID', $indexNames); // user_uuid index
    }

    public function testDatabaseConstraints(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Erstelle Schema
        $schemaTool->createSchema($metadata);

        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->createSchemaManager();
        
        // Prüfe User-Tabelle Constraints
        $userTable = $schemaManager->listTableDetails('user');
        $userConstraints = $userTable->getForeignKeys();
        
        // Prüfe FoundsImage-Tabelle Constraints
        $foundsImageTable = $schemaManager->listTableDetails('founds_image');
        $foundsImageConstraints = $foundsImageTable->getForeignKeys();
        
        // Prüfe, dass Foreign Key Constraints vorhanden sind
        $this->assertNotEmpty($foundsImageConstraints);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Cleanup Schema
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
    }
} 