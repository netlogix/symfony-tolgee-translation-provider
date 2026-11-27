<?php

declare(strict_types = 1);

namespace Netlogix\SymfonyTolgeeTranslationProvider\InContext;

class TranslationMarker
{
    private InContextState $state;

    private KeyMapper $keyMapper;

    private InvisibleWrapper $invisibleWrapper;

    public function __construct(InContextState $state, KeyMapper $keyMapper, InvisibleWrapper $invisibleWrapper)
    {
        $this->state = $state;
        $this->keyMapper = $keyMapper;
        $this->invisibleWrapper = $invisibleWrapper;
    }

    public function mark(string $translation, string $id, ?string $domain = null): string
    {
        // Templates use empty translations as switches, e.g. "{% if 'notice'|trans is not empty %}".
        if ($translation === '' || !$this->state->isActive()) {
            return $translation;
        }

        $key = $this->keyMapper->map($id, $domain);
        if ($key === null) {
            return $translation;
        }

        return $this->invisibleWrapper->wrap($translation, $key->getKey(), $key->getNamespace());
    }
}
