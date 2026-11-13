{{--
    Styled dropdown that replaces the native <select>, whose open list is drawn
    by the operating system and cannot be themed. The chosen value travels in a
    hidden input, so forms submit exactly as before.

    Props:
      name      form field name
      id        id for the trigger, so <label for="..."> focuses it
      options   [value => label]
      selected  the current value
      submit    submit the enclosing form as soon as a value is chosen
      size      'md' (forms) or 'sm' (filter bars)
      disabled  render read-only
--}}
@props([
    'name',
    'id' => null,
    'options' => [],
    'selected' => '',
    'submit' => false,
    'size' => 'md',
    'disabled' => false,
])

@php
    $id ??= $name;
    $selected = (string) $selected;
    $values = array_map('strval', array_keys($options));
    $labels = array_values($options);
    $current = array_search($selected, $values, true);
    $current = $current === false ? 0 : $current;
    $trigger = $size === 'sm' ? 'py-2 text-xs' : 'py-2.5 text-sm';
    $item = $size === 'sm' ? 'py-1.5 text-xs' : 'py-2 text-sm';
@endphp

<div x-data="{
        open: false,
        index: {{ $current }},
        active: {{ $current }},
        values: @js($values),
        labels: @js($labels),
        submit: @js((bool) $submit),
        toggle() { this.open ? this.close() : this.show() },
        show() {
            this.active = this.index;
            this.open = true;
            this.$nextTick(() => this.scrollActive());
        },
        close(refocus = false) {
            this.open = false;
            if (refocus) this.$refs.button.focus();
        },
        choose(i) {
            const changed = i !== this.index;
            this.index = i;
            this.close(true);
            if (changed && this.submit) {
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            }
        },
        move(step) {
            if (! this.open) return this.show();
            this.active = (this.active + step + this.values.length) % this.values.length;
            this.scrollActive();
        },
        scrollActive() {
            this.$refs.list?.children[this.active]?.scrollIntoView({ block: 'nearest' });
        },
    }"
    @click.outside="close()"
    @keydown.escape.prevent.stop="close(true)"
    {{ $attributes->merge(['class' => 'relative']) }}>

    <input type="hidden" name="{{ $name }}" :value="values[index]" value="{{ $values[$current] ?? '' }}">

    <button type="button" id="{{ $id }}" x-ref="button"
        @click="toggle()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.home.prevent="active = 0; scrollActive()"
        @keydown.end.prevent="active = values.length - 1; scrollActive()"
        @keydown.enter.prevent="open ? choose(active) : show()"
        @keydown.space.prevent="open ? choose(active) : show()"
        @keydown.tab="close()"
        aria-haspopup="listbox"
        :aria-expanded="open.toString()"
        aria-controls="{{ $id }}-listbox"
        :aria-activedescendant="open ? '{{ $id }}-opt-' + active : null"
        @disabled($disabled)
        class="group flex w-full items-center justify-between gap-2 rounded-xl border bg-ink-900/60 px-3.5 text-left text-slate-100 shadow-inner transition
               focus:outline-none focus-visible:border-brand-500 focus-visible:ring-2 focus-visible:ring-brand-500/30
               disabled:cursor-not-allowed disabled:opacity-60 {{ $trigger }}"
        :class="open ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-white/10 hover:border-white/20'">
        <span class="truncate" x-text="labels[index]">{{ $labels[$current] ?? '' }}</span>
        <svg class="h-4 w-4 shrink-0 text-slate-500 transition duration-200 group-hover:text-slate-300"
            :class="open && 'rotate-180 text-brand-300'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd"
                d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                clip-rule="evenodd" />
        </svg>
    </button>

    <ul x-ref="list" id="{{ $id }}-listbox" role="listbox" tabindex="-1" x-cloak x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-[.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute left-0 z-50 mt-1.5 max-h-64 w-full min-w-max overflow-auto rounded-xl border border-white/10
               bg-ink-800 p-1 shadow-2xl shadow-black/50 ring-1 ring-black/20 focus:outline-none">
        @foreach ($labels as $i => $label)
            <li id="{{ $id }}-opt-{{ $i }}" role="option"
                :aria-selected="(index === {{ $i }}).toString()"
                @click="choose({{ $i }})"
                @mouseenter="active = {{ $i }}"
                class="flex cursor-pointer select-none items-center justify-between gap-3 rounded-lg px-3 {{ $item }}"
                :class="{
                    'bg-brand-500/15 text-white': active === {{ $i }},
                    'text-slate-300': active !== {{ $i }},
                    'font-semibold text-brand-200': index === {{ $i }},
                }">
                <span class="truncate">{{ $label }}</span>
                <svg x-show="index === {{ $i }}" class="h-4 w-4 shrink-0 text-brand-400" viewBox="0 0 20 20"
                    fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 111.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z"
                        clip-rule="evenodd" />
                </svg>
            </li>
        @endforeach
    </ul>
</div>
