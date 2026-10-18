<?php

namespace App\Models;

use Database\Factories\PropertyImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['property_id', 'path', 'position', 'is_cover', 'alt_text'])]
class PropertyImage extends Model
{
    /** @use HasFactory<PropertyImageFactory> */
    use HasFactory;

    public const string DISK = 'public';

    public const string DIRECTORY = 'properties';

    /**
     * Keep a single cover image per property and remove files that are no longer referenced.
     */
    protected static function booted(): void
    {
        static::saved(function (PropertyImage $image): void {
            if ($image->is_cover && ($image->wasRecentlyCreated || $image->wasChanged('is_cover'))) {
                static::query()
                    ->where('property_id', $image->property_id)
                    ->whereKeyNot($image->getKey())
                    ->update(['is_cover' => false]);
            }

            if ($image->wasChanged('path') && filled($image->getOriginal('path'))) {
                Storage::disk(self::DISK)->delete($image->getOriginal('path'));
            }
        });

        static::deleted(function (PropertyImage $image): void {
            Storage::disk(self::DISK)->delete($image->path);
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_cover' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
