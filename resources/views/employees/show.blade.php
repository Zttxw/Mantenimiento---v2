<x-layouts.app :title="$employee->full_name" moduleLabel="Organización" pageTitle="Perfil de Empleado">

    {{-- Encabezado Principal Ultraminimalista --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-6 border-b border-zinc-200/80 mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('employees.index') }}" class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400 hover:text-zinc-600 transition-colors">Personal y Responsables</a>
                <span class="text-zinc-300">/</span>
                <span class="text-[11px] font-medium text-zinc-500">{{ $employee->position ?? 'Trabajador' }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ $employee->full_name }}</h1>
                <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-zinc-100 text-zinc-700 border border-zinc-200">
                    DNI {{ $employee->dni }}
                </span>
                @if ($employee->is_technician)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60">
                        Técnico OTI
                    </span>
                @endif
                @if ($employee->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-zinc-100 text-zinc-500 border border-zinc-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                        Inactivo
                    </span>
                @endif
            </div>
            <p class="text-xs text-zinc-500 mt-1">
                {{ $employee->organizationalUnit?->name ?? 'Sin oficina asignada' }}
                @if ($employee->position) &bull; {{ $employee->position }} @endif
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('employees.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white text-zinc-700 hover:bg-zinc-50 border border-zinc-200 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                <span>Volver</span>
            </a>
            @can('employee.edit')
            <a href="{{ route('employees.edit', $employee) }}"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                <span>Editar</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- Layout Principal: Perfil a la izquierda + Activos y Casos a la derecha --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Columna Lateral: Ficha de Perfil & Cuenta --}}
        <div class="lg:col-span-4 space-y-6">

            {{-- Tarjeta de Identidad --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 p-5 shadow-xs">
                <div class="flex flex-col items-center text-center pb-5 border-b border-zinc-100">
                    <div class="w-16 h-16 rounded-full bg-zinc-900 text-white font-bold text-lg flex items-center justify-center shadow-xs mb-3">
                        {{ mb_substr($employee->name ?? 'E', 0, 1) }}{{ mb_substr($employee->last_name ?? '', 0, 1) }}
                    </div>
                    <h2 class="text-base font-bold text-zinc-900">{{ $employee->full_name }}</h2>
                    <p class="text-xs text-zinc-500 mt-0.5">{{ $employee->position ?? 'Sin cargo asignado' }}</p>
                    @if ($employee->organizationalUnit)
                        <a href="{{ route('organizational-units.show', $employee->organizationalUnit) }}"
                           class="inline-flex items-center gap-1 text-[11px] text-zinc-600 hover:text-zinc-950 font-medium mt-1 bg-zinc-100 px-2 py-0.5 rounded transition-colors">
                            <span>{{ $employee->organizationalUnit->name }}</span>
                            <svg class="w-3 h-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/></svg>
                        </a>
                    @endif
                </div>

                <dl class="space-y-3 text-xs pt-4">
                    <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100">
                        <dt class="text-zinc-400">DNI</dt>
                        <dd class="font-mono font-medium text-zinc-800">{{ $employee->dni }}</dd>
                    </div>

                    <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100">
                        <dt class="text-zinc-400">Correo Electrónico</dt>
                        <dd class="font-medium text-zinc-800 text-right truncate max-w-[200px]">{{ $employee->email ?? '—' }}</dd>
                    </div>

                    <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100">
                        <dt class="text-zinc-400">Teléfono / Celular</dt>
                        <dd class="font-mono font-medium text-zinc-800">{{ $employee->phone ?? '—' }}</dd>
                    </div>

                    @if ($employee->is_technician)
                    <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100">
                        <dt class="text-zinc-400">Especialidad</dt>
                        <dd class="font-medium text-zinc-800">{{ $employee->specialty ?? 'Soporte OTI' }}</dd>
                    </div>
                    <div class="flex items-center justify-between pb-2.5 border-b border-zinc-100">
                        <dt class="text-zinc-400">Nivel Técnico</dt>
                        <dd class="font-medium text-zinc-800">{{ $employee->technical_level ?? 'Operador' }}</dd>
                    </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-400">Equipos Asignados</dt>
                        <dd class="font-mono font-semibold text-zinc-900">{{ $employee->assignedAssets->count() }} activos</dd>
                    </div>
                </dl>

                @can('employee.edit')
                <div class="mt-5 pt-4 border-t border-zinc-100">
                    <form method="POST" action="{{ route('employees.toggle-active', $employee) }}" class="w-full">
                        @csrf @method('PATCH')
                        <button type="submit"
                                class="w-full py-2 px-3 rounded-lg text-xs font-medium border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors">
                            {{ $employee->is_active ? 'Desactivar Trabajador' : 'Activar Trabajador' }}
                        </button>
                    </form>
                </div>
                @endcan
            </div>

            {{-- Cuenta de Acceso al Sistema --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 p-5 shadow-xs">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-wider mb-3">Acceso al Sistema</h3>
                @if ($employee->user)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-zinc-50 border border-zinc-200/60 text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-zinc-900 text-white font-medium text-xs flex items-center justify-center shrink-0">
                                {{ mb_substr($employee->user->name ?? 'U', 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <span class="font-semibold text-zinc-900 block truncate">{{ $employee->user->name }}</span>
                                <span class="text-[11px] font-mono text-zinc-400 block truncate">{{ '@' . $employee->user->username }}</span>
                            </div>
                        </div>
                        @can('user.edit')
                        <a href="{{ route('users.edit', $employee->user) }}" class="text-xs font-medium text-zinc-700 hover:text-zinc-950 px-2 py-1 rounded hover:bg-zinc-200/60 transition-colors">
                            Editar
                        </a>
                        @endcan
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-zinc-50 border border-dashed border-zinc-200 text-center text-xs text-zinc-400">
                        Este empleado no tiene cuenta de usuario activa en SIGAT.
                    </div>
                @endif
            </div>

        </div>

        {{-- Columnas Centrales: Activos Asignados y Casos de Soporte --}}
        <div class="lg:col-span-8 space-y-6">

            {{-- Activos Informáticos en Custodia --}}
            <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-zinc-200/80 flex items-center justify-between bg-zinc-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Bienes y Equipos en Custodia</h3>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                            {{ $employee->assignedAssets->count() }}
                        </span>
                    </div>
                    @can('asset.create')
                    <a href="{{ route('assets.create', ['responsible_employee_id' => $employee->id]) }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-950 transition-colors">
                        + Asignar equipo
                    </a>
                    @endcan
                </div>

                @if ($employee->assignedAssets->isEmpty())
                    <div class="p-8 text-center text-zinc-400 text-xs">
                        Este trabajador no tiene activos informáticos asignados actualmente.
                    </div>
                @else
                    <div class="divide-y divide-zinc-200/60">
                        @foreach ($employee->assignedAssets as $asset)
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
                                    @if ($asset->serial_number)
                                        <span>&bull;</span>
                                        <span class="font-mono">S/N: {{ $asset->serial_number }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @can('asset.view')
                                <a href="{{ route('assets.show', $asset) }}" class="px-2.5 py-1 text-xs font-medium text-zinc-700 hover:text-zinc-950 hover:bg-zinc-100 rounded transition-colors">
                                    Ver equipo &rarr;
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Casos de Mantenimiento (si es técnico) --}}
            @if ($employee->is_technician)
            <div class="bg-white rounded-xl border border-zinc-200/80 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-zinc-200/80 flex items-center justify-between bg-zinc-50/50">
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Tickets y Casos Asignados</h3>
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 text-zinc-600 border border-zinc-200">
                            {{ $employee->maintenanceCases->count() }}
                        </span>
                    </div>
                    @can('maintenance-case.view')
                    <a href="{{ route('maintenance-cases.index', ['technician_id' => $employee->id]) }}" class="text-xs font-medium text-zinc-600 hover:text-zinc-950 transition-colors">
                        Ver todos &rarr;
                    </a>
                    @endcan
                </div>

                @if ($employee->maintenanceCases->isEmpty())
                    <div class="p-8 text-center text-zinc-400 text-xs">
                        Sin casos de mantenimiento registrados para este técnico.
                    </div>
                @else
                    <div class="divide-y divide-zinc-200/60">
                        @foreach ($employee->maintenanceCases->take(6) as $case)
                        <div class="p-3.5 hover:bg-zinc-50/70 transition-colors flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('maintenance-cases.show', $case) }}" class="font-mono font-semibold text-zinc-900 hover:text-zinc-600 transition-colors">
                                        {{ $case->code }}
                                    </a>
                                    <span class="text-zinc-400">&bull;</span>
                                    <span class="font-medium text-zinc-700">{{ $case->asset?->name ?? 'Equipo' }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-zinc-400 mt-0.5">
                                    <span>{{ $case->maintenance_type?->label() }}</span>
                                    <span>&bull;</span>
                                    <span>{{ $case->created_at?->format('d/m/Y') }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-zinc-100 text-zinc-700">
                                    {{ $case->status?->label() }}
                                </span>
                                @can('maintenance-case.view')
                                <a href="{{ route('maintenance-cases.show', $case) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors">
                                    Ver &rarr;
                                </a>
                                @endcan
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

        </div>

    </div>

</x-layouts.app>
