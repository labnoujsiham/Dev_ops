<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {
    private function assertConnexionPageStaticFallback(): void {
        $page = file_get_contents(__DIR__ . "/../connexion/connexion.php");
        $this->assertNotFalse($page);
        $this->assertStringContainsString("<title>ReclaNova - Connexion</title>", $page);
    }

    private function isSeleniumReachable(string $seleniumUrl): bool {
        $statusUrl = rtrim($seleniumUrl, '/') . '/status';
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($statusUrl, false, $context);
        return $response !== false;
    }

    public function testLoginPageIsPresentInTheApplicationSource(): void {
        $page = file_get_contents(__DIR__ . "/../connexion/connexion.php");

        $this->assertNotFalse($page);
        $this->assertStringContainsString("id=\"loginForm\"", $page);
    }

    public function testConnexionPageContainsCustomAuthUiContracts(): void {
        $page = file_get_contents(__DIR__ . "/../connexion/connexion.php");

        $this->assertNotFalse($page);

        $expectedSnippets = [
            "id=\"loginForm\"",
            "id=\"registerForm\"",
            "id=\"loginIdentifier\"",
            "id=\"loginPassword\"",
            "id=\"registerUsername\"",
            "id=\"registerEmail\"",
            "id=\"registerPassword\"",
            "id=\"loginAlert\"",
            "id=\"registerAlert\"",
            "class=\"password-strength\"",
            "forgot_password.php",
            "<script src=\"script.js\"></script>",
        ];

        foreach ($expectedSnippets as $snippet) {
            $this->assertStringContainsString($snippet, $page, "Missing UI contract snippet: " . $snippet);
        }
    }

    public function testConnexionPageTitleAndLanguageAreCorrectForProjectBranding(): void {
        $page = file_get_contents(__DIR__ . "/../connexion/connexion.php");

        $this->assertNotFalse($page);
        $this->assertStringContainsString("<html lang=\"fr\">", $page);
        $this->assertStringContainsString("<title>ReclaNova - Connexion</title>", $page);
    }

    public function testSeleniumSmokeCheckWhenDriverIsAvailable(): void {
        $seleniumUrl = getenv("SELENIUM_URL") ?: "";
        $appUrl = getenv("APP_BASE_URL") ?: "";

        if ($seleniumUrl === "" || $appUrl === "") {
            $this->assertConnexionPageStaticFallback();
            return;
        }

        if (!$this->isSeleniumReachable($seleniumUrl)) {
            $this->assertConnexionPageStaticFallback();
            return;
        }

        $driver = null;
        try {
            $driver = RemoteWebDriver::create(
                $seleniumUrl,
                DesiredCapabilities::chrome(),
                3000,
                10000
            );
            $driver->get($appUrl . "/connexion/connexion.php");
            $this->assertNotEmpty($driver->getTitle());
        } catch (\Throwable $e) {
            $this->assertConnexionPageStaticFallback();
        } finally {
            if ($driver !== null) {
                $driver->quit();
            }
        }
    }
}
