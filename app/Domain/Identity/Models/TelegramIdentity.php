<?php

namespace App\Domain\Identity\Models;

use App\Models\User;
use Database\Factories\TelegramIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $telegram_id
 * @property string|null $username
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $language_code
 * @property Carbon $linked_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'user_id',
    'telegram_id',
    'username',
    'first_name',
    'last_name',
    'language_code',
    'linked_at',
])]
class TelegramIdentity extends Model
{
    /** @use HasFactory<TelegramIdentityFactory> */
    use HasFactory;

    protected static function newFactory(): TelegramIdentityFactory
    {
        return TelegramIdentityFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'linked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
