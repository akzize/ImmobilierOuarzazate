@php
    use App\Filament\Forms\Components\MapPicker;
    use Filament\Support\Facades\FilamentAsset;

    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    {{-- wire:ignore keeps Livewire from morphing the DOM that Leaflet manages. --}}
    <div
        wire:ignore
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('map-picker') }}"
        x-load-css="[@js(FilamentAsset::getStyleHref('map-picker'))]"
        x-data="mapPicker({
            boundary: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            latitude: $wire.$entangle(@js($getLatitudeStatePath())),
            longitude: $wire.$entangle(@js($getLongitudeStatePath())),
            center: @js(MapPicker::DEFAULT_CENTER),
            isReadOnly: @js($isDisabled()),
            labels: @js(MapPicker::labels()),
        })"
        {{ $getExtraAttributeBag() }}
    >
        {{-- Inline styles: the panel ships precompiled CSS. `isolation` keeps Leaflet's z-indexes below dropdowns and modals. --}}
        <div x-ref="map" style="height: 26rem; border-radius: 0.5rem; isolation: isolate;"></div>

        <p x-show="areaText" x-text="areaText" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--gray-500);"></p>
    </div>
</x-dynamic-component>
