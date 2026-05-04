<?php

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use PHPUnit\Framework\TestCase;

final class FrontendTest extends TestCase {
    public function testLoginPageIsPresentInTheApplicationSource(): void {
        $page = file_get_contents(__DIR__ . '/../connexion/connexion.php');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('id="loginForm"', $page);
    }

    public function testSeleniumSmokeCheckWhenDriverIsAvailable(): void {
        $seleniumUrl = getenv('SELENIUM_URL') ?: '';
        $appUrl = getenv('APP_BASE_URL') ?: '';

        if ($seleniumUrl === '' || $appUrl === '') {
            $this->markTestSkipped('Selenium non configure dans cet environnement.');
        }

        $driver = RemoteWebDriver::create($seleniumUrl, DesiredCapabilities::chrome());

        try {
            $driver->get($appUrl . '/connexion/connexion.php');
            $this->assertNotEmpty($driver->getTitle());
        } finally {
            $driver->quit();
        }
    }
}