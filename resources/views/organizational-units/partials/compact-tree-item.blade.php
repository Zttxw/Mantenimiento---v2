@props(['node', 'depth' => 1])

@php
    $hasChildren = count($node['children'] ?? []) > 0;
    $paddingLeft = ($depth - 1) * 14 + 6;

    // Pip color por nivel jerárquico
    $pipColor = match($depth) {
        1 => 'bg-amber-500 ring-2 ring-amber-300/40',
        2 => 'bg-indigo-600',
        3 => 'bg-blue-600',
        4 => 'bg-teal-600',
        default => 'bg-zinc-400',
    };
@endphp

<div class="tree-item-wrapper"
     data-node-id="{{ $node['id'] }}"
     data-node-name="{{ mb_strtolower($node['name'] ?? '') }}"
     data-node-code="{{ mb_strtolower($node['code'] ?? '') }}"
     data-node-titular="{{ mb_strtolower($node['responsible']['name'] ?? '') }}">

    {{-- Fila del Item del Árbol (32px de alto, ultracompacta) --}}
    <div @click="selectUnit({{ $node['id'] }})"
         style="padding-left: {{ $paddingLeft }}px"
         class="group flex items-center gap-1.5 pr-2 py-1.5 rounded-lg text-xs cursor-pointer select-none transition-colors"
         :class="selectedId === {{ $node['id'] }}
            ? 'bg-zinc-900 text-white font-medium shadow-xs'
            : (isMatch({{ $node['id'] }})
                ? 'bg-amber-100 text-amber-950 font-medium ring-1 ring-amber-300'
                : 'text-zinc-700 hover:bg-zinc-100/90')">

        {{-- Chevron desplegable o punto de hoja --}}
        @if ($hasChildren)
            <button type="button"
                    @click.stop="toggleExpand({{ $node['id'] }})"
                    class="w-4 h-4 flex items-center justify-center rounded shrink-0 transition-transform duration-150"
                    :class="selectedId === {{ $node['id'] }} ? 'text-zinc-300 hover:text-white' : 'text-zinc-400 hover:text-zinc-700'">
                <svg class="w-3 h-3 transition-transform duration-150"
                     :class="isExpanded({{ $node['id'] }}) ? 'rotate-90' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        @else
            <span class="w-4 h-4 flex items-center justify-center shrink-0 opacity-40 text-[9px]">&bull;</span>
        @endif

        {{-- Punto de Nivel --}}
        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $pipColor }}"
              :class="selectedId === {{ $node['id'] }} ? 'bg-amber-300' : ''"></span>

        {{-- Nombre de la Unidad --}}
        <span class="truncate flex-1 leading-tight tracking-tight" title="{{ $node['name'] }}">
            {{ $node['name'] }}
        </span>

        {{-- Código (si existe) --}}
        @if (!empty($node['code']))
            <span class="px-1 py-0.2 rounded font-mono text-[9px] shrink-0 border"
                  :class="selectedId === {{ $node['id'] }} ? 'bg-white/10 text-zinc-200 border-white/20' : 'bg-zinc-100 text-zinc-500 border-zinc-200/80'">
                {{ $node['code'] }}
            </span>
        @endif

        {{-- Equipos OTI badge (si tiene) --}}
        @if (($node['assets_count'] ?? 0) > 0)
            <span class="px-1 py-0.2 rounded font-mono text-[9px] shrink-0 border flex items-center gap-0.5"
                  :class="selectedId === {{ $node['id'] }} ? 'bg-white/10 text-white border-white/20' : 'bg-blue-50 text-blue-700 border-blue-200/60'">
                <span>💻</span>
                <span>{{ $node['assets_count'] }}</span>
            </span>
        @endif

    </div>

    {{-- Subunidades Recursivas --}}
    @if ($hasChildren)
        <div x-show="isExpanded({{ $node['id'] }})"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="space-y-0.5 mt-0.5">
            @foreach ($node['children'] as $child)
                @include('organizational-units.partials.compact-tree-item', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif

</div>
