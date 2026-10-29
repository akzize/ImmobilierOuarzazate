<?php

namespace App\Models;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\Language;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['property_id', 'full_name', 'phone', 'email', 'message', 'lang', 'source', 'preferred_visit_at', 'status', 'admin_notes'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory;

    /**
     * Added to local Moroccan numbers (06…, 07…) to build WhatsApp links.
     */
    public const string COUNTRY_CODE = '212';

    /**
     * Mirrors the database defaults so unsaved inquiries already have a status.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'lang' => 'ar',
        'source' => 'form',
        'status' => 'new',
    ];

    /**
     * Stamp the first reply and the closing date from the status, whichever screen changes it.
     */
    protected static function booted(): void
    {
        static::saving(function (Inquiry $inquiry): void {
            if ($inquiry->status !== InquiryStatus::New) {
                $inquiry->contacted_at ??= now();
            }

            $inquiry->closed_at = $inquiry->status->isClosed() ? ($inquiry->closed_at ?? now()) : null;
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
            'lang' => Language::class,
            'source' => InquirySource::class,
            'status' => InquiryStatus::class,
            'preferred_visit_at' => 'datetime',
            'contacted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * wa.me link for the phone number, or null when it has too few digits to be a full number.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', $this->phone);

        $internationalNumber = match (true) {
            str_starts_with($digits, '00') => substr($digits, 2),
            str_starts_with($digits, '0') => self::COUNTRY_CODE.substr($digits, 1),
            default => $digits,
        };

        return strlen($internationalNumber) >= 10 ? 'https://wa.me/'.$internationalNumber : null;
    }
}
