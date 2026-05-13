<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="rounded-xl bg-gradient-to-r from-green-600 to-green-500 px-5 py-4 shadow-sm dark:from-green-700 dark:to-green-600">
            <div class="flex items-center gap-3">
                <span class="text-2xl">📱</span>
                <div>
                    <h1 class="text-base font-semibold text-white">WhatsApp Toplu Mesaj</h1>
                    <p class="text-xs text-green-100">Kişilerinize hızlı ve kolay mesaj gönderin</p>
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left Side: Form + Emoji --}}
            <div class="lg:col-span-2 space-y-6">
                {{ $this->form }}

                {{-- Emoji Picker --}}
                <div x-data="emojiPicker()" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    {{-- Picker Header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                        <button
                            type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <span class="text-base">😊</span>
                            <span>Emoji Ekle</span>
                            <svg :class="open ? 'rotate-180' : ''" class="h-3.5 w-3.5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ strlen($message) }} / 1000</span>
                    </div>

                    {{-- Picker Body --}}
                    <div x-show="open" x-collapse>
                        {{-- Category Bar --}}
                        <div class="flex overflow-x-auto border-b border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50" style="scrollbar-width: none;">
                            <template x-for="(cat, key) in categories" :key="key">
                                <button
                                    type="button"
                                    @click="category = key"
                                    :class="category === key
                                        ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400 bg-white dark:bg-gray-800'
                                        : 'text-gray-500 hover:text-gray-700 hover:bg-white dark:text-gray-400 dark:hover:bg-gray-800'"
                                    class="flex flex-shrink-0 flex-col items-center gap-1 px-4 py-2.5 transition"
                                    :title="cat.label"
                                >
                                    <span class="text-2xl leading-none" x-text="cat.icon"></span>
                                    <span class="text-[10px] font-medium leading-none" x-text="cat.label"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Emoji Grid --}}
                        <div class="max-h-64 overflow-y-auto p-3">
                            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(5rem, 1fr));">
                                <template x-for="emoji in categories[category].emojis" :key="emoji">
                                    <button
                                        type="button"
                                        @click="insertEmoji(emoji)"
                                        class="flex items-center justify-center rounded-xl transition hover:scale-110 hover:bg-green-50 active:scale-95 dark:hover:bg-green-900/30"
                                        style="height: 5rem; font-size: 2.8rem; line-height: 1;"
                                        x-text="emoji"
                                    ></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Side: Recipient Count --}}
            <div>
                <div class="rounded-lg border shadow-sm @if($recipient_count > 0) border-green-200 bg-green-50 dark:border-green-700 dark:bg-green-900/30 @else border-yellow-200 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/30 @endif">
                    <div class="p-6 text-center">
                        <div class="mb-1 text-5xl font-bold @if($recipient_count > 0) text-green-600 dark:text-green-400 @else text-yellow-600 dark:text-yellow-400 @endif">
                            {{ number_format($recipient_count) }}
                        </div>
                        <div class="text-sm font-medium @if($recipient_count > 0) text-green-800 dark:text-green-300 @else text-yellow-800 dark:text-yellow-300 @endif">
                            @if($recipient_count > 0)
                                kişiye gönderilecek
                            @else
                                Alıcı bulunamadı
                            @endif
                        </div>
                        @if($recipient_count > 0)
                            <div class="mt-1 text-xs text-green-700 dark:text-green-400">
                                benzersiz telefon bazında
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function emojiPicker() {
            return {
                open: false,
                category: 'smileys',
                categories: @json($this->getEmojiCategories()),
                insertEmoji(emoji) {
                    const textarea = document.querySelector(
                        'textarea[wire\\:model\\.live="data.message"],' +
                        'textarea[wire\\:model="data.message"],' +
                        'textarea[id*="message"],' +
                        'textarea[name*="message"]'
                    );

                    if (!textarea) return;

                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    const newValue = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);

                    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLTextAreaElement.prototype, 'value').set;
                    nativeInputValueSetter.call(textarea, newValue);
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));

                    this.$nextTick(() => {
                        textarea.focus();
                        const pos = start + emoji.length;
                        textarea.setSelectionRange(pos, pos);
                    });
                }
            }
        }
    </script>
</x-filament-panels::page>
