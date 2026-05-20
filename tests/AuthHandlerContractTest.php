<?php

use PHPUnit\Framework\TestCase;

final class AuthHandlerContractTest extends TestCase {
    public function testAuthHandlerSupportsExpectedActions(): void {
        $source = file_get_contents(__DIR__ . '/../connexion/auth_handler.php');

        $this->assertNotFalse($source);
        $this->assertStringContainsString('if ($action === \'login\')', $source);
        $this->assertStringContainsString('elseif ($action === \'register\')', $source);
        $this->assertStringContainsString('\'Action invalide\'', $source);
    }

    public function testLoginFlowContainsSecurityAndSessionContracts(): void {
        $source = file_get_contents(__DIR__ . '/../connexion/auth_handler.php');

        $this->assertNotFalse($source);

        $this->assertStringContainsString('password_verify($password, $user[\'mot_de_passe\'])', $source);
        $this->assertStringContainsString('SELECT id, nom, email, role, mot_de_passe', $source);
        $this->assertStringContainsString('WHERE email = ? OR nom = ?', $source);
        $this->assertStringContainsString('$_SESSION[\'user_id\']', $source);
        $this->assertStringContainsString('$_SESSION[\'user_role\']', $source);
        $this->assertStringContainsString('getDashboardPathForRole($user[\'role\'])', $source);
    }

    public function testRegisterFlowContainsValidationAndDefaultRoleContracts(): void {
        $source = file_get_contents(__DIR__ . '/../connexion/auth_handler.php');

        $this->assertNotFalse($source);

        $this->assertStringContainsString("'Veuillez remplir tous les champs'", $source);
        $this->assertStringContainsString("'Le nom doit contenir au moins 3 caractères'", $source);
        $this->assertStringContainsString("'Email invalide'", $source);
        $this->assertStringContainsString("'Le mot de passe doit contenir au moins 6 caractères'", $source);
        $this->assertStringContainsString('password_hash($password, PASSWORD_DEFAULT)', $source);
        $this->assertStringContainsString("VALUES (?, ?, ?, 'reclamant', NOW())", $source);
    }

    public function testAuthHandlerRespondsWithJson(): void {
        $source = file_get_contents(__DIR__ . '/../connexion/auth_handler.php');

        $this->assertNotFalse($source);
        $this->assertStringContainsString("header('Content-Type: application/json');", $source);
        $this->assertStringContainsString('json_encode([', $source);
    }
}
