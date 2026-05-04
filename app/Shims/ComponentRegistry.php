<?php

namespace Livewire\Mechanisms;

/**
 * Shim for Livewire v4 (experimental) compatibility with packages
 * that expect Livewire v3's ComponentRegistry.
 */
class ComponentRegistry
{
    public function getClass($name)
    {
        return app('livewire.finder')->resolveClassComponentClassName($name);
    }
}
