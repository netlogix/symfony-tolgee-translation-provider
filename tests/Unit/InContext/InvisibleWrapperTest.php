<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit\InContext;

use Netlogix\SymfonyTolgeeTranslationProvider\InContext\InvisibleWrapper;
use PHPUnit\Framework\TestCase;

class InvisibleWrapperTest extends TestCase
{
    private const FIXTURE_FILE = __DIR__ . '/../../Fixtures/InContext/invisible-wrapper.json';

    public function testWrapMatchesTolgeeSdkOutput(): void
    {
        $wrapper = new InvisibleWrapper();

        foreach ($this->loadFixtureCases() as $case) {
            self::assertSame(
                $case['wrapped'],
                $wrapper->wrap($case['translation'], $case['key'], $case['ns']),
                sprintf('Marker for key "%s" differs from the Tolgee SDK output', $case['key'])
            );
        }
    }

    public function testRemoveMarkersRestoresTheTranslation(): void
    {
        $wrapper = new InvisibleWrapper();

        foreach ($this->loadFixtureCases() as $case) {
            self::assertSame($case['translation'], $wrapper->removeMarkers($case['wrapped']));
        }
    }

    public function testRemoveMarkersKeepsOtherZeroWidthCharacters(): void
    {
        $text = "Zero\u{200C}width non-joiner and Zero\u{200D}width joiner";

        self::assertSame($text, (new InvisibleWrapper())->removeMarkers($text));
    }

    /**
     * @return list<array{key: string, ns: ?string, translation: string, wrapped: string}>
     */
    private function loadFixtureCases(): array
    {
        $fixture = json_decode((string) file_get_contents(self::FIXTURE_FILE), true, 512, JSON_THROW_ON_ERROR);

        return $fixture['cases'];
    }
}
