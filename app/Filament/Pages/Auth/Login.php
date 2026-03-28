<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\AuthService;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('phone')
                    ->label('Telefon Numarası')
                    ->placeholder('05XX XXX XX XX')
                    ->tel()
                    ->required()
                    ->autocomplete('tel')
                    ->live(onBlur: true),

                $this->getPasswordFormComponent(),

                Select::make('company_id')
                    ->label('Şirket')
                    ->options(function (Get $get): array {
                        $phone = $get('phone') ?? '';

                        if (empty($phone)) {
                            return [];
                        }

                        $user = app(\App\Repositories\UserRepository::class)->findByPhone($phone);

                        if (! $user) {
                            return [];
                        }

                        return $user->companies()
                            ->where('is_active', true)
                            ->pluck('companies.name', 'companies.id')
                            ->toArray();
                    })
                    ->required()
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $authService = app(AuthService::class);

        $success = $authService->login(
            phone: $data['phone'],
            password: $data['password'],
            companyId: (int) $data['company_id'],
        );

        if (! $success) {
            Notification::make()
                ->title('Giriş başarısız')
                ->body('Telefon numarası, şifre veya şirket bilgisi hatalı.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'data.phone' => __('filament-panels::auth/pages/login.messages.failed'),
            ]);
        }

        return app(LoginResponse::class);
    }
}
