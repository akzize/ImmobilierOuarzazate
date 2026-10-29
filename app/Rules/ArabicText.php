<?php

namespace App\Rules;

use Closure;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Text written mainly in Arabic: it has Arabic letters, and more of them than Latin letters.
 * A place or brand name in Latin script inside an Arabic sentence passes; an English or French text does not.
 * Digits, punctuation and formatting (HTML, or the rich editor's document while the form is open) are ignored.
 */
class ArabicText implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $text = is_array($value)
            ? RichContentRenderer::make($value)->toText()
            : html_entity_decode(strip_tags((string) $value));

        $arabicLetters = preg_match_all('/(?=\p{L})\p{Arabic}/u', $text);
        $latinLetters = preg_match_all('/\p{Latin}/u', $text);

        if ($arabicLetters === 0 || $arabicLetters <= $latinLetters) {
            $fail(__('The :attribute must be written in Arabic.'));
        }
    }
}
