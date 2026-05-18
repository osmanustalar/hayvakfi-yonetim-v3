<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Contact;
use App\Models\ContactCategory;
use App\Models\Region;
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

    public array $region_ids = [];

    public array $contact_ids = [];

    public string $message = '';

    public array $image_urls = [];

    public string $phone_region = '';

    public int $recipient_count = 0;

    public function getEmojiCategories(): array
    {
        return [
            'smileys'    => ['label' => 'Yüzler',    'icon' => '😀', 'emojis' => ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😚','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🤧','🥵','🥶','🥴','😵','🤯','🤠','🥳','🥸','😎','🤓','🧐','😕','😟','🙁','☹','😮','😯','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿','💀','💩','🤡','👹','👺','👻','👽','👾','🤖','😺','😸','😹','😻','😼','😽','🙀','😿','😾']],
            'people'     => ['label' => 'İnsanlar',  'icon' => '👋', 'emojis' => ['👋','🤚','🖐','✋','🖖','👌','🤌','🤏','✌','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍','💅','🤳','💪','🦾','🦵','🦶','👂','🦻','👃','👀','👁','👅','👄','🧠','🦷','🦴','👶','🧒','👦','👧','🧑','👱','👨','🧔','👩','🧓','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','💂','🥷','👷','🤴','👸','👳','👲','🧕','🤵','👰','🤰','🤱','👼','🎅','🤶','🦸','🦹','🧙','🧚','🧛','🧜','🧝','🧞','🧟','💆','💇','🚶','🧍','🧎','🏃','💃','🕺','👯','🧖','🏋','🚴','🏊','👫','👬','👭','💏','💑']],
            'animals'    => ['label' => 'Hayvanlar', 'icon' => '🐶', 'emojis' => ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🐛','🦋','🐌','🐞','🐜','🦟','🦗','🕷','🦂','🐢','🐍','🦎','🦖','🦕','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🦓','🦍','🦧','🦣','🐘','🦛','🦏','🐪','🐫','🦒','🦘','🦬','🐃','🐂','🐄','🐎','🐖','🐏','🐑','🦙','🐐','🦌','🐕','🐩','🦮','🐈','🐓','🦃','🦤','🦚','🦜','🦢','🦩','🕊','🐇','🦝','🦨','🦡','🦫','🦦','🦥','🐁','🐀','🐿','🦔']],
            'nature'     => ['label' => 'Doğa',      'icon' => '🌿', 'emojis' => ['🌵','🎄','🌲','🌳','🌴','🪵','🌱','🌿','🍀','🎍','🎋','🍃','🍂','🍁','🍄','🌾','💐','🌷','🌹','🥀','🌺','🌸','🌼','🌻','🌞','🌝','🌛','🌜','🌚','🌕','🌖','🌗','🌘','🌑','🌒','🌓','🌔','🌙','🌟','⭐','🌠','🌌','☀','🌤','⛅','☁','🌦','🌧','⛈','🌩','🌨','❄','☃','⛄','💨','💧','💦','☔','🌊','🌈','⚡','🔥','💥','🌀','🌪']],
            'food'       => ['label' => 'Yiyecek',   'icon' => '🍕', 'emojis' => ['🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶','🫑','🧄','🧅','🥔','🍠','🥐','🥯','🍞','🥖','🥨','🧀','🥚','🍳','🧈','🥞','🧇','🥓','🥩','🍗','🍖','🌭','🍔','🍟','🍕','🫓','🥪','🥙','🧆','🌮','🌯','🫔','🥗','🥘','🫕','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🦪','🍤','🍙','🍚','🍘','🍥','🥮','🍢','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🧃','🥤','🧋','☕','🍵','🫖','🍶','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🧉','🍾','🧊','🥄','🍴','🍽','🥢','🧂']],
            'travel'     => ['label' => 'Seyahat',   'icon' => '✈', 'emojis' => ['🚗','🚕','🚙','🚌','🚎','🏎','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🏍','🛵','🚲','🛴','🛹','🚧','⚓','⛵','🛶','🚤','🛥','🛳','🚢','✈','🛩','🛫','🛬','🪂','💺','🚁','🛰','🚀','🛸','🌍','🌎','🌏','🗺','🧭','🏔','⛰','🌋','🗻','🏕','🏖','🏜','🏝','🏞','🏟','🏛','🏗','🏘','🏠','🏡','🏢','🏣','🏤','🏥','🏦','🏨','🏩','🏪','🏫','🏬','🏭','🏯','🏰','💒','🗼','🗽','⛪','🕌','🛕','🕍','⛩','🕋','⛲','⛺','🌁','🌃','🏙','🌄','🌅','🌆','🌇','🌉','🎠','🎡','🎢','🎪']],
            'activities' => ['label' => 'Spor',      'icon' => '⚽', 'emojis' => ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🥏','🎱','🪀','🏓','🏸','🏒','🏑','🥍','🏏','🪃','🥅','⛳','🪁','🎣','🤿','🎽','🎿','🛷','🥌','🎯','🥊','🥋','🤺','🤼','🤸','🤾','🏌','🏇','🧘','🏋','🚴','🏊','🤽','🧗','🚵','🎖','🏆','🥇','🥈','🥉','🏅','🎗','🎫','🎟','🎪','🤹','🎭','🎨','🎰','🎲','🎮','🕹']],
            'objects'    => ['label' => 'Nesneler',  'icon' => '📱', 'emojis' => ['📱','💻','🖥','🖨','⌨','🖱','💾','💿','📀','📷','📸','📹','🎥','📞','☎','📟','📠','🔋','🔌','💡','🔦','🕯','📝','✏','🖊','🖋','📌','📍','📎','🔗','📐','📏','📚','📖','📰','🗞','📓','📔','📒','📕','📗','📘','📙','🔖','🏷','💰','💴','💵','💶','💷','💸','💳','🧾','📧','📨','📩','📤','📥','📦','📫','📪','📬','📭','📮','📋','📁','📂','🗒','🗃','🗄','🗑','🔒','🔓','🔑','🗝','🔨','🪓','🛠','⚔','🛡','🪚','🔧','🪛','🔩','⚙','🧲','🪜','🧰','💉','💊','🩹','🩺','🩻','🚪','🪑','🚿','🛁','🧴','🪥','🧹','🧺','🧻','🧼','🪞','🛋','🛏','🛒','🎁','🎈','🎀','🎊','🎉','🧧','🕯','🧨','✨','🎆','🎇','🎃']],
            'symbols'    => ['label' => 'Semboller', 'icon' => '❤', 'emojis' => ['❤','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣','💕','💞','💓','💗','💖','💘','💝','💟','☮','✝','☪','🕉','☸','✡','🔯','🕎','☯','🛐','⚛','🆗','🆙','🆒','🆕','🆓','🆔','🅰','🅱','🆎','🅾','🆘','❌','⭕','🛑','⛔','📛','🚫','💯','💢','♨','🔞','📵','🔇','🔈','🔉','🔊','📣','📢','🔔','🔕','⚠','♻','✅','❎','✔','❓','❔','❕','❗','‼','⁉','🔱','⚜','🔰','♾','©','®','™','🔀','🔁','🔂','▶','⏩','⏭','⏯','◀','⏪','⏮','🔼','⏫','🔽','⏬','⏸','⏹','⏺','🔃','➕','➖','➗','✖','⬆','↗','➡','↘','⬇','↙','⬅','↖','↕','↔','↩','↪','⤴','⤵','🔄']],
            'flags'      => ['label' => 'Bayraklar', 'icon' => '🏁', 'emojis' => ['🏁','🚩','🎌','🏴','🏳','🏴‍☠️','🇦🇩','🇦🇪','🇦🇫','🇦🇱','🇦🇲','🇦🇴','🇦🇷','🇦🇹','🇦🇺','🇦🇿','🇧🇦','🇧🇩','🇧🇪','🇧🇬','🇧🇭','🇧🇯','🇧🇳','🇧🇴','🇧🇷','🇧🇸','🇧🇹','🇧🇼','🇧🇾','🇧🇿','🇨🇦','🇨🇩','🇨🇫','🇨🇬','🇨🇭','🇨🇮','🇨🇱','🇨🇲','🇨🇳','🇨🇴','🇨🇷','🇨🇺','🇨🇻','🇨🇾','🇨🇿','🇩🇪','🇩🇰','🇩🇲','🇩🇴','🇩🇿','🇪🇨','🇪🇪','🇪🇬','🇪🇷','🇪🇸','🇪🇹','🇪🇺','🇫🇮','🇫🇯','🇫🇷','🇬🇦','🇬🇧','🇬🇩','🇬🇪','🇬🇭','🇬🇷','🇬🇹','🇬🇾','🇭🇰','🇭🇳','🇭🇷','🇭🇹','🇭🇺','🇮🇩','🇮🇪','🇮🇱','🇮🇳','🇮🇶','🇮🇷','🇮🇸','🇮🇹','🇯🇲','🇯🇴','🇯🇵','🇰🇪','🇰🇬','🇰🇭','🇰🇲','🇰🇵','🇰🇷','🇰🇼','🇰🇾','🇰🇿','🇱🇦','🇱🇧','🇱🇮','🇱🇰','🇱🇷','🇱🇸','🇱🇹','🇱🇺','🇱🇻','🇱🇾','🇲🇦','🇲🇩','🇲🇪','🇲🇬','🇲🇰','🇲🇱','🇲🇲','🇲🇳','🇲🇴','🇲🇷','🇲🇹','🇲🇺','🇲🇻','🇲🇼','🇲🇽','🇲🇾','🇲🇿','🇳🇦','🇳🇪','🇳🇬','🇳🇮','🇳🇱','🇳🇴','🇳🇵','🇳🇿','🇴🇲','🇵🇦','🇵🇪','🇵🇬','🇵🇭','🇵🇰','🇵🇱','🇵🇷','🇵🇸','🇵🇹','🇵🇾','🇶🇦','🇷🇴','🇷🇸','🇷🇺','🇷🇼','🇸🇦','🇸🇩','🇸🇪','🇸🇬','🇸🇮','🇸🇰','🇸🇱','🇸🇳','🇸🇴','🇸🇷','🇸🇸','🇸🇹','🇸🇻','🇸🇾','🇸🇿','🇹🇩','🇹🇬','🇹🇭','🇹🇱','🇹🇲','🇹🇳','🇹🇴','🇹🇷','🇹🇹','🇹🇻','🇹🇼','🇹🇿','🇺🇦','🇺🇬','🇺🇳','🇺🇸','🇺🇾','🇺🇿','🇻🇦','🇻🇨','🇻🇪','🇻🇳','🇻🇺','🇼🇸','🇾🇪','🇿🇦','🇿🇲','🇿🇼']],
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'recipient_type' => 'all',
            'categories'     => [],
            'region_ids'     => [],
            'contact_ids'    => [],
            'phone_region'   => '',
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
                    ->description('Sadece WhatsApp bildirimi aktif olan kişilere gönderim yapılır.')
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

                        Select::make('region_ids')
                            ->label('Bölge Filtresi')
                            ->multiple()
                            ->searchable()
                            ->placeholder('Tüm bölgeler')
                            ->options(fn (): array => Region::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (Region $r): array => [$r->id => $r->name])
                                ->toArray()
                            )
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount())
                            ->helperText('Boş bırakılırsa tüm bölgelere gönderilir.'),

                        Select::make('phone_region')
                            ->label('Numara Bölgesi')
                            ->placeholder('Tümü (filtre yok)')
                            ->options([
                                'domestic'      => 'Yurtiçi (+90 / 05 ile başlayanlar)',
                                'international' => 'Yurtdışı (+90 / 05 ile başlamayanlar)',
                            ])
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount())
                            ->helperText('Seçilmezse tüm numaralara gönderilir.'),

                        CheckboxList::make('categories')
                            ->label('Kategoriler')
                            ->options(fn (): array => ContactCategory::query()
                                ->where('is_active', true)
                                ->whereHas('contacts', fn ($q) => $q->where('whatsapp_enabled', true)->whereNotNull('phone'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->columns(3)
                            ->live()
                            ->afterStateUpdated(fn () => $this->updateRecipientCount())
                            ->visible(fn ($get): bool => $get('recipient_type') === 'category'),

                        Select::make('contact_ids')
                            ->label('Kişiler')
                            ->multiple()
                            ->searchable()
                            ->options(fn (): array => Contact::query()
                                ->where('whatsapp_enabled', true)
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
                            ->placeholder("Merhaba...")
                            ->rows(6)
                            ->maxLength(1000)
                            ->live()
                            ->afterStateUpdated(fn (string $state) => $this->message = $state)
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
                ->action(fn () => $this->sendMessages()),
        ];
    }

    public function sendMessages(): void
    {
        $data = $this->form->getState();

        $this->recipient_type = $data['recipient_type'];
        $this->categories     = $data['categories'] ?? [];
        $this->region_ids     = $data['region_ids'] ?? [];
        $this->contact_ids    = $data['contact_ids'] ?? [];
        $this->phone_region   = $data['phone_region'] ?? '';
        $this->message        = $data['message'] ?? '';

        $imagePaths = $data['image_urls'] ?? [];
        $this->image_urls = array_values(array_map(
            function (string $path): string {
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            },
            $imagePaths
        ));

        if (blank($this->message)) {
            Notification::make()->warning()->title('Mesaj boş olamaz.')->send();
            $this->restoreForm($imagePaths);
            return;
        }

        $contacts = $this->buildQuery()
            ->whereNotNull('phone')
            ->get()
            ->unique('phone');

        if ($contacts->isEmpty()) {
            Notification::make()->warning()->title('Gönderilecek kişi bulunamadı.')->send();
            $this->restoreForm($imagePaths);
            return;
        }

        $phones = $contacts->pluck('phone')->values()->all();
        $service = app(N8nService::class);

        try {
            $service->sendWhatsappBulkMessage(
                $phones,
                $this->message,
                empty($this->image_urls) ? null : $this->image_urls
            );

            Notification::make()
                ->success()
                ->title('Mesaj gönderimi tamamlandı')
                ->body(count($phones) . ' kişiye gönderim tamamlandı')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Mesaj gönderilemedi')
                ->body('Bir hata oluştu: ' . $e->getMessage())
                ->send();
        }

        $this->restoreForm($imagePaths);
    }

    private function restoreForm(array $imagePaths): void
    {
        $this->form->fill([
            'recipient_type' => $this->recipient_type,
            'categories'     => $this->categories,
            'region_ids'     => $this->region_ids,
            'contact_ids'    => $this->contact_ids,
            'phone_region'   => $this->phone_region,
            'message'        => $this->message,
            'image_urls'     => $imagePaths,
        ]);
    }

    protected function buildQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Contact::query()
            ->where('whatsapp_enabled', true)
            ->whereNotNull('phone');

        if (!empty($this->region_ids)) {
            $query->whereIn('region_id', $this->region_ids);
        }

        if ($this->phone_region === 'domestic') {
            $query->where(function ($q): void {
                $q->where('phone', 'like', '+90%')
                  ->orWhere('phone', 'like', '05%');
            });
        } elseif ($this->phone_region === 'international') {
            $query->where(function ($q): void {
                $q->where('phone', 'not like', '+90%')
                  ->where('phone', 'not like', '05%');
            });
        }

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

        $query->whereHas('categories', function ($q): void {
            $q->whereIn('contact_categories.id', $this->categories);
        });
    }
}
