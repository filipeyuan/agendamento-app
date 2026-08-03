<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BusinessPlan;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $name
 * @property string $slug
 * @property BusinessPlan $plan
 * @property string|null $stripe_customer_id
 * @property string|null $stripe_subscription_id
 * @property int $assistant_daily_count
 * @property Carbon|null $assistant_daily_date
 * @property Carbon|null $premium_prompt_seen_at
 * @property string|null $logo_url
 * @property string|null $logo_public_id
 * @property string|null $banner_url
 * @property string|null $banner_public_id
 * @property string|null $accent_color
 */
#[Fillable(['name', 'slug'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    public const FREE_ASSISTANT_DAILY_LIMIT = 3;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPro(): bool
    {
        return $this->plan === BusinessPlan::Pro;
    }

    public function isPremiumPromptDue(): bool
    {
        return ! $this->isPro() && (! $this->premium_prompt_seen_at || $this->premium_prompt_seen_at->lt(now()->subDay()));
    }

    public function hasReachedAssistantDailyLimit(): bool
    {
        if ($this->isPro()) {
            return false;
        }

        if (! $this->assistant_daily_date?->isToday()) {
            $this->forceFill(['assistant_daily_count' => 0, 'assistant_daily_date' => now()->toDateString()])->save();
        }

        return $this->assistant_daily_count >= self::FREE_ASSISTANT_DAILY_LIMIT;
    }

    public function registerAssistantMessage(): void
    {
        $this->increment('assistant_daily_count');
    }

    public function assistantMessagesRemainingToday(): ?int
    {
        if ($this->isPro()) {
            return null;
        }

        $usedToday = $this->assistant_daily_date?->isToday() ? $this->assistant_daily_count : 0;

        return max(0, self::FREE_ASSISTANT_DAILY_LIMIT - $usedToday);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function admins(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<BusinessHour, $this>
     */
    public function businessHours(): HasMany
    {
        return $this->hasMany(BusinessHour::class);
    }

    /**
     * @return HasMany<ScheduleBlock, $this>
     */
    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class);
    }

    /**
     * @return HasOne<GoogleCalendarConnection, $this>
     */
    public function googleCalendarConnection(): HasOne
    {
        return $this->hasOne(GoogleCalendarConnection::class);
    }

    /**
     * @return HasMany<BusinessInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(BusinessInvite::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => BusinessPlan::class,
            'premium_prompt_seen_at' => 'datetime',
            'assistant_daily_date' => 'date',
        ];
    }
}
