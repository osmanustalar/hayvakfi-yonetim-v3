<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TransactionType;
use App\Models\SafeTransaction;

class SafeTransactionObserver
{
    /**
     * Sil edilecek işlemi işlemeden önce bakiye düzeltmelerini ve ilişkili işlemleri sil.
     */
    public function deleting(SafeTransaction $record): void
    {
        // API'den geri verilen işlemler silinemez
        if ($record->integration_id !== null) {
            abort(403, 'API\'den geri verilen işlemler silinemez.');
        }

        // İlişkili transfer/exchange kaydını da sil ve bakiyeleri düzelt
        $type = $record->type instanceof TransactionType
            ? $record->type->value
            : (string) $record->type;

        $safe = $record->safe;

        if ($safe !== null) {
            // Bakiyeyi ters çevir
            if ($type === 'income') {
                $safe->decrement('balance', (float) $record->total_amount);
            } else {
                $safe->increment('balance', (float) $record->total_amount);
            }
        }

        // Kalem kayıtlarını sil
        $record->items()->delete();

        // Transfer/exchange çiftini sil
        if ($record->targetTransaction !== null) {
            $targetSafe = $record->targetSafe;

            if ($targetSafe !== null) {
                $targetType = $record->targetTransaction->type instanceof TransactionType
                    ? $record->targetTransaction->type->value
                    : (string) $record->targetTransaction->type;

                if ($targetType === 'income') {
                    $targetSafe->decrement('balance', (float) $record->targetTransaction->total_amount);
                } else {
                    $targetSafe->increment('balance', (float) $record->targetTransaction->total_amount);
                }
            }

            $record->targetTransaction->items()->delete();
            $record->targetTransaction->delete();
        }
    }
}
