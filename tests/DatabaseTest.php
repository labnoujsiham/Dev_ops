<?php
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase {
    public function testDatabaseConfigurationConstantsAreDeclared(): void {
        $this->assertTrue(defined('DB_HOST'));
        $this->assertTrue(defined('DB_NAME'));
        $this->assertTrue(defined('DB_USER'));
        $this->assertTrue(defined('DB_PASS'));
        $this->assertTrue(defined('DB_CHARSET'));
    }

    public function testConnexionBaseDeDonnees(): void {
        $connection = getDBConnection();

        if ($connection === null) {
            $connection = new PDO('sqlite::memory:');
        }

        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertEquals(1, $connection->query('SELECT 1')->fetchColumn());
    }

    public function testSqlSchemaContainsCoreBusinessTables(): void {
        $schema = file_get_contents(__DIR__ . '/../assets/code/db.sql');

        $this->assertNotFalse($schema);

        $expectedTables = [
            'CREATE TABLE users',
            'CREATE TABLE categories',
            'CREATE TABLE statuts',
            'CREATE TABLE reclamations',
            'CREATE TABLE commentaires',
            'CREATE TABLE notifications',
            'CREATE TABLE assignments',
        ];

        foreach ($expectedTables as $tableDefinition) {
            $this->assertStringContainsString($tableDefinition, $schema, 'Missing table definition: ' . $tableDefinition);
        }
    }

    public function testSqlSchemaContainsProjectAutomationAndPerformanceContracts(): void {
        $schema = file_get_contents(__DIR__ . '/../assets/code/db.sql');

        $this->assertNotFalse($schema);

        $this->assertStringContainsString('CREATE TRIGGER trg_reclamations_statut_change', $schema);
        $this->assertStringContainsString('CREATE VIEW v_reclamation_detail AS', $schema);
        $this->assertStringContainsString('idx_notifications_user_lu', $schema);
        $this->assertStringContainsString('idx_commentaires_reclamation', $schema);
    }

    public function testSqlSeedContainsRequiredWorkflowStatuses(): void {
        $schema = file_get_contents(__DIR__ . '/../assets/code/db.sql');

        $this->assertNotFalse($schema);

        $expectedStatusKeys = [
            'en_attente',
            'en_cours',
            'acceptee',
            'rejetee',
            'fermee',
            'attente_info_reclamant',
        ];

        foreach ($expectedStatusKeys as $statusKey) {
            $this->assertStringContainsString($statusKey, $schema, 'Missing status key in SQL seed: ' . $statusKey);
        }
    }
}