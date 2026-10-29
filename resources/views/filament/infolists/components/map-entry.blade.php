@php
    use App\Filament\Forms\Components\MapPicker;
    use Filament\Support\Facades\FilamentAsset;
@endphp

<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div
        wire:ignore
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('map-picker') }}"
        x-load-css="[@js(FilamentAsset::getStyleHref('map-picker'))]"
        x-data="mapPicker({
            boundary: @js($getState()),
            latitude: @js($getLatitude()),
            longitude: @js($getLongitude()),
            center: @js(MapPicker::DEFAULT_CENTER),
            isReadOnly: true,
            labels: @js(MapPicker::labels()),
        })"
        {{ $getExtraAttributeBag() }}
    >
        <div x-ref="map" style="height: 20rem; border-radius: 0.5rem; isolation: isolate;"></div>

        <p x-show="areaText" x-text="areaText" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-500);"></p>
    </div>
</x-dynamic-component>
