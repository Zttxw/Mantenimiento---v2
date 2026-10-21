<x-layouts.app title="Personal y Responsables" moduleLabel="Organización" pageTitle="Directorio de Personal">

    {{-- Encabezado Principal Ultraminimalista --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200/80 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Organización Institucional</span>
                <span class="text-zinc-300">/</span>
                <span class="text-[11px] font-medium text-zinc-500">Directorio Humano</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Personal y Responsables</h1>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                    {{ $employees->total() }}
                </span>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Directorio de trabajadores, custodios de activos informáticos y personal técnico de la municipalidad.</p>
        </div>

        @can('employee.create')
        <a href="{{ route('employees.create') }}"
           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Nuevo Empleado</span>
        </a>
        @endcan
    </div>

    {{-- Tarjetas de Métricas Rápidas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Total Personal</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['total'] ?? $employees->total()) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Trabajadores registrados</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Personal Activo</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-emerald-600 font-mono flex items-center gap-2">
                {{ number_format($stats['active'] ?? 0) }}
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            </div>
            <div class="text-[11px] text-zinc-400 mt-0.5">En planilla / funciones</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Custodios de Bienes</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-zinc-900 font-mono">{{ number_format($stats['with_assets'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Con activos a su cargo</div>
        </div>
        <div class="p-4 rounded-xl bg-white border border-zinc-200/80 shadow-xs">
            <div class="text-[11px] font-medium text-zinc-400 uppercase tracking-wider">Técnicos OTI</div>
            <div class="mt-1 text-2xl font-bold tracking-tight text-amber-600 font-mono">{{ number_format($stats['technicians'] ?? 0) }}</div>
            <div class="text-[11px] text-zinc-400 mt-0.5">Equipo técnico de campo</div>
        </div>
    </div>

    {{-- Filtros y Búsqueda Directa --}}
    <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap items-center gap-2 mb-4 p-3 bg-white rounded-xl border border-zinc-200/80 shadow-xs">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, DNI o correo..."
                   class="w-full pl-9 pr-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
        </div>

        <div class="w-56">
            <select name="unit_id" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                <option value="">Todas las oficinas</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-36">
            <select name="is_technician" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                <option value="">Todos los roles</option>
                <option value="1" {{ request('is_technician') === '1' ? 'selected' : '' }}>Solo técnicos OTI</option>
                <option value="0" {{ request('is_technician') === '0' ? 'selected' : '' }}>Personal general</option>
            </select>
        </div>

        <div class="w-32">
            <select name="status" onchange="this.form.submit()"
                    class="w-full px-3 py-2 bg-zinc-50 hover:bg-white focus:bg-white rounded-lg border border-zinc-200 text-xs text-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition-colors">
                <option value="">Todos</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activos</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
            </select>
        </div>

        <button type="submit" class="px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors">
            Filtrar
        </button>

        @if (request()->hasAny(['search', 'unit_id', 'is_technician', 'status']))
            <a href="{{ route('employees.index') }}"
               class="px-2.5 py-2 text-xs font-medium text-zinc-500 hover:text-zinc-900 transition-colors" title="Restablecer filtros">
                Limpiar
            </a>
        @endif
    </form>

    {{-- Tabla Ultraminimalista de Empleados --}}
    <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-zinc-200/80 bg-zinc-50/60 text-[11px] font-semibold text-zinc-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Trabajador / Responsable</th>
                        <th class="py-3 px-4">DNI</th>
                        <th class="py-3 px-4">Oficina / Dependencia</th>
                        <th class="py-3 px-4">Cargo</th>
                        <th class="py-3 px-4">Rol</th>
                        <th class="py-3 px-4 text-center">Bienes en Custodia</th>
                        <th class="py-3 px-4">Estado</th>
                        <th class="py-3 px-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 text-xs">
                    @forelse ($employees as $employee)
                    <tr class="hover:bg-zinc-50/70 transition-colors group">
                        {{-- Avatar y Nombre --}}
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-zinc-900 text-white font-medium text-xs flex items-center justify-center shrink-0">
                                    {{ mb_substr($employee->name ?? 'E', 0, 1) }}{{ mb_substr($employee->last_name ?? '', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('employees.show', $employee) }}"
                                       class="font-semibold text-zinc-900 hover:text-zinc-600 transition-colors block truncate">
                                        {{ $employee->full_name }}
                                    </a>
                                    @if ($employee->email)
                                        <span class="text-[11px] text-zinc-400 block truncate">{{ $employee->email }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- DNI --}}
                        <td class="py-3 px-4">
                            <span class="font-mono text-xs text-zinc-700 bg-zinc-100 px-2 py-0.5 rounded border border-zinc-200/60">
                                {{ $employee->dni }}
                            </span>
                        </td>

                        {{-- Oficina / Dependencia --}}
                        <td class="py-3 px-4">
                            @if ($employee->organizationalUnit)
                                <a href="{{ route('organizational-units.show', $employee->organizationalUnit) }}"
                                   class="text-zinc-700 hover:text-zinc-950 transition-colors font-medium flex items-center gap-1 group/link">
                                    <span class="truncate max-w-[200px]">{{ $employee->organizationalUnit->name }}</span>
                                    <svg class="w-3 h-3 text-zinc-400 group-hover/link:text-zinc-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                                </a>
                            @else
                                <span class="text-zinc-400 italic">Sin oficina</span>
                            @endif
                        </td>

                        {{-- Cargo --}}
                        <td class="py-3 px-4">
                            <span class="text-zinc-600 truncate block max-w-[180px]">{{ $employee->position ?? '—' }}</span>
                        </td>

                        {{-- Rol --}}
                        <td class="py-3 px-4">
                            @if ($employee->is_technician)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60">
                                    Técnico OTI
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-zinc-100 text-zinc-600 border border-zinc-200/60">
                                    Personal
                                </span>
                            @endif
                        </td>

                        {{-- Bienes en Custodia --}}
                        <td class="py-3 px-4 text-center">
                            @php $assetCount = $employee->assigned_assets_count ?? $employee->assignedAssets?->count() ?? 0; @endphp
                            @if ($assetCount > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-blue-50 text-blue-700 border border-blue-200/60">
                                    {{ $assetCount }} {{ $assetCount === 1 ? 'activo' : 'activos' }}
                                </span>
                            @else
                                <span class="text-zinc-300 font-mono text-[11px]">—</span>
                            @endif
                        </td>

                        {{-- Estado --}}
                        <td class="py-3 px-4">
                            @if ($employee->is_active)
                                <span class="inline-flex items-center gap-1.5 text-zinc-700 text-[11px] font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-zinc-400 text-[11px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-300"></span>
                                    Inactivo
                                </span>
                            @endif
                        </td>

                        {{-- Acciones --}}
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('employees.show', $employee) }}"
                                   class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100 rounded transition-colors">
                                    Ver
                                </a>
                                @can('employee.edit')
                                <a href="{{ route('employees.edit', $employee) }}"
                                   class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 hover:bg-zinc-100 rounded transition-colors">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('employees.toggle-active', $employee) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="px-2 py-1 text-xs font-medium text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 rounded transition-colors">
                                        {{ $employee->is_active ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>
                                @endcan
                                @can('employee.delete')
                                <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                      onsubmit="return confirm('¿Seguro que deseas eliminar a {{ $employee->full_name }}?')" class="inline">
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
                            <p class="text-sm font-medium">No se encontraron empleados o responsables.</p>
                            <p class="text-xs text-zinc-400 mt-1">Prueba ajustando el término de búsqueda o seleccionando otra oficina.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if ($employees->hasPages())
        <div class="p-4 border-t border-zinc-200/80 bg-zinc-50/40">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

</x-layouts.app>
