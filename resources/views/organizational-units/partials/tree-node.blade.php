@props(['node', 'depth' => 1])

@php
    $hasChildren = count($node['children'] ?? []) > 0;
    $typeLabel = $node['type_label'] ?? 'Oficina';
    $type = $node['type'] ?? 'otro';

    // Colores y temas según nivel institucional
    $theme = match(true) {
        $depth === 1 => [
            'card' => 'bg-slate-950 text-white border-slate-800 shadow-md',
            'badge' => 'bg-amber-400/20 text-amber-300 border-amber-400/30',
            'code' => 'bg-white/10 text-white border-white/20',
            'title' => 'text-white font-bold',
            'text' => 'text-slate-300',
            'muted' => 'text-slate-400',
            'accent' => 'bg-amber-400',
            'border' => 'border-amber-400/50',
            'counter' => 'bg-white/10 text-slate-200 border-white/10',
            'btn' => 'bg-white/10 hover:bg-white/20 text-white',
        ],
        $depth === 2 => [
            'card' => 'bg-white text-zinc-900 border-zinc-300 shadow-xs hover:border-zinc-400',
            'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
            'code' => 'bg-zinc-100 text-zinc-700 border-zinc-200',
            'title' => 'text-zinc-900 font-bold',
            'text' => 'text-zinc-700',
            'muted' => 'text-zinc-400',
            'accent' => 'bg-indigo-600',
            'border' => 'border-indigo-400',
            'counter' => 'bg-zinc-50 text-zinc-700 border-zinc-200',
            'btn' => 'bg-zinc-100 hover:bg-zinc-200 text-zinc-800',
        ],
        $depth === 3 => [
            'card' => 'bg-white text-zinc-900 border-zinc-200 shadow-xs hover:border-zinc-300',
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200/80',
            'code' => 'bg-zinc-100 text-zinc-700 border-zinc-200',
            'title' => 'text-zinc-900 font-semibold',
            'text' => 'text-zinc-700',
            'muted' => 'text-zinc-400',
            'accent' => 'bg-blue-500',
            'border' => 'border-blue-400',
            'counter' => 'bg-zinc-50 text-zinc-700 border-zinc-200',
            'btn' => 'bg-zinc-100 hover:bg-zinc-200 text-zinc-800',
        ],
        $depth === 4 => [
            'card' => 'bg-white text-zinc-900 border-zinc-200 shadow-xs hover:border-zinc-300',
            'badge' => 'bg-teal-50 text-teal-700 border-teal-200/80',
            'code' => 'bg-zinc-100 text-zinc-700 border-zinc-200',
            'title' => 'text-zinc-800 font-medium',
            'text' => 'text-zinc-600',
            'muted' => 'text-zinc-400',
            'accent' => 'bg-teal-500',
            'border' => 'border-teal-400',
            'counter' => 'bg-zinc-50 text-zinc-600 border-zinc-200',
            'btn' => 'bg-zinc-100 hover:bg-zinc-200 text-zinc-800',
        ],
        default => [
            'card' => 'bg-zinc-50/80 text-zinc-800 border-zinc-200 shadow-2xs hover:border-zinc-300',
            'badge' => 'bg-zinc-100 text-zinc-600 border-zinc-200',
            'code' => 'bg-white text-zinc-600 border-zinc-200',
            'title' => 'text-zinc-800 font-medium',
            'text' => 'text-zinc-600',
            'muted' => 'text-zinc-400',
            'accent' => 'bg-zinc-400',
            'border' => 'border-zinc-300',
            'counter' => 'bg-white text-zinc-600 border-zinc-200',
            'btn' => 'bg-zinc-200/80 hover:bg-zinc-300 text-zinc-800',
        ],
    };
@endphp

