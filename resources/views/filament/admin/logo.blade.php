{{-- Inline layout styles: the panel ships precompiled CSS, so arbitrary Tailwind classes would not be generated. --}}
<div style="display: flex; align-items: center; gap: 0.5rem;">
    <img src="{{ asset('images/3a9ar-ouarzazate-logo.png') }}" alt="" style="height: 1.75rem; width: auto;">
    <span>{{ config('app.name') }}</span>
</div>
