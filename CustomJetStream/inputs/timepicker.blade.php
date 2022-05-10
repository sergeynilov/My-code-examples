@props(['options' => []])

@php
    $options = array_merge([
                    'enableTime' => true,
                    'noCalendar' => true,
                    'dateFormat' => 'H:i',
                    'time_24hr' => true
                    ], $options);
@endphp

<div wire:ignore class="p-2 w-full d1`">
    <input
        x-data
        x-init="flatpickr($refs.input, {{json_encode((object)$options)}});"
        x-ref="input"
        type="text"
        class="editor_form_input "
        {{ $attributes->merge(['class' => 'form-input w-full rounded-md shadow-sm']) }}
    />
</div>