<div class="relative node-container"
     id="node-{{ $node['id'] }}"
     data-node-id="{{ $node['id'] }}"
     data-parent-id="{{ $node['parent_id'] ?? '' }}"
     data-level="{{ $depth }}"
     data-search-text="{{ mb_strtolower(($node['name'] ?? '') . ' ' . ($node['code'] ?? '') . ' ' . ($node['type_label'] ?? '') . ' ' . ($node['responsible']['name'] ?? '')) }}"
     x-show="shouldShowNode({{ $node['id'] }})"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0">

    {{-- Línea conectora horizontal hacia el nodo --}}
    @if ($depth > 1)
        <div class="absolute -left-6 top-6 w-6 h-0.5 bg-zinc-300/80"></div>
    @endif

    {{-- Tarjeta del Nodo Organizacional --}}
    <div class="relative group rounded-xl border p-4 transition-all duration-150 {{ $theme['card'] }}"
         :class="{
             'ring-2 ring-zinc-900 shadow-md': selectedNode && selectedNode.id === {{ $node['id'] }},
             'ring-2 ring-amber-400 ring-offset-2': isMatchedBySearch({{ $node['id'] }})
         }">

        {{-- Barra de Nivel Superior --}}
        <div class="flex items-center justify-between gap-2 mb-2">
            <div class="flex items-center gap-1.5 flex-wrap">
                {{-- Nivel jerárquico --}}
                <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold tracking-wider uppercase border {{ $theme['badge'] }}">
                    Nivel {{ $depth }} &bull; {{ $typeLabel }}
                </span>

                @if (!empty($node['code']))
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono border {{ $theme['code'] }}">
                        {{ $node['code'] }}
                    </span>
                @endif
            </div>

            {{-- Estado / Indicador --}}
            <div class="flex items-center gap-1.5">
                @if ($node['is_active'])
                    <span class="w-2 h-2 rounded-full bg-emerald-500" title="Unidad Activa"></span>
                @else
                    <span class="w-2 h-2 rounded-full bg-zinc-400" title="Unidad Inactiva"></span>
                @endif
            </div>
        </div>

        {{-- Nombre de la Unidad (Clic para inspeccionar en slide-over) --}}
        <div class="cursor-pointer mb-2.5" @click="selectNode({{ json_encode($node, JSON_HEX_APOS | JSON_HEX_QUOT) }})">
            <h4 class="text-sm tracking-tight hover:underline transition-all {{ $theme['title'] }}">
                {{ $node['name'] }}
            </h4>
        </div>

        {{-- Titular / Responsable --}}
        <div class="flex items-center gap-2 mb-3 py-1.5 px-2 rounded-lg {{ $depth === 1 ? 'bg-white/5 border border-white/10' : 'bg-zinc-50 border border-zinc-200/60' }}">
            @if (!empty($node['responsible']))
                <div class="w-6 h-6 rounded-full {{ $depth === 1 ? 'bg-amber-400 text-slate-950' : 'bg-zinc-800 text-white' }} font-bold text-[10px] flex items-center justify-center shrink-0">
                    {{ mb_substr($node['responsible']['name'] ?? 'R', 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-xs font-medium truncate block {{ $depth === 1 ? 'text-white' : 'text-zinc-900' }}">
                        {{ $node['responsible']['name'] }}
                    </span>
                    @if (!empty($node['responsible']['position']))
                        <span class="text-[10px] truncate block {{ $theme['muted'] }}">{{ $node['responsible']['position'] }}</span>
                    @endif
                </div>
            @else
                <div class="w-6 h-6 rounded-full {{ $depth === 1 ? 'bg-white/10' : 'bg-zinc-200' }} text-zinc-400 text-[10px] flex items-center justify-center shrink-0">
                    &bull;
                </div>
                <span class="text-xs italic {{ $theme['muted'] }}">Sin responsable asignado</span>
            @endif
        </div>

        {{-- Pie de Tarjeta: Contadores y Botones de Acción --}}
        <div class="flex items-center justify-between gap-2 pt-2 border-t {{ $depth === 1 ? 'border-white/10' : 'border-zinc-100' }}">
            {{-- Conteo de Personal y Activos --}}
            <div class="flex items-center gap-1.5 text-[11px] font-mono">
                <span class="px-1.5 py-0.5 rounded border flex items-center gap-1 {{ $theme['counter'] }}" title="Personal adscrito">
                    <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    {{ $node['employees_count'] }}
                </span>
                <span class="px-1.5 py-0.5 rounded border flex items-center gap-1 {{ $theme['counter'] }}" title="Equipos OTI en custodia">
                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    {{ $node['assets_count'] }}
                </span>
            </div>

            {{-- Acciones: Desplegar hijos y Ficha --}}
            <div class="flex items-center gap-1">
                @if ($hasChildren)
                    <button type="button"
                            @click="toggleExpand({{ $node['id'] }})"
                            class="px-2 py-1 rounded text-[11px] font-medium flex items-center gap-1 transition-colors {{ $theme['btn'] }}"
                            :title="isExpanded({{ $node['id'] }}) ? 'Colapsar subunidades' : 'Expandir subunidades'">
                        <span x-text="isExpanded({{ $node['id'] }}) ? '▾' : '▸'"></span>
                        <span x-text="isExpanded({{ $node['id'] }}) ? 'Ocultar' : '{{ count($node['children']) }} subunidades'"></span>
                    </button>
                @else
                    <span class="text-[10px] font-mono {{ $theme['muted'] }} px-1.5 py-0.5">
                        Hoja
                    </span>
                @endif

                {{-- Botón rápido de inspección lateral --}}
                <button type="button"
                        @click="selectNode({{ json_encode($node, JSON_HEX_APOS | JSON_HEX_QUOT) }})"
                        class="p-1 rounded text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors"
                        title="Ver detalles rápidos">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>

                {{-- Enlace directo a la ficha completa --}}
                <a href="{{ $node['show_url'] }}"
                   class="p-1 rounded text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100 transition-colors"
                   title="Abrir ficha completa de la unidad">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>

    </div>

    {{-- Sub-rama con Nodos Hijos (Conectores en árbol) --}}
    @if ($hasChildren)
        <div class="relative ml-6 pl-6 mt-3 space-y-3 border-l-2 border-zinc-200/80 transition-all duration-200"
             x-show="isExpanded({{ $node['id'] }})"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2">

            @foreach ($node['children'] as $child)
                @include('organizational-units.partials.tree-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif

</div>
