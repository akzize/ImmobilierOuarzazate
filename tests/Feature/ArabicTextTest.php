<?php

use App\Rules\ArabicText;

function passesArabicText(string $value): bool
{
    $passes = true;

    (new ArabicText)->validate('title_ar', $value, function () use (&$passes): void {
        $passes = false;
    });

    return $passes;
}

test('Arabic text passes', function (string $value) {
    expect(passesArabicText($value))->toBeTrue();
})->with([
    'title' => ['فيلا للبيع في ورزازات'],
    'with digits and units' => ['شقة 120 م² - الطابق 3'],
    'with a Latin place name' => ['شقة فاخرة بإقامة Al Wahda'],
    'rich editor HTML' => ['<p>منزل <strong>جميل</strong> &amp; مشمس</p>'],
]);

test('text that is not mainly Arabic fails', function (string $value) {
    expect(passesArabicText($value))->toBeFalse();
})->with([
    'English' => ['Villa for sale in Ouarzazate'],
    'French' => ['Villa à vendre à Ouarzazate'],
    'mostly English with one Arabic word' => ['Beautiful villa فيلا'],
    'digits only' => ['12345'],
    'HTML without Arabic' => ['<p>Nice house</p>'],
]);
