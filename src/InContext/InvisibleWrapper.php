<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

/**
 * Server side counterpart of the InvisibleWrapper in @tolgee/web (observerType "invisible", fullKeyEncode: true).
 */
class InvisibleWrapper
{
    private const BIT_CHARACTERS = [
        '0' => "\u{200C}",
        '1' => "\u{200D}",
    ];

    private const MESSAGE_END = "\n";

    private const MARKER_PATTERN = '/(?:[\x{200C}\x{200D}]{9})+/u';

    public function wrap(string $translation, string $key, ?string $namespace = null): string
    {
        $value = [
            'k' => $key,
        ];
        if ($namespace !== null && $namespace !== '') {
            $value['n'] = $namespace;
        }

        $message = json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS
        );

        return $translation . $this->encode($message . self::MESSAGE_END);
    }

    public function removeMarkers(string $text): string
    {
        return preg_replace(self::MARKER_PATTERN, '', $text) ?? $text;
    }

    private function encode(string $message): string
    {
        $bits = '';
        foreach (unpack('C*', $message) ?: [] as $byte) {
            $bits .= sprintf('%08b', $byte) . '0';
        }

        return strtr($bits, self::BIT_CHARACTERS);
    }
}
