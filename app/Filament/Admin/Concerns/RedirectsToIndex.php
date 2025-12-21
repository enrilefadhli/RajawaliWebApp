<?php

namespace App\Filament\Admin\Concerns;

trait RedirectsToIndex
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getRedirectAfterSave(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}
