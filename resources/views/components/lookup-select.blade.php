@props([
    'id',
    'name',
    'type',
    'value' => null,
    'scope' => null,
    'placeholder' => 'Escribe para buscar',
    'required' => false,
    'clearable' => false,
    'hint' => null,
])

@php
    $current = app(\App\Support\Lookups\LookupCatalog::class)->find($type, $value);
@endphp

<div
    class="relative"
    x-data="lookupSelect({ url: @js(route('lookups.search', $type)), scope: @js($scope), name: @js($name), id: @js($id), value: @js($current['id'] ?? null), label: @js($current['label'] ?? ''), detail: @js($current['detail'] ?? '') })"
    @keydown.escape="close()"
    @click.outside="close()"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="value || ''" value="{{ $current['id'] ?? '' }}">
    <input
        id="{{ $id }}"
        type="text"
        role="combobox"
        autocomplete="off"
        spellcheck="false"
        class="form-input {{ $clearable ? 'pr-20' : '' }}"
        placeholder="{{ $placeholder }}"
        aria-autocomplete="list"
        aria-controls="{{ $id }}-list"
        aria-describedby="{{ $id }}-status{{ $errors->has($name) ? ' '.$id.'-error' : '' }}"
        @if ($errors->has($name)) aria-invalid="true" @endif
        x-bind:aria-expanded="open.toString()"
        x-bind:aria-activedescendant="active >= 0 ? '{{ $id }}-option-' + active : null"
        x-bind:required="@js((bool) $required) && ! value"
        x-model="query"
        @input.debounce.250ms="onInput()"
        @focus="openList()"
        @blur="onBlur()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter="onEnter($event)"
        value="{{ $current['label'] ?? '' }}"
    >
    @if ($clearable)
        <button type="button" class="focus-ring absolute right-2 top-2 inline-flex min-h-11 items-center rounded px-2 text-xs font-bold text-blue-400 sm:min-h-6" x-show="value" x-cloak @click="clear()" aria-label="Quitar selección">Quitar</button>
    @endif
    <ul
        id="{{ $id }}-list"
        role="listbox"
        x-show="open"
        x-cloak
        class="absolute z-30 mt-1 max-h-72 w-full overflow-auto rounded-xl border border-slate-700 bg-slate-950 py-1 shadow-xl"
    >
        <template x-for="(item, index) in items" x-bind:key="item.id">
            <li
                role="option"
                x-bind:id="'{{ $id }}-option-' + index"
                x-bind:aria-selected="isSelected(item)"
                class="cursor-pointer px-3 py-2 text-sm text-slate-200"
                x-bind:class="index === active ? 'bg-blue-950/60' : ''"
                @mousedown.prevent="choose(item)"
                @mousemove="active = index"
            >
                <span class="block font-bold text-white" x-text="item.label"></span>
                <span class="block text-xs text-slate-400" x-text="item.detail" x-show="item.detail"></span>
            </li>
        </template>
        <li class="px-3 py-2 text-sm text-slate-400" x-show="status && items.length === 0" x-text="status"></li>
        <li class="border-t border-slate-800 px-3 py-2 text-xs text-slate-400" x-show="more">Se muestran los primeros resultados. Escribe más para afinar la búsqueda.</li>
    </ul>
    <p id="{{ $id }}-status" class="sr-only" role="status" aria-live="polite" x-text="status"></p>
    @if ($errors->has($name))
        <p id="{{ $id }}-error" class="mt-2 text-sm text-red-400" role="alert">{{ $errors->first($name) }}</p>
    @endif
    @if ($hint)<p class="mt-2 text-xs text-slate-400">{{ $hint }}</p>@endif
</div>
