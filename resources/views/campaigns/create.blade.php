<x-layouts.app title="Nueva Campaña" moduleLabel="Mantenimiento" pageTitle="Nueva Campaña">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-zinc-200/80 mb-6 max-w-3xl">
        <div>
            <nav class="flex items-center gap-1.5 text-xs text-zinc-400 mb-1.5">
                <a href="{{ route('campaigns.index') }}" class="hover:text-zinc-700 transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span>Campañas</span>
                </a>
                <span>/</span>
                <span class="text-zinc-600 font-medium">Nueva</span>
            </nav>
            <h1 class="text-2xl font-bold tracking-tight text-zinc-900">Planificar Campaña</h1>
            <p class="text-xs text-zinc-500 mt-1">Configura un nuevo cronograma institucional de mantenimiento preventivo.</p>
        </div>

        <a href="{{ route('campaigns.index') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 transition-colors">
            Cancelar
        </a>
    </div>

    {{-- Formulario Ultraminimalista --}}
    <form method="POST" action="{{ route('campaigns.store') }}" class="max-w-3xl space-y-6">
        @csrf

        <div class="bg-white rounded-2xl border border-zinc-200/90 p-6 shadow-xs space-y-5 text-xs">
            {{-- Nombre --}}
            <div>
                <label class="block font-semibold text-zinc-800 mb-1.5">Nombre de la Campaña *</label>
                <input type="text" name="name" required value="{{ old('name') }}" placeholder="Ej: Mantenimiento Preventivo y Correctivo N° 1 - 2026"
                       class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-medium">
                @error('name') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Objetivo --}}
            <div>
                <label class="block font-semibold text-zinc-800 mb-1.5">Objetivo Institucional</label>
                <textarea name="objective" rows="3" placeholder="Describe el alcance y propósito de la campaña (ej. atención preventiva integral de equipos de cómputo y periféricos en el palacio municipal)..."
                          class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">{{ old('objective') }}</textarea>
                @error('objective') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Coordinador y Estado --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Coordinador Responsable</label>
                    <select name="coordinator_id"
                            class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        <option value="">Seleccionar coordinador...</option>
                        @foreach ($coordinators as $coord)
                            <option value="{{ $coord->id }}" {{ old('coordinator_id') == $coord->id ? 'selected' : '' }}>
                                {{ $coord->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('coordinator_id') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Estado Inicial</label>
                    <select name="status"
                            class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" {{ old('status', 'planificada') === $status->value ? 'selected' : '' }}>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Fechas --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Fecha de Inicio</label>
                    <input type="date" name="start_date" value="{{ old('start_date', now()->toDateString()) }}"
                           class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono">
                    @error('start_date') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-zinc-800 mb-1.5">Fecha de Fin Estimada</label>
                    <input type="date" name="end_date" value="{{ old('end_date', now()->addMonths(3)->toDateString()) }}"
                           class="w-full rounded-lg border border-zinc-200 px-3.5 py-2 text-xs bg-white text-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900 font-mono">
                    @error('end_date') <span class="text-rose-600 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-5 py-2 rounded-lg bg-zinc-900 text-white text-xs font-medium hover:bg-zinc-800 transition-colors shadow-sm cursor-pointer">
                Crear Campaña
            </button>
            <a href="{{ route('campaigns.index') }}"
               class="px-4 py-2 rounded-lg border border-zinc-200 text-zinc-600 hover:bg-zinc-50 text-xs font-medium transition-colors">
                Cancelar
            </a>
        </div>
    </form>

</x-layouts.app>
