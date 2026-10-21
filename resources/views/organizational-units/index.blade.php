<x-layouts.app title="Estructura Organizacional" moduleLabel="Organización" pageTitle="Estructura Institucional">

<div x-data="orgHierarchy()" x-init="init()" class="relative">

    {{-- Encabezado Principal Ultraminimalista --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200/80 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Organización Institucional</span>
                <span class="text-zinc-300">/</span>
                <span class="text-[11px] font-medium text-zinc-500">Estructura Orgánica</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Oficinas y Dependencias</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                    {{ $stats['total'] ?? $units->total() }} dependencias
                </span>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Estructura jerárquica de gobierno, gerencias, subgerencias, áreas y oficinas de la Municipalidad Distrital de San Jerónimo.</p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            @can('org-unit.create')
            <a href="{{ route('organizational-units.create') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Oficina</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- Tarjetas de Métricas Institucionales --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Total Unidades</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['total'] ?? $units->total()) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">En el organigrama municipal</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Unidades Activas</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-emerald-600 font-mono flex items-center gap-2">
                {{ number_format($stats['active'] ?? 0) }}
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Operativas en funciones</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Con Responsable Titular</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['with_responsible'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Funcionario a cargo asignado</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Con Equipos OTI</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['with_assets'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Con activos informáticos</div>
        </div>
    </div>

    {{-- Selector de Modo de Visualización (Pestañas Organigrama Gráfico vs Directorio Tabular) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-zinc-200/60">
        <div class="inline-flex items-center p-1 rounded-xl bg-zinc-100 border border-zinc-200/80 text-xs">
            <button type="button"
                    @click="currentView = 'tree'"
                    :class="currentView === 'tree' ? 'bg-white text-zinc-900 font-semibold shadow-xs' : 'text-zinc-600 hover:text-zinc-900'"
                    class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                <span>Organigrama Institucional (Estructura)</span>
            </button>
            <button type="button"
                    @click="currentView = 'table'"
                    :class="currentView === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-xs' : 'text-zinc-600 hover:text-zinc-900'"
                    class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg transition-all">
                <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 011.875 1.875v11.25a1.875 1.875 0 01-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V6.375A1.875 1.875 0 015.625 4.5z"/></svg>
                <span>Directorio Tabular (Lista)</span>
            </button>
        </div>

        <div class="text-xs text-zinc-400">
            <span x-show="currentView === 'tree'">Explora el árbol institucional expandiendo ramas o haciendo clic en cualquier nodo.</span>
            <span x-show="currentView === 'table'">Búsqueda y paginación rápida de todas las unidades del municipio.</span>
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 1: ORGANIGRAMA ESTRUCTURADO (ÁRBOL GRÁFICO INTERACTIVO)              --}}
    {{-- ========================================================================= --}}
    <div x-show="currentView === 'tree'" x-cloak class="space-y-4">

        {{-- Barra de Herramientas del Organigrama --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
            {{-- Buscador en tiempo real del Árbol --}}
            <div class="relative flex-1 max-w-md">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       x-model="treeSearch"
                       @input="onTreeSearch()"
                       placeholder="Buscar en el organigrama (oficina, código o titular)..."
                       class="w-full pl-9 pr-8 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                <button type="button"
                        x-show="treeSearch.length > 0"
                        @click="treeSearch = ''; onTreeSearch()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-700 text-xs">
                    ✕
                </button>
            </div>

            {{-- Contadores y Botones de Nivel --}}
            <div class="flex items-center gap-1.5 flex-wrap text-xs">
                <span x-show="matchedIds.length > 0" class="px-2 py-1 rounded bg-amber-50 text-amber-800 border border-amber-200/80 text-[11px] font-medium mr-2">
                    <span x-text="matchedIds.length"></span> coincidencia(s)
                </span>

                <div class="flex items-center gap-1 bg-zinc-100 p-1 rounded-lg border border-zinc-200/60">
                    <button type="button"
                            @click="expandLevel(2)"
                            class="px-2 py-1 rounded text-[11px] font-medium text-zinc-600 hover:text-zinc-900 hover:bg-white transition-colors"
                            title="Solo Alta Dirección y Control">
                        Dirección (N1)
                    </button>
                    <button type="button"
                            @click="expandLevel(3)"
                            class="px-2 py-1 rounded text-[11px] font-medium text-zinc-600 hover:text-zinc-900 hover:bg-white transition-colors"
                            title="Ver Gerencias y Oficinas Generales">
                        Gerencias (N2)
                    </button>
                    <button type="button"
                            @click="expandLevel(4)"
                            class="px-2 py-1 rounded text-[11px] font-medium text-zinc-600 hover:text-zinc-900 hover:bg-white transition-colors"
                            title="Ver Subgerencias y Oficinas">
                        Subgerencias (N3)
                    </button>
                </div>

                <button type="button"
                        @click="expandAll()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
                    Expandir Todo
                </button>
                <button type="button"
                        @click="collapseAll()"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors">
                    Colapsar Todo
                </button>
            </div>
        </div>

        {{-- Lienzo del Organigrama Institucional --}}
        <div class="p-6 bg-zinc-50/50 rounded-2xl border border-zinc-200/80 overflow-x-auto min-h-[500px]">
            <div class="max-w-4xl space-y-4">
                @foreach ($treeData as $rootNode)
                    @include('organizational-units.partials.tree-node', ['node' => $rootNode, 'depth' => 1])
                @endforeach
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 2: DIRECTORIO TABULAR (LISTA CON FILTROS Y PAGINACIÓN)               --}}
    {{-- ========================================================================= --}}
    <div x-show="currentView === 'table'" x-cloak class="space-y-4">

        {{-- Filtros y Búsqueda Directa --}}
        <form method="GET" action="{{ route('organizational-units.index') }}" class="flex flex-wrap items-center gap-2 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
            <div class="relative flex-1 min-w-[220px]">
                <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o código de oficina..."
                       class="w-full pl-9 pr-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
            </div>

            <div class="w-44">
                <select name="type" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                    <option value="">Todos los tipos</option>
                    @foreach (\App\Enums\OrgUnitType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-36">
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                    <option value="">Todos los estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>

            <button type="submit" class="px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
                Filtrar
            </button>

            @if (request()->hasAny(['search', 'type', 'status']))
                <a href="{{ route('organizational-units.index') }}"
                   class="px-2.5 py-2 text-xs font-medium text-zinc-500 hover:text-zinc-900 transition-colors" title="Restablecer filtros">
                    Limpiar
                </a>
            @endif
        </form>

        {{-- Tabla Ultraminimalista de Oficinas --}}
        <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200/80 bg-zinc-50/60 text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Oficina / Dependencia</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4">Depende de</th>
                            <th class="py-3 px-4">Responsable</th>
                            <th class="py-3 px-4 text-center">Personal</th>
                            <th class="py-3 px-4 text-center">Equipos OTI</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 text-xs">
                        @forelse ($units as $unit)
                        <tr class="hover:bg-zinc-50/70 transition-colors group">
                            {{-- Nombre y Código --}}
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('organizational-units.show', $unit) }}"
                                       class="font-semibold text-zinc-900 hover:text-zinc-600 transition-colors">
                                        {{ $unit->name }}
                                    </a>
                                    @if ($unit->code)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                                            {{ $unit->code }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Tipo --}}
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 border border-zinc-200/70">
                                    {{ $unit->type->label() }}
                                </span>
                            </td>

                            {{-- Dependencia Padre --}}
                            <td class="py-3 px-4">
                                @if ($unit->parent)
                                    <a href="{{ route('organizational-units.show', $unit->parent) }}" class="text-zinc-600 hover:text-zinc-900 transition-colors flex items-center gap-1">
                                        <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                                        <span class="truncate max-w-[180px]">{{ $unit->parent->name }}</span>
                                    </a>
                                @else
                                    <span class="text-zinc-400 italic">Jerarquía Principal</span>
                                @endif
                            </td>

                            {{-- Responsable --}}
                            <td class="py-3 px-4">
                                @if ($unit->responsible)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-zinc-800 text-white text-[10px] font-medium flex items-center justify-center shrink-0">
                                            {{ mb_substr($unit->responsible->name ?? 'U', 0, 1) }}{{ mb_substr($unit->responsible->last_name ?? '', 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <a href="{{ route('employees.show', $unit->responsible) }}" class="font-medium text-zinc-800 hover:text-zinc-950 truncate block">
                                                {{ $unit->responsible->full_name }}
                                            </a>
                                            @if ($unit->responsible->position)
                                                <span class="text-[11px] text-zinc-400 block truncate">{{ $unit->responsible->position }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-zinc-400 text-[11px]">Sin responsable</span>
                                @endif
                            </td>

                            {{-- Personal --}}
                            <td class="py-3 px-4 text-center">
                                @php $empCount = $unit->employees_count ?? $unit->empoyees?->count() ?? 0; @endphp
                                @if ($empCount > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-zinc-50 text-zinc-700 border border-zinc-200">
                                        {{ $empCount }}
                                    </span>
                                @else
                                    <span class="text-zinc-300 font-mono text-[11px]">—</span>
                                @endif
                            </td>

                            {{-- Activos OTI --}}
                            <td class="py-3 px-4 text-center">
                                @php $assetCount = $unit->assets_count ?? $unit->assets?->count() ?? 0; @endphp
                                @if ($assetCount > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                        {{ $assetCount }}
                                    </span>
                                @else
                                    <span class="text-zinc-300 font-mono text-[11px]">—</span>
                                @endif
                            </td>

                            {{-- Estado --}}
                            <td class="py-3 px-4">
                                @if ($unit->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-zinc-700 text-[11px] font-medium">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Activa
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-zinc-400 text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                                        Inactiva
                                    </span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('organizational-units.show', $unit) }}"
                                       class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100 rounded transition-colors">
                                        Ver
                                    </a>
                                    @can('org-unit.edit')
                                    <a href="{{ route('organizational-units.edit', $unit) }}"
                                       class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100 rounded transition-colors">
                                        Editar
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                <p class="text-sm font-medium">No se encontraron oficinas o dependencias.</p>
                                <p class="text-xs text-zinc-400 mt-1">Prueba ajustando el término de búsqueda o limpiando los filtros.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if ($units->hasPages())
            <div class="p-4 border-t border-zinc-200/80 bg-zinc-50/40">
                {{ $units->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- PANEL LATERAL INSPECTOR (SLIDE-OVER DRAWER AL CLIC EN CUALQUIER NODO)     --}}
    {{-- ========================================================================= --}}
    <div x-show="selectedNode !== null"
         x-cloak
         class="fixed inset-0 z-50 overflow-hidden"
         aria-labelledby="slide-over-title"
         role="dialog"
         aria-modal="true">

        {{-- Backdrop desenfocado --}}
        <div x-show="selectedNode !== null"
             x-transition:enter="ease-in-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="closeInspector()"
             class="fixed inset-0 bg-zinc-900/40 backdrop-blur-xs transition-opacity"></div>

        <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
            <div x-show="selectedNode !== null"
                 x-transition:enter="transform transition ease-in-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-300"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md bg-white border-l border-zinc-200 shadow-2xl flex flex-col">

                {{-- Encabezado del Slide-over --}}
                <div class="p-6 border-b border-zinc-200/80 bg-zinc-50/60 flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-semibold bg-zinc-200/80 text-zinc-700"
                                  x-text="selectedNode ? selectedNode.type_label : ''"></span>
                            <span x-show="selectedNode && selectedNode.code"
                                  class="px-2 py-0.5 rounded text-[10px] font-mono bg-zinc-100 text-zinc-700 border border-zinc-200"
                                  x-text="selectedNode ? selectedNode.code : ''"></span>
                        </div>
                        <h3 class="text-lg font-bold text-zinc-900" x-text="selectedNode ? selectedNode.name : ''"></h3>
                        <p class="text-xs text-zinc-500 mt-1" x-show="selectedNode && selectedNode.parent_name">
                            Depende de: <span class="font-medium text-zinc-700" x-text="selectedNode.parent_name"></span>
                        </p>
                    </div>

                    <button type="button"
                            @click="closeInspector()"
                            class="rounded-lg p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200/50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Contenido del Inspector --}}
                <div class="p-6 space-y-6 flex-1 overflow-y-auto text-xs">

                    {{-- Métricas Rápidas de la Dependencia --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/60">
                            <span class="text-[10px] uppercase font-semibold text-zinc-400 block">Personal Adscrito</span>
                            <span class="text-xl font-bold font-mono text-zinc-900 mt-0.5 block" x-text="selectedNode ? selectedNode.employees_count : 0"></span>
                            <span class="text-[10px] text-zinc-400">Trabajadores</span>
                        </div>
                        <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/60">
                            <span class="text-[10px] uppercase font-semibold text-zinc-400 block">Equipos Informáticos</span>
                            <span class="text-xl font-bold font-mono text-blue-700 mt-0.5 block" x-text="selectedNode ? selectedNode.assets_count : 0"></span>
                            <span class="text-[10px] text-zinc-400">Activos OTI</span>
                        </div>
                    </div>

                    {{-- Responsable Titular --}}
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2">Responsable Asignado</span>
                        <template x-if="selectedNode && selectedNode.responsible">
                            <div class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/60 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-zinc-900 text-white font-bold text-sm flex items-center justify-center shrink-0"
                                     x-text="selectedNode.responsible.name.charAt(0)"></div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="font-semibold text-zinc-900 truncate" x-text="selectedNode.responsible.name"></h4>
                                    <p class="text-zinc-500 text-[11px] truncate" x-text="selectedNode.responsible.position || 'Responsable titular'"></p>
                                    <p class="text-zinc-400 font-mono text-[10px] mt-0.5" x-text="'DNI: ' + selectedNode.responsible.dni"></p>
                                </div>
                            </div>
                        </template>
                        <template x-if="selectedNode && !selectedNode.responsible">
                            <div class="p-4 rounded-xl bg-zinc-50 border border-dashed border-zinc-200 text-center text-zinc-400">
                                Sin funcionario responsable asignado actualmente.
                            </div>
                        </template>
                    </div>

                    {{-- Subunidades dependientes directas --}}
                    <div x-show="selectedNode && selectedNode.children && selectedNode.children.length > 0">
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Subunidades Dependientes (' + (selectedNode ? selectedNode.children.length : 0) + ')'"></span>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                            <template x-for="child in (selectedNode ? selectedNode.children : [])" :key="child.id">
                                <div @click="selectNode(child)"
                                     class="p-2.5 rounded-lg bg-zinc-50 hover:bg-zinc-100 border border-zinc-200/60 cursor-pointer flex items-center justify-between transition-colors">
                                    <span class="font-medium text-zinc-800 truncate" x-text="child.name"></span>
                                    <span class="text-[10px] font-mono text-zinc-400" x-text="child.type_label"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>

                {{-- Pie de Acciones del Inspector --}}
                <div class="p-4 border-t border-zinc-200/80 bg-zinc-50/50 flex items-center gap-2">
                    <a :href="selectedNode ? selectedNode.show_url : '#'"
                       class="flex-1 text-center py-2 px-3 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                        Ver Ficha Completa &rarr;
                    </a>
                    @can('org-unit.edit')
                    <a :href="selectedNode ? selectedNode.edit_url : '#'"
                       class="py-2 px-3 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-100 transition-colors">
                        Editar
                    </a>
                    @endcan
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Script del Controlador Jerárquico en Alpine --}}
<script>
    function orgHierarchy() {
        return {
            currentView: '{{ request()->hasAny(['search', 'type', 'status']) ? 'table' : 'tree' }}',
            expanded: { 1: true, 6: true },
            treeSearch: '',
            matchedIds: [],
            selectedNode: null,

            init() {
                // Auto-expande niveles iniciales
                this.expanded[1] = true;
                this.expanded[6] = true;
            },

            toggleExpand(id) {
                this.expanded[id] = !this.expanded[id];
            },

            isExpanded(id) {
                if (this.treeSearch.trim().length > 1) {
                    return true;
                }
                return !!this.expanded[id];
            },

            expandAll() {
                const nodes = document.querySelectorAll('.node-container');
                nodes.forEach(el => {
                    const id = parseInt(el.getAttribute('data-node-id'));
                    if (id) this.expanded[id] = true;
                });
            },

            collapseAll() {
                this.expanded = { 1: true };
            },

            expandLevel(maxLevel) {
                const nodes = document.querySelectorAll('.node-container');
                const newExp = {};
                nodes.forEach(el => {
                    const id = parseInt(el.getAttribute('data-node-id'));
                    const level = parseInt(el.getAttribute('data-level'));
                    if (level < maxLevel) {
                        newExp[id] = true;
                    }
                });
                this.expanded = newExp;
            },

            onTreeSearch() {
                const q = this.treeSearch.trim().toLowerCase();
                if (q.length < 2) {
                    this.matchedIds = [];
                    return;
                }
                const matches = [];
                const nodes = document.querySelectorAll('.node-container');
                nodes.forEach(el => {
                    const text = el.getAttribute('data-search-text') || '';
                    const id = parseInt(el.getAttribute('data-node-id'));
                    if (text.includes(q)) {
                        matches.push(id);
                    }
                });
                this.matchedIds = matches;
            },

            isMatchedBySearch(id) {
                return this.matchedIds.includes(id);
            },

            shouldShowNode(id) {
                return true;
            },

            selectNode(node) {
                this.selectedNode = node;
            },

            closeInspector() {
                this.selectedNode = null;
            }
        };
    }
</script>

</x-layouts.app>
