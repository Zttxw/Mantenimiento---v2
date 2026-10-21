<x-layouts.app title="Oficinas y Dependencias" moduleLabel="Organización" pageTitle="Estructura Institucional">

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
                    {{ $units->total() }}
                </span>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Estructura jerárquica de gerencias, subgerencias, áreas y oficinas de la Municipalidad Distrital de San Jerónimo.</p>
        </div>

        @can('org-unit.create')
        <a href="{{ route('organizational-units.create') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Nueva Oficina</span>
        </a>
        @endcan
    </div>

    {{-- Tarjetas de Métricas Rápidas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Total Unidades</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['total'] ?? $units->total()) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Dependencias registradas</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Unidades Activas</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-emerald-600 font-mono flex items-center gap-2">
                {{ number_format($stats['active'] ?? 0) }}
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Operativas en el sistema</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Con Responsable</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['with_responsible'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Titular asignado</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Con Equipos OTI</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['with_assets'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Con activos tecnológicos</div>
        </div>
    </div>

    {{-- Filtros y Búsqueda Directa --}}
    <form method="GET" action="{{ route('organizational-units.index') }}" class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
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
                                @can('org-unit.delete')
                                <form method="POST" action="{{ route('organizational-units.destroy', $unit) }}"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar la unidad {{ $unit->name }}?')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-medium text-rose-600 hover:text-rose-800 hover:bg-rose-50 rounded transition-colors">
                                        Eliminar
                                    </button>
                                </form>
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

</x-layouts.app>
