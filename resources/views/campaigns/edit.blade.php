<x-layouts.app title="Editar Campaña" moduleLabel="Mantenimiento" pageTitle="Editar Campaña">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-zinc-200/80 mb-6 max-w-4xl">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-zinc-400 mb-1.5">
                <a href="{{ route('campaigns.index') }}" class="hover:text-zinc-700 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Campañas</span>
                </a>
                <span>/</span>
                <a href="{{ route('campaigns.show', $campaign) }}" class="font-mono text-zinc-600 hover:text-zinc-900 transition-colors">
                    {{ $campaign->code }}
                </a>
                <span>/</span>
                <span class="text-zinc-600 font-medium">Editar</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Editar Parámetros de Campaña</h1>
            <p class="text-xs text-zinc-500 mt-1">Modifica los datos generales, objetivos y cronograma institucional.</p>
        </div>

        <a href="{{ route('campaigns.show', $campaign) }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 transition-colors">
            Volver a la Campaña
        </a>
    </div>

    <div class="max-w-4xl space-y-6">
        {{-- Formulario Principal --}}
        <form method="POST" action="{{ route('campaigns.update', $campaign) }}" class="space-y-5">
            @csrf @method('PUT')

            <div class="bg-white rounded-2xl border border-zinc-200/90 p-6 shadow-xs space-y-5 text-xs">
                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Nombre de la Campaña *</label>
                    <input type="text" name="name" required value="{{ old('name', $campaign->name) }}"
                           class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-medium">
                    @error('name') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Objetivo Institucional</label>
                    <textarea name="objective" rows="3"
                              class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">{{ old('objective', $campaign->objective) }}</textarea>
                    @error('objective') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-zinc-800 mb-1.5">Coordinador Responsable</label>
                        <select name="coordinator_id"
                                class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            <option value="">Seleccionar coordinador...</option>
                            @foreach ($coordinators as $coord)
                                <option value="{{ $coord->id }}" {{ old('coordinator_id', $campaign->coordinator_id) == $coord->id ? 'selected' : '' }}>
                                    {{ $coord->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('coordinator_id') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-800 mb-1.5">Estado</label>
                        <select name="status"
                                class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" {{ old('status', $campaign->status->value) === $status->value ? 'selected' : '' }}>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('status') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold text-zinc-800 mb-1.5">Fecha de Inicio</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}"
                               class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono">
                        @error('start_date') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-zinc-800 mb-1.5">Fecha de Fin Estimada</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}"
                               class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono">
                        @error('end_date') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2 rounded-lg bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                    Guardar Cambios
                </button>
                <a href="{{ route('campaigns.show', $campaign) }}"
                   class="px-4 py-2 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 text-xs font-medium transition-colors">
                    Cancelar
                </a>
            </div>
        </form>

        {{-- Asignación de Activos --}}
        @can('campaign.edit')
        @if (!in_array($campaign->status->value, ['completada', 'cancelada']))
        <div class="pt-6 border-t border-zinc-200/80">
            <h2 class="text-sm font-bold text-zinc-900 mb-1">Incorporación de Activos al Cronograma</h2>
            <p class="text-xs text-zinc-500 mb-4">Agrega equipos individuales del inventario o grupos completos por dependencia.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Agregar Activo Individual --}}
                <div class="bg-white rounded-xl border border-zinc-200/90 p-5 shadow-xs text-xs space-y-3">
                    <h3 class="font-bold text-zinc-900 text-xs">Agregar Activo Individual</h3>

                    <form method="POST" action="{{ route('campaigns.assets.add', $campaign) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Activo</label>
                            <select name="asset_id" required
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                <option value="">Seleccionar activo...</option>
                                @foreach ($availableAssets as $avAsset)
                                    <option value="{{ $avAsset->id }}">{{ $avAsset->name }} ({{ $avAsset->internal_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Técnico Asignado</label>
                            <select name="assigned_technician_id"
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                <option value="">Sin técnico asignado</option>
                                @foreach ($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit"
                                class="w-full py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-xs cursor-pointer">
                            Agregar Activo
                        </button>
                    </form>
                </div>

                {{-- Cargar por Oficina Completa --}}
                <div class="bg-white rounded-xl border border-zinc-200/90 p-5 shadow-xs text-xs space-y-3">
                    <h3 class="font-bold text-zinc-900 text-xs">Cargar por Dependencia Completa</h3>

                    <form method="POST" action="{{ route('campaigns.assets.bulk-unit', $campaign) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Oficina / Unidad</label>
                            <select name="unit_id" required
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                <option value="">Seleccionar oficina...</option>
                                @foreach ($organizationalUnits as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block font-medium text-zinc-700 mb-1">Técnico Asignado</label>
                            <select name="assigned_technician_id"
                                    class="w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-xs bg-white focus:outline-none focus:ring-1 focus:ring-zinc-900">
                                <option value="">Sin técnico asignado</option>
                                @foreach ($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit"
                                class="w-full py-1.5 rounded-lg bg-zinc-900 text-white font-medium hover:bg-zinc-800 transition-colors shadow-xs cursor-pointer">
                            Cargar Todos los Activos de la Oficina
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
        @endcan
    </div>

</x-layouts.app>
