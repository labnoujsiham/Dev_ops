<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase {
    #[DataProvider('dashboardPathProvider')]
    public function testRoleRedirectMappingContracts(?string $role, string $expectedPath): void {
        $this->assertSame($expectedPath, getDashboardPathForRole($role));

        $absolutePath = realpath(__DIR__ . '/..') . '/' . ltrim(str_replace('../', '', $expectedPath), '/');
        $this->assertFileExists($absolutePath, 'Redirect target file must exist for role: ' . (string) $role);
    }

    public function testPasswordVerificationMatchesTheStoredHashPattern(): void {
        $plainPassword = 'Secret123!';
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($plainPassword, $hash));
        $this->assertFalse(password_verify('WrongPassword!', $hash));
    }

    public static function dashboardPathProvider(): array {
        return [
            'administrateur role' => ['administrateur', '../admin/dashboard.php'],
            'gestionnaire role' => ['gestionnaire', '../gestionnaire/dashboard.php'],
            'reclamant role defaults to user dashboard' => ['reclamant', '../user/dashboard.php'],
            'null role defaults to user dashboard' => [null, '../user/dashboard.php'],
            'unexpected role defaults to user dashboard' => ['role_inconnu', '../user/dashboard.php'],
        ];
    }
}