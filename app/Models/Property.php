<?php

namespace App\Models;

use App\Enums\LegalTitle;
use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Observers\PropertyObserver;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A listing. `reference`, `slug` and `published_at` are filled by PropertyObserver.
 * `owner_id`, `address` and `internal_notes` are private and must never reach the public site.
 */
#[Fillable([
    'property_type_id', 'city_id', 'transaction', 'status', 'featured',
    'title_ar', 'title_en', 'description_ar', 'description_en',
    'price', 'price_negotiable', 'surface_m2', 'built_area_m2', 'bedrooms', 'bathrooms',
    'latitude', 'longitude', 'boundary', 'legal_title', 'video_url', 'details',
    'owner_id', 'address', 'internal_notes',
])]
#[Hidden(['owner_id', 'address', 'internal_notes'])]
#[ObservedBy([PropertyObserver::class])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    public const string REFERENCE_PREFIX = 'OZZ';

    /**
     * Listings on the market longer than this are flagged as stale.
     */
    public const int STALE_AFTER_DAYS = 90;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'transaction' => TransactionType::class,
            'status' => PropertyStatus::class,
            'legal_title' => LegalTitle::class,
            'featured' => 'boolean',
            'price_negotiable' => 'boolean',
            'price' => 'decimal:2',
            'surface_m2' => 'decimal:2',
            'built_area_m2' => 'decimal:2',
            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'boundary' => 'array',
            'details' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Mark the first photo as cover when none is marked.
     */
    public function ensureCoverImage(): void
    {
        if ($this->images()->where('is_cover', true)->exists()) {
            return;
        }

        $this->images()->first()?->update(['is_cover' => true]);
    }

    /**
     * Append photos after the existing ones, in the given order.
     *
     * @param  array<string>  $paths
     */
    public function addImages(array $paths): void
    {
        $lastPosition = (int) $this->images()->reorder()->max('position');

        foreach (array_values($paths) as $offset => $path) {
            $this->images()->create([
                'path' => $path,
                'position' => $lastPosition + $offset + 1,
            ]);
        }
    }

    /**
     * Whole days since first publication, while the listing is still on the market.
     */
    public function daysOnMarket(): ?int
    {
        if ($this->published_at === null || ! $this->status->isOnMarket()) {
            return null;
        }

        return (int) $this->published_at->diffInDays(now());
    }

    /**
     * A listing needs at least one photo before it can go live.
     */
    public function canBePublished(): bool
    {
        return $this->images()->exists();
    }

    /**
     * The listing's page on the public website; null while it is not published or the website URL is not configured.
     */
    public function publicUrl(): ?string
    {
        $frontendUrl = config('app.frontend_url');

        if (blank($frontendUrl) || $this->status !== PropertyStatus::Published) {
            return null;
        }

        return rtrim($frontendUrl, '/').'/marketplace/'.$this->slug;
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', PropertyStatus::Published);
    }

    /**
     * @return BelongsTo<PropertyType, $this>
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Owner, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * @return HasMany<PropertyImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('position');
    }

    /**
     * @return HasOne<PropertyImage, $this>
     */
    public function coverImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class)->where('is_cover', true);
    }

    /**
     * @return BelongsToMany<Amenity, $this>
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'property_amenities');
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }
}
