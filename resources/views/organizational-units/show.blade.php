<x-layouts.app :title="$organizationalUnit->name" moduleLabel="Organización" pageTitle="Detalle de Unidad">

    {{-- Encabezado Principal Ultraminimalista --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200/80 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('organizational-units.index') }}" class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 hover:text-zinc-600 transition-colors">Oficinas y Dependencias</a>
                <span class="text-zinc-300">/</span>
                <span class="text-[11px] font-medium text-zinc-500">{{ $organizationalUnit->type->label() }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ $organizationalUnit->name }}</h1>
                @if ($organizationalUnit->code)
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-zinc-100 text-zinc-700 border border-zinc-200">
                        {{ $organizationalUnit->code }}
                    </span>
                @endif
                @if ($organizationalUnit->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Activa
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-100 text-zinc-500 border border-zinc-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                        Inactiva
                    </span>
                @endif
            </div>
            <p class="text-xs text-zinc-500 mt-1">{{ $organizationalUnit->full_path ?? 'Estructura institucional MDSJ' }}</p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('organizational-units.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white text-zinc-700 hover:bg-zinc-50 border border-zinc-200 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Volver</span>
            </a>
            @can('org-unit.edit')
            <a href="{{ route('organizational-units.edit', $organizationalUnit) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                <span>Editar</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- Layout Principal: Columna lateral (Información) + Columnas centrales (Personal y Activos) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Columna Lateral: Ficha Técnica & Subunidades --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Ficha de Información --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-4">Información General</h3>

                <dl class="space-y-3.5 text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <dt class="text-zinc-500">Tipo de Dependencia</dt>
                        <dd>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 border border-zinc-200">
                                {{ $organizationalUnit->type->label() }}
                            </span>
                        </dd>
                    </div>

                    @if ($organizationalUnit->code)
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <dt class="text-zinc-500">Código</dt>
                        <dd class="font-mono font-medium text-zinc-800">{{ $organizationalUnit->code }}</dd>
                    </div>
                    @endif

                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <dt class="text-zinc-500">Dependencia Superior</dt>
                        <dd class="text-right">
                            @if ($organizationalUnit->parent)
                                <a href="{{ route('organizational-units.show', $organizationalUnit->parent) }}" class="font-medium text-zinc-800 hover:text-zinc-950 transition-colors">
                                    {{ $organizationalUnit->parent->name }}
                                </a>
                            @else
                                <span class="text-zinc-400 italic">Nivel Superior (Raíz)</span>
                            @endif
                        </dd>
                    </div>

                    <div class="flex items-center justify-between pb-3 border-b border-zinc-100">
                        <dt class="text-zinc-500">Personal Registrado</dt>
                        <dd class="font-mono font-semibold text-zinc-800">{{ $organizationalUnit->empoyees->count() }} personas</dd>
                    </div>

                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500">Equipos Asignados</dt>
                        <dd class="font-mono font-semibold text-zinc-800">{{ $organizationalUnit->assets->count() }} activos</dd>
                    </div>
                </dl>

                {{-- Responsable Titular --}}
                <div class="mt-5 pt-4 border-t border-zinc-100">
                    <span class="text-[11px] font-semibold text-zinc-400 uppercase tracking-wider block mb-2">Responsable Titular</span>
                    @if ($organizationalUnit->responsible)
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-zinc-50 border border-zinc-200/60">
                            <div class="w-9 h-9 rounded-full bg-zinc-900 text-white font-medium text-xs flex items-center justify-center shrink-0">
                                {{ mb_substr($organizationalUnit->responsible->name ?? 'R', 0, 1) }}{{ mb_substr($organizationalUnit->responsible->last_name ?? '', 0, 1) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('employees.show', $organizationalUnit->responsible) }}" class="text-xs font-semibold text-zinc-900 hover:text-zinc-700 truncate block">
                                    {{ $organizationalUnit->responsible->full_name }}
                                </a>
                                <p class="text-[11px] text-zinc-500 truncate">{{ $organizationalUnit->responsible->position ?? 'Responsable de Área' }}</p>
                                @if ($organizationalUnit->responsible->email)
                                    <p class="text-[10px] text-zinc-400 truncate mt-0.5">{{ $organizationalUnit->responsible->email }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="p-3 rounded-xl bg-zinc-50 border border-dashed border-zinc-200 text-center">
                            <p class="text-xs text-zinc-400">Sin responsable asignado actualmente.</p>
                            @can('org-unit.edit')
                            <a href="{{ route('organizational-units.edit', $organizationalUnit) }}" class="text-[11px] font-medium text-zinc-700 hover:underline mt-1 inline-block">
                                + Asignar Responsable
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            {{-- Subunidades Dependientes --}}
            @if ($organizationalUnit->children->isNotEmpty())
            <div class="bg-white rounded-xl border border-zinc-200/80 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider">Subunidades Dependientes</h3>
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                        {{ $organizationalUnit->children->count() }}
                    </span>
                </div>
                <div class="space-y-1.5">
                    @foreach ($organizationalUnit->children as $child)
                    <a href="{{ route('organizational-units.show', $child) }}"
                       class="flex items-center justify-between p-2.5 rounded-lg hover:bg-zinc-50 border border-transparent hover:border-zinc-200/70 transition-all text-xs group">
                        <span class="font-medium text-zinc-800 group-hover:text-zinc-950 truncate">{{ $child->name }}</span>
                        <span class="text-[10px] font-mono text-zinc-400 bg-zinc-100 px-1.5 py-0.5 rounded">{{ $child->type->label() }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Columnas Centrales: Personal y Activos Tecnológicos --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Listado de Personal --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-zinc-200/80 flex items-center justify-between bg-zinc-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Personal Adscrito</h3>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                            {{ $organizationalUnit->empoyees->count() }}
                        </span>
                    </div>
                    @can('employee.create')
                    <a href="{{ route('employees.create', ['unit_id' => $organizationalUnit->id]) }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-950 transition-colors">
                        + Registrar empleado
                    </a>
                    @endcan
                </div>

                @if ($organizationalUnit->empoyees->isEmpty())
                    <div class="p-8 text-center text-zinc-400 text-xs">
                        No hay personal registrado en esta oficina o dependencia.
                    </div>
                @else
                    <div class="divide-y divide-zinc-200/60">
                        @foreach ($organizationalUnit->empoyees as $emp)
                        <div class="p-3.5 hover:bg-zinc-50/70 transition-colors flex items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-zinc-800 text-white font-medium text-xs flex items-center justify-center shrink-0">
                                    {{ mb_substr($emp->name ?? 'E', 0, 1) }}{{ mb_substr($emp->last_name ?? '', 0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('employees.show', $emp) }}" class="font-semibold text-zinc-900 hover:text-zinc-600 transition-colors truncate block">
                                        {{ $emp->full_name }}
                                    </a>
                                    <div class="flex items-center gap-2 text-[11px] text-zinc-400">
                                        <span>{{ $emp->position ?? 'Sin cargo especificado' }}</span>
                                        @if ($emp->dni)
                                            <span>&bull;</span>
                                            <span class="font-mono">DNI {{ $emp->dni }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if ($emp->is_technician)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60">
                                        Técnico OTI
                                    </span>
                                @endif
                                <a href="{{ route('employees.show', $emp) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors">
                                    Ver perfil &rarr;
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Listado de Equipos y Activos OTI --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-zinc-200/80 flex items-center justify-between bg-zinc-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Equipos Informáticos y Activos</h3>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                            {{ $organizationalUnit->assets->count() }}
                        </span>
                    </div>
                    @can('asset.create')
                    <a href="{{ route('assets.create', ['organizational_unit_id' => $organizationalUnit->id]) }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-950 transition-colors">
                        + Registrar activo
                    </a>
                    @endcan
                </div>

                @if ($organizationalUnit->assets->isEmpty())
                    <div class="p-8 text-center text-zinc-400 text-xs">
                        No hay equipos informáticos asignados a esta dependencia.
                    </div>
                @else
                    <div class="divide-y divide-zinc-200/60">
                        @foreach ($organizationalUnit->assets as $asset)
                        <div class="p-3.5 hover:bg-zinc-50/70 transition-colors flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('assets.show', $asset) }}" class="font-semibold text-zinc-900 hover:text-zinc-600 transition-colors">
                                        {{ $asset->name }}
                                    </a>
                                    @if ($asset->internal_code)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-zinc-100 text-zinc-700 border border-zinc-200">
                                            {{ $asset->internal_code }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-zinc-400 mt-0.5">
                                    <span>{{ $asset->asset_type?->label() ?? 'Equipo' }}</span>
                                    @if ($asset->brand)
                                        <span>&bull;</span>
                                        <span>{{ $asset->brand }} {{ $asset->model ?? '' }}</span>
                                    @endif
                                    @if ($asset->responsibleEmployee)
                                        <span>&bull;</span>
                                        <span>Custodio: {{ $asset->responsibleEmployee->full_name }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @can('asset.view')
                                <a href="{{ route('assets.show', $asset) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors">
                                    Ver activo &rarr;
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

</x-layouts.app>
