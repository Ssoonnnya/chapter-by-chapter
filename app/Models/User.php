<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Identity\Enums\Locale;
use App\Domain\Identity\Models\TelegramIdentity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Locale $locale
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read TelegramIdentity|null $telegramIdentity
 * @property-read Collection<int, LibraryItem> $libraryItems
 * @property-read Collection<int, ReadingSession> $readingSessions
 */
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'locale' => Locale::class,
        ];
    }

    /**
     * @return HasOne<TelegramIdentity, $this>
     */
    public function telegramIdentity(): HasOne
    {
        return $this->hasOne(TelegramIdentity::class);
    }

    /**
     * @return HasMany<LibraryItem, $this>
     */
    public function libraryItems(): HasMany
    {
        return $this->hasMany(LibraryItem::class);
    }

    /**
     * @return HasMany<ReadingSession, $this>
     */
    public function readingSessions(): HasMany
    {
        return $this->hasMany(ReadingSession::class);
    }
}
