<?php

use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase {
    public function testAdministrateurRedirectsToAdminDashboard(): void {
        $this->assertSame('../admin/dashboard.php', getDashboardPathForRole('administrateur'));
    }

    public function testGestionnaireRedirectsToGestionnaireDashboard(): void {
        $this->assertSame('../gestionnaire/dashboard.php', getDashboardPathForRole('gestionnaire'));
    }

    public function testOtherRolesRedirectToUserDashboard(): void {
        $this->assertSame('../user/dashboard.php', getDashboardPathForRole('reclamant'));
        $this->assertSame('../user/dashboard.php', getDashboardPathForRole(null));
    }

    public function testPasswordVerificationMatchesTheStoredHashPattern(): void {
        $plainPassword = 'Secret123!';
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

        $this->assertTrue(password_verify($plainPassword, $hash));
        $this->assertFalse(password_verify('WrongPassword!', $hash));
    }
}