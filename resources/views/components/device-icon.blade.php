@props(['type', 'class' => 'w-6 h-6'])

@php
    // $type is an integer from the device_types table:
    // 1 = Desktop, 2 = Mobile, 3 = Tablet
    $typeValue = $type;
    if (is_object($type) && property_exists($type, 'id')) {
        $typeValue = $type->id;
    }

    $iconClass = match((int) $typeValue) {
        2 => 'fa-solid fa-mobile-screen',
        3 => 'fa-solid fa-tablet-screen-button',
        default => 'fa-solid fa-desktop',
    };
@endphp

<i class="{{ $iconClass }} {{ $class }}" aria-hidden="true"></i>
