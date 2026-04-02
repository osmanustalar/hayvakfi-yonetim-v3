<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute kabul edilmelidir.',
    'accepted_if' => ':other :value olduğunda :attribute kabul edilmelidir.',
    'active_url' => ':attribute geçerli bir URL olmalıdır.',
    'after' => ':attribute, :date tarihinden sonra bir tarih olmalıdır.',
    'after_or_equal' => ':attribute, :date tarihine eşit veya sonrasında bir tarih olmalıdır.',
    'alpha' => ':attribute yalnızca harf içermelidir.',
    'alpha_dash' => ':attribute yalnızca harf, rakam, tire ve alt çizgi içermelidir.',
    'alpha_num' => ':attribute yalnızca harf ve rakam içermelidir.',
    'array' => ':attribute bir dizi olmalıdır.',
    'ascii' => ':attribute yalnızca tek baytlık alfanümerik karakterler ve semboller içermelidir.',
    'before' => ':attribute, :date tarihinden önce bir tarih olmalıdır.',
    'before_or_equal' => ':attribute, :date tarihine eşit veya öncesinde bir tarih olmalıdır.',
    'between' => [
        'array' => ':attribute :min ile :max arasında öğe içermelidir.',
        'file' => ':attribute :min ile :max kilobayt arasında olmalıdır.',
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string' => ':attribute :min ile :max karakter arasında olmalıdır.',
    ],
    'boolean' => ':attribute alanı doğru veya yanlış olmalıdır.',
    'can' => ':attribute alanı yetkisiz bir değer içeriyor.',
    'confirmed' => ':attribute onayı eşleşmiyor.',
    'contains' => ':attribute zorunlu bir değer içermiyor.',
    'current_password' => 'Mevcut şifre hatalı.',
    'date' => ':attribute geçerli bir tarih olmalıdır.',
    'date_equals' => ':attribute, :date tarihine eşit olmalıdır.',
    'date_format' => ':attribute, :format biçimiyle eşleşmelidir.',
    'decimal' => ':attribute :decimal ondalık basamağa sahip olmalıdır.',
    'declined' => ':attribute reddedilmelidir.',
    'declined_if' => ':other :value olduğunda :attribute reddedilmelidir.',
    'different' => ':attribute ile :other birbirinden farklı olmalıdır.',
    'digits' => ':attribute :digits basamaktan oluşmalıdır.',
    'digits_between' => ':attribute :min ile :max basamak arasında olmalıdır.',
    'dimensions' => ':attribute görsel boyutları geçersiz.',
    'distinct' => ':attribute alanının tekrarlayan bir değeri var.',
    'doesnt_end_with' => ':attribute şu değerlerden biriyle bitmemelidir: :values.',
    'doesnt_start_with' => ':attribute şu değerlerden biriyle başlamamalıdır: :values.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'ends_with' => ':attribute şu değerlerden biriyle bitmelidir: :values.',
    'enum' => 'Seçilen :attribute geçersiz.',
    'exists' => 'Seçilen :attribute geçersiz.',
    'extensions' => ':attribute şu uzantılardan birine sahip olmalıdır: :values.',
    'file' => ':attribute bir dosya olmalıdır.',
    'filled' => ':attribute alanı bir değere sahip olmalıdır.',
    'gt' => [
        'array' => ':attribute, :value öğeden fazla içermelidir.',
        'file' => ':attribute, :value kilobayttan büyük olmalıdır.',
        'numeric' => ':attribute, :value değerinden büyük olmalıdır.',
        'string' => ':attribute, :value karakterden fazla olmalıdır.',
    ],
    'gte' => [
        'array' => ':attribute, :value veya daha fazla öğe içermelidir.',
        'file' => ':attribute, :value kilobayt veya daha büyük olmalıdır.',
        'numeric' => ':attribute, :value değerine eşit veya daha büyük olmalıdır.',
        'string' => ':attribute, :value karakter veya daha uzun olmalıdır.',
    ],
    'hex_color' => ':attribute geçerli bir onaltılık renk olmalıdır.',
    'image' => ':attribute bir görsel olmalıdır.',
    'in' => 'Seçilen :attribute geçersiz.',
    'in_array' => ':attribute alanı :other içinde mevcut olmalıdır.',
    'integer' => ':attribute tam sayı olmalıdır.',
    'ip' => ':attribute geçerli bir IP adresi olmalıdır.',
    'ipv4' => ':attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6' => ':attribute geçerli bir IPv6 adresi olmalıdır.',
    'json' => ':attribute geçerli bir JSON metni olmalıdır.',
    'list' => ':attribute liste olmalıdır.',
    'lowercase' => ':attribute küçük harf olmalıdır.',
    'lt' => [
        'array' => ':attribute, :value öğeden az içermelidir.',
        'file' => ':attribute, :value kilobayttan küçük olmalıdır.',
        'numeric' => ':attribute, :value değerinden küçük olmalıdır.',
        'string' => ':attribute, :value karakterden az olmalıdır.',
    ],
    'lte' => [
        'array' => ':attribute, :value öğeden fazla içermemelidir.',
        'file' => ':attribute, :value kilobayt veya daha küçük olmalıdır.',
        'numeric' => ':attribute, :value değerine eşit veya daha küçük olmalıdır.',
        'string' => ':attribute, :value karakter veya daha kısa olmalıdır.',
    ],
    'mac_address' => ':attribute geçerli bir MAC adresi olmalıdır.',
    'max' => [
        'array' => ':attribute, :max öğeden fazla içermemelidir.',
        'file' => ':attribute, :max kilobayttan büyük olmamalıdır.',
        'numeric' => ':attribute, :max değerinden büyük olmamalıdır.',
        'string' => ':attribute, :max karakterden uzun olmamalıdır.',
    ],
    'max_digits' => ':attribute, :max basamaktan fazla olmamalıdır.',
    'mimes' => ':attribute şu dosya türlerinden biri olmalıdır: :values.',
    'mimetypes' => ':attribute şu dosya türlerinden biri olmalıdır: :values.',
    'min' => [
        'array' => ':attribute en az :min öğe içermelidir.',
        'file' => ':attribute en az :min kilobayt olmalıdır.',
        'numeric' => ':attribute en az :min olmalıdır.',
        'string' => ':attribute en az :min karakter olmalıdır.',
    ],
    'min_digits' => ':attribute en az :min basamak içermelidir.',
    'missing' => ':attribute alanı eksik olmalıdır.',
    'missing_if' => ':other :value olduğunda :attribute alanı eksik olmalıdır.',
    'missing_unless' => ':other :value olmadığında :attribute alanı eksik olmalıdır.',
    'missing_with' => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'missing_with_all' => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'multiple_of' => ':attribute, :value değerinin katı olmalıdır.',
    'not_in' => 'Seçilen :attribute geçersiz.',
    'not_regex' => ':attribute biçimi geçersiz.',
    'numeric' => ':attribute sayı olmalıdır.',
    'password' => [
        'letters' => ':attribute en az bir harf içermelidir.',
        'mixed' => ':attribute en az bir büyük ve bir küçük harf içermelidir.',
        'numbers' => ':attribute en az bir rakam içermelidir.',
        'symbols' => ':attribute en az bir özel karakter içermelidir.',
        'uncompromised' => 'Girilen :attribute bir veri ihlalinde yer almaktadır. Lütfen farklı bir :attribute seçin.',
    ],
    'present' => ':attribute alanı mevcut olmalıdır.',
    'present_if' => ':other :value olduğunda :attribute alanı mevcut olmalıdır.',
    'present_unless' => ':other :value olmadığında :attribute alanı mevcut olmalıdır.',
    'present_with' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'present_with_all' => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'prohibited' => ':attribute alanı yasaktır.',
    'prohibited_if' => ':other :value olduğunda :attribute alanı yasaktır.',
    'prohibited_unless' => ':other, :values içinde değilse :attribute alanı yasaktır.',
    'prohibits' => ':attribute alanı :other alanının mevcut olmasını engellemektedir.',
    'regex' => ':attribute biçimi geçersiz.',
    'required' => ':attribute alanı zorunludur.',
    'required_array_keys' => ':attribute alanı şu girişleri içermelidir: :values.',
    'required_if' => ':other, :value olduğunda :attribute alanı zorunludur.',
    'required_if_accepted' => ':other kabul edildiğinde :attribute alanı zorunludur.',
    'required_if_declined' => ':other reddedildiğinde :attribute alanı zorunludur.',
    'required_unless' => ':other, :values içinde değilse :attribute alanı zorunludur.',
    'required_with' => ':values mevcut olduğunda :attribute alanı zorunludur.',
    'required_with_all' => ':values mevcut olduğunda :attribute alanı zorunludur.',
    'required_without' => ':values mevcut olmadığında :attribute alanı zorunludur.',
    'required_without_all' => 'Hiçbiri mevcut olmadığında :attribute alanı zorunludur.',
    'same' => ':attribute ile :other eşleşmelidir.',
    'size' => [
        'array' => ':attribute :size öğe içermelidir.',
        'file' => ':attribute :size kilobayt olmalıdır.',
        'numeric' => ':attribute :size olmalıdır.',
        'string' => ':attribute :size karakter olmalıdır.',
    ],
    'starts_with' => ':attribute şu değerlerden biriyle başlamalıdır: :values.',
    'string' => ':attribute metin olmalıdır.',
    'timezone' => ':attribute geçerli bir saat dilimi olmalıdır.',
    'unique' => ':attribute zaten kullanılıyor.',
    'uploaded' => ':attribute yüklenirken hata oluştu.',
    'uppercase' => ':attribute büyük harf olmalıdır.',
    'url' => ':attribute geçerli bir URL olmalıdır.',
    'ulid' => ':attribute geçerli bir ULID olmalıdır.',
    'uuid' => ':attribute geçerli bir UUID olmalıdır.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        // Kullanıcı
        'name' => 'Ad Soyad',
        'phone' => 'Telefon',
        'password' => 'Şifre',
        'password_confirmation' => 'Şifre Onayı',
        'can_login' => 'Giriş İzni',
        'is_active' => 'Aktif',
        'default_company_id' => 'Varsayılan Şirket',

        // Şirket
        'company_id' => 'Şirket',
        'tax_number' => 'Vergi Numarası',
        'address' => 'Adres',
        'city' => 'Şehir',

        // Kişi
        'first_name' => 'Ad',
        'last_name' => 'Soyad',
        'national_id' => 'TC Kimlik No',
        'birth_date' => 'Doğum Tarihi',
        'notes' => 'Notlar',
        'is_donor' => 'Bağışçı',
        'is_aid_recipient' => 'Yardım Alan',
        'is_student' => 'Öğrenci',

        // Para Birimi
        'symbol' => 'Sembol',
        'currency_id' => 'Para Birimi',

        // Kasa
        'safe_id' => 'Kasa',
        'safe_group_id' => 'Kasa Grubu',
        'balance' => 'Bakiye',
        'iban' => 'IBAN',
        'is_manual_transaction' => 'Manuel İşlem',
        'sort_order' => 'Sıralama',
        'integration_id' => 'Entegrasyon ID',

        // İşlem
        'type' => 'İşlem Tipi',
        'operation_type' => 'Operasyon Tipi',
        'total_amount' => 'Toplam Tutar',
        'amount' => 'Tutar',
        'exchange_rate' => 'Döviz Kuru',
        'item_rate' => 'Birim Fiyat',
        'contact_id' => 'Kişi',
        'description' => 'Açıklama',
        'process_date' => 'İşlem Tarihi',
        'transaction_date' => 'Banka Tarih',
        'target_safe_id' => 'Hedef Kasa',

        // Kategori
        'transaction_category_id' => 'Kategori',
        'parent_id' => 'Üst Kategori',
        'color' => 'Renk',
        'contact_type' => 'Kişi Tipi',

        // Eğitim
        'class_id' => 'Sınıf',
        'enrollment_date' => 'Kayıt Tarihi',
        'monthly_fee' => 'Aylık Ücret',
        'default_monthly_fee' => 'Varsayılan Aylık Ücret',
        'teacher_id' => 'Öğretmen',
        'start_date' => 'Başlangıç Tarihi',
        'end_date' => 'Bitiş Tarihi',
        'capacity' => 'Kapasite',
        'period' => 'Dönem',
        'due_date' => 'Vade Tarihi',
        'paid_at' => 'Ödeme Tarihi',
        'status' => 'Durum',

        // Yardım
        'aid_type' => 'Yardım Türü',
        'given_at' => 'Verilme Tarihi',
        'transaction_id' => 'Kasa İşlemi',
    ],

];
