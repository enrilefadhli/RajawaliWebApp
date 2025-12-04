<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Forms;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->statePath('data')
                ->schema([
                    Forms\Components\TextInput::make('username')
                        ->label('Username')
                        ->required()
                        ->autofocus(),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->required()
                        ->autocomplete('current-password'),
                    Forms\Components\Checkbox::make('remember')
                        ->label(__('filament-panels::pages/auth/login.form.remember.label')),
                ]),
        ];
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }
}
