<x-layouts.app title="Nuevo Ticket de Soporte" moduleLabel="Mesa de Ayuda" pageTitle="Nuevo Ticket">

    <x-ui.page-header
        title="Nuevo Ticket de Soporte"
        description="Registra una solicitud de asistencia técnica o reporte de incidencia en dependencias municipales."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm" href="{{ route('maintenance-cases.index') }}">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Volver
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <form method="POST" action="{{ route('maintenance-cases.store') }}" class="max-w-2xl"
          x-data="templatePicker('{{ route('maintenance-templates.data', ['maintenanceTemplate' => '__ID__']) }}')">
        @csrf

        <div class="space-y-4">

            {{-- Selector de plantilla --}}
            @if ($templates->isNotEmpty())
            <div class="rounded-xl border border-sigat-200 bg-sigat-50 p-4 flex flex-col sm:flex-row sm:items-center gap-3">
                <svg class="w-5 h-5 text-sigat-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-sigat-800">Cargar desde plantilla</p>
                    <p class="text-xs text-sigat-600">Selecciona una plantilla para pre-rellenar los campos automáticamente.</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <select x-model="selectedId" @change="loadTemplate()"
                            class="rounded-lg border border-sigat-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-sigat-500 focus:ring-2 focus:ring-sigat-500/20 focus:outline-none min-w-[180px]">
                        <option value="">Seleccionar plantilla…</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    </select>
                    <span x-show="loading" class="text-xs text-sigat-600">Cargando…</span>
                </div>
            </div>
            @endif

            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-slate-900">Datos del caso</h3>
                </x-slot:header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <x-ui.select
                            name="asset_id"
                            label="Activo"
                            :value="old('asset_id', request('asset_id'))"
                            :options="$assets->pluck('name', 'id')->toArray()"
                            placeholder="Seleccionar activo..."
                        />
                    </div>
                    <x-ui.select
                        name="maintenance_type"
                        label="Tipo de mantenimiento"
                        :value="old('maintenance_type')"
                        :options="collect($types)->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray()"
                        placeholder="Seleccionar tipo..."
                    />
                    <x-ui.select
                        name="priority"
                        label="Prioridad"
                        :value="old('priority', 'media')"
                        :options="collect($priorities)->mapWithKeys(fn($p) => [$p->value => $p->label()])->toArray()"
                    />
                    <x-ui.select
                        name="assigned_technician_id"
                        label="Técnico asignado"
                        :value="old('assigned_technician_id')"
                        :options="$technicians->pluck('full_name', 'id')->toArray()"
                        placeholder="Sin asignar"
                    />
                    <x-ui.select
                        name="reported_by_employee_id"
                        label="Reportado por"
                        :value="old('reported_by_employee_id')"
                        :options="$employees->pluck('full_name', 'id')->toArray()"
                        placeholder="Seleccionar empleado..."
                    />
                </div>
            </x-ui.card>

            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-slate-900">Descripción</h3>
                </x-slot:header>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción del problema</label>
                        <textarea name="problem_description" rows="3" x-model="fields.problem_description"
                                  placeholder="Describe el problema o motivo del mantenimiento..."
                                  class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-sigat-500 focus:ring-2 focus:ring-sigat-500/20 focus:outline-none resize-none">{{ old('problem_description') }}</textarea>
                    </div>
                </div>
            </x-ui.card>

            <div class="flex gap-3">
                <x-ui.button type="submit">Crear ticket de soporte</x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('maintenance-cases.index') }}">Cancelar</x-ui.button>
            </div>
        </div>
    </form>

    <script>
    function templatePicker(dataUrlPattern) {
        return {
            selectedId: '',
            loading: false,
            fields: {
                problem_description: '{{ old('problem_description') }}',
                diagnosis: '',
                actions_taken: '',
                next_maintenance_interval_days: null,
            },
            async loadTemplate() {
                if (!this.selectedId) return;
                this.loading = true;
                try {
                    const url = dataUrlPattern.replace('__ID__', this.selectedId);
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const tpl = await res.json();
                    if (tpl.problem_description) this.fields.problem_description = tpl.problem_description;
                    if (tpl.diagnosis_template)  this.fields.diagnosis            = tpl.diagnosis_template;
                    if (tpl.actions_template)    this.fields.actions_taken        = tpl.actions_template;
                } finally {
                    this.loading = false;
                }
            },
        };
    }
    </script>

</x-layouts.app>
