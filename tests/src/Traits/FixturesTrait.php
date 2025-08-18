<?php

declare(strict_types=1);

namespace Openeuropa\Tests\GptAtEcPhpClient\Traits;

trait FixturesTrait
{

    protected function getFixture(string $name): array {
        $filename = __DIR__ . '/../../fixtures/' . $name . '.json';
        if (!file_exists($filename)) {
            throw new \Exception('Fixture ' . $name . ' not found');
        }

        return json_decode(file_get_contents($filename), true);
    }

}