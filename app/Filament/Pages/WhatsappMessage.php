<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Services\N8nService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsappMessage extends Page
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Mesajı';

    protected static string|\UnitEnum|null $navigationGroup = 'İletişim';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.whatsapp-message';

    public string $recipient_type = 'all';

    public array $categories = [];

    public array $contact_ids = [];

    public string $message = '';

    public array $image_urls = [];

    public int $recipient_count = 0;

    public function mount(): void
    {
        $this->form->fill([
            'recipient_type' => 'all',
            'categories'     => [],
            'contact_ids'    => [],
            'message'        => '',
            'image_urls'     => [],
        ]);

        $this->updateRecipientCount();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Alıcılar')
                    ->schema([
                        Radio::make('recipient_type')
                            ->label('Kime Gönderilecek?')
                            ->options([
                                'all'      => 'Tüm Kişiler',
                                'category' => 'Kategoriye Göre',
                                'manual'   => 'Manuel Seçim',
                            ])
                            ->default('all')
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount()),

                        CheckboxList::make('categories')
                            ->label('Kategoriler')
                            ->options([
                                'is_donor'         => 'Bağışçılar',
                                'is_aid_recipient' => 'Yardım Alanlar',
                                'is_student'       => 'Öğrenciler',
                            ])
                            ->columns(3)
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount())
                            ->visible(fn ($get): bool => $get('recipient_type') === 'category'),

                        Select::make('contact_ids')
                            ->label('Kişiler')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Contact::query()
                                ->whereNotNull('phone')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Contact $c): array => [
                                    $c->id => "{$c->first_name} {$c->last_name} ({$c->phone})",
                                ])
                                ->toArray()
                            )
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount())
                            ->visible(fn ($get): bool => $get('recipient_type') === 'manual'),
                    ]),

                Section::make('Mesaj')
                    ->schema([
                        Textarea::make('message')
                            ->label('Mesaj İçeriği')
                            ->placeholder("Merhaba {Ad} {Soyad}, ...")
                            ->helperText('Kullanılabilir değişkenler: {Ad} · {Soyad} · {AdSoyad} · {Telefon}')
                            ->rows(6)
                            ->required(),

                        FileUpload::make('image_urls')
                            ->label('Görseller (isteğe bağlı)')
                            ->multiple()
                            ->image()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('whatsapp-images')
                            ->visibility('public')
                            ->helperText('Mesajla birlikte gönderilecek görseller. Maks. 5 MB/görsel.'),
                    ]),
            ]);
    }

    public function updateRecipientCount(): void
    {
        $this->recipient_count = $this->buildQuery()->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Gönder')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mesaj Gönder')
                ->modalDescription(fn (): string => "{$this->recipient_count} kişiye WhatsApp mesajı gönderilecek. Onaylıyor musunuz?")
                ->modalSubmitActionLabel('Evet, Gönder')
                ->modalCancelActionLabel('İptal')
                ->action('sendMessages'),
        ];
    }

    public function sendMessages(): void
    {
        $data = $this->form->getState();

        $this->recipient_type = $data['recipient_type'];
        $this->categories     = $data['categories'] ?? [];
        $this->contact_ids    = $data['contact_ids'] ?? [];
        $this->message        = $data['message'] ?? '';
        $this->image_urls = array_values(array_map(
            fn (string $path): string => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
            $data['image_urls'] ?? []
        ));

        if (blank($this->message)) {
            Notification::make()->warning()->title('Mesaj boş olamaz.')->send();
            return;
        }

        $contacts = $this->buildQuery()
            ->whereNotNull('phone')
            ->get()
            ->unique('phone');

        if ($contacts->isEmpty()) {
            Notification::make()->warning()->title('Gönderilecek kişi bulunamadı.')->send();
            return;
        }

        $service = app(N8nService::class);
        $success = 0;
        $failed  = 0;

        foreach ($contacts as $contact) {
            $personalized = $this->personalizeMessage($this->message, $contact);

            try {
                $service->sendWhatsappTextMessage(
                    $contact->phone,
                    $personalized,
                    empty($this->image_urls) ? null : $this->image_urls
                );
                $success++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $body = "Başarılı: {$success}";
        if ($failed > 0) {
            $body .= " · Başarısız: {$failed}";
        }

        Notification::make()
            ->success()
            ->title('Mesaj gönderimi tamamlandı')
            ->body($body)
            ->send();
    }

    protected function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Contact::query()->whereNotNull('phone');

        match ($this->recipient_type) {
            'category' => $this->applyCategoryFilter($query),
            'manual'   => $query->whereIn('id', $this->contact_ids),
            default    => null,
        };

        return $query;
    }

    private function applyCategoryFilter(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (empty($this->categories)) {
            return;
        }

        $query->where(function ($q): void {
            foreach ($this->categories as $cat) {
                $q->orWhere($cat, true);
            }
        });
    }

    private function personalizeMessage(string $message, Contact $contact): string
    {
        return str_replace(
            ['{Ad}', '{Soyad}', '{AdSoyad}', '{Telefon}'],
            [$contact->first_name, $contact->last_name, "{$contact->first_name} {$contact->last_name}", $contact->phone ?? ''],
            $message
        );
    }
}
