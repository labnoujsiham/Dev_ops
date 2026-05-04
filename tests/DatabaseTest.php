<?php

use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase {
    public function testConnexionBaseDeDonnees(): void {
        $connection = getDBConnection();

        if ($connection === null) {
            $this->markTestSkipped('Base de donnees indisponible dans cet environnement.');
        }

        $this->assertInstanceOf(PDO::class, $connection);
        $this->assertEquals(1, $connection->query('SELECT 1')->fetchColumn());
    }
}