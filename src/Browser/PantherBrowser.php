<?php

namespace Zenstruck\Browser;

use PHPUnit\Framework\Assert as PHPUnit;
use Symfony\Component\Panther\Client;
use Symfony\Component\VarDumper\VarDumper;
use Zenstruck\Browser;
use Zenstruck\Browser\Mink\PantherDriver;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental in 1.0
 */
class PantherBrowser extends Browser
{
    private Client $client;

    final public function __construct(Client $client)
    {
        parent::__construct(new PantherDriver($this->client = $client));
    }

    final public function client(): Client
    {
        return $this->client;
    }

    /**
     * @return static
     */
    final public function assertVisible(string $selector): self
    {
        return $this->wrapMinkExpectation(function() use ($selector) {
            $element = $this->webAssert()->elementExists('css', $selector);

            PHPUnit::assertTrue($element->isVisible());
        });
    }

    /**
     * @return static
     */
    final public function assertNotVisible(string $selector): self
    {
        $element = $this->documentElement()->find('css', $selector);

        if (!$element) {
            PHPUnit::assertTrue(true);

            return $this;
        }

        PHPUnit::assertFalse($element->isVisible());

        return $this;
    }

    /**
     * @return static
     */
    final public function wait(int $milliseconds): self
    {
        \usleep($milliseconds * 1000);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilVisible(string $selector): self
    {
        $this->client->waitForVisibility($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotVisible(string $selector): self
    {
        $this->client->waitForInvisibility($selector);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilSeeIn(string $selector, string $expected): self
    {
        $this->client->waitForElementToContain($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function waitUntilNotSeeIn(string $selector, string $expected): self
    {
        $this->client->waitForElementToNotContain($selector, $expected);

        return $this;
    }

    /**
     * @return static
     */
    final public function inspect(): self
    {
        if (!($_SERVER['PANTHER_NO_HEADLESS'] ?? false)) {
            throw new \RuntimeException('The "PANTHER_NO_HEADLESS" env variable must be set to inspect.');
        }

        \fwrite(STDIN, "\n\nInspecting the browser.\n\nPress enter to continue...");
        \fgets(STDIN);

        return $this;
    }

    /**
     * @return static
     */
    final public function takeScreenshot(string $filename): self
    {
        $this->client->takeScreenshot($filename);

        return $this;
    }

    final public function saveConsoleLog(string $filename): self
    {
        $log = $this->client->manage()->getLog('browser');
        $log = \json_encode($log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        \file_put_contents($filename, $log);

        return $this;
    }

    final public function dumpConsoleLog(): self
    {
        VarDumper::dump($this->client->manage()->getLog('browser'));

        return $this;
    }

    final public function ddConsoleLog(): void
    {
        $this->dumpConsoleLog();
        exit(1);
    }

    protected function rawResponse(): string
    {
        return "URL: {$this->minkSession()->getCurrentUrl()}\n\n{$this->documentElement()->getContent()}";
    }

    protected function normalizeDumpVariable(string $selector)
    {
        return $this->documentElement()->find('css', $selector)->getHtml();
    }
}
