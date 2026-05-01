<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class Login extends \Filament\Pages\Auth\Login
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label(__('filament-panels::pages/auth/login.form.email.label'))
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->default('admin@paroquia.local'),
                TextInput::make('password')
                    ->label(__('filament-panels::pages/auth/login.form.password.label'))
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->required()
                    ->default('admin123'),
                \Filament\Forms\Components\Checkbox::make('remember')
                    ->label(__('filament-panels::pages/auth/login.form.remember.label')),
            ])
            ->statePath('data');
    }
}
