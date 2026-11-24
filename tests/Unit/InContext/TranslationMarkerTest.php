<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\Test\Unit\InContext;

use Netlogix\SymfonyTolgeeTranslationProvider\InContext\DomainNamespaceKeyMapper;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\InContextState;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\InvisibleWrapper;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\KeyMapper;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\TolgeeKey;
use Netlogix\SymfonyTolgeeTranslationProvider\InContext\TranslationMarker;
use PHPUnit\Framework\TestCase;

class TranslationMarkerTest extends TestCase
{
    private const KEY = 'header.language.danish';

    private const TRANSLATION = 'Dänisch';

    public function testKeepsTranslationUnmarkedWhenInactive(): void
    {
        self::assertSame(self::TRANSLATION, $this->createMarker(false)->mark(self::TRANSLATION, self::KEY));
    }

    public function testMarksTranslationWithTheMappedKeyWhenActive(): void
    {
        self::assertSame(
            (new InvisibleWrapper())->wrap(self::TRANSLATION, self::KEY, 'messages'),
            $this->createMarker(true)->mark(self::TRANSLATION, self::KEY)
        );
    }

    public function testUsesKeyAndNamespaceOfTheKeyMapper(): void
    {
        $keyMapper = $this->createStub(KeyMapper::class);
        $keyMapper->method('map')->willReturn(new TolgeeKey('button.save', 'my_ext.locallang'));

        $marker = new TranslationMarker($this->createState(true), $keyMapper, new InvisibleWrapper());

        self::assertSame(
            (new InvisibleWrapper())->wrap(self::TRANSLATION, 'button.save', 'my_ext.locallang'),
            $marker->mark(self::TRANSLATION, 'LLL:EXT:my_ext/Resources/Private/Language/locallang.xlf:button.save')
        );
    }

    public function testKeepsTranslationUnmarkedWhenKeyMapperSkipsIt(): void
    {
        self::assertSame(self::TRANSLATION, $this->createMarker(true)->mark(self::TRANSLATION, self::KEY, 'validators'));
    }

    public function testKeepsEmptyTranslationsEmpty(): void
    {
        self::assertSame('', $this->createMarker(true)->mark('', 'listing.beforeListPrice'));
    }

    private function createMarker(bool $active): TranslationMarker
    {
        return new TranslationMarker($this->createState($active), new DomainNamespaceKeyMapper(), new InvisibleWrapper());
    }

    private function createState(bool $active): InContextState
    {
        $state = $this->createStub(InContextState::class);
        $state->method('isActive')->willReturn($active);

        return $state;
    }
}
