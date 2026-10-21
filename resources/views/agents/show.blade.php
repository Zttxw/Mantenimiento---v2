<x-layouts.app title="Detalle Agente" moduleLabel="Agentes" pageTitle="Detalle de Agente">

    @php
        $agentTone = match($agentDevice->status->value) {
            'activo' => 'success', 'inactivo' => 'neutral', 'desconectado' => 'danger', default => 'neutral',
        };

        $snap = $agentDevice->last_snapshot_json ?? [];
        $health = $agentDevice->last_health_json ?? [];

        $deviceData = data_get($snap, 'Device', data_get($snap, 'device', []));
        $software = data_get($snap, 'InstalledSoftware', data_get($snap, 'installedSoftware', [])) ?? [];
        $binding = data_get($snap, 'AdministrativeBinding', data_get($snap, 'administrativeBinding', []));

        $hostname = data_get($deviceData, 'Hostname', data_get($deviceData, 'hostname'));
        $operatingSystem = data_get($deviceData, 'OperatingSystem', data_get($deviceData, 'operatingSystem'));
        $osVersion = data_get($deviceData, 'OsVersion', data_get($deviceData, 'osVersion'));
        $osArchitecture = data_get($deviceData, 'OsArchitecture', data_get($deviceData, 'osArchitecture'));
        $processorName = data_get($deviceData, 'ProcessorName', data_get($deviceData, 'processorName'));
        $totalMemoryGb = data_get($deviceData, 'TotalMemoryGb', data_get($deviceData, 'totalMemoryGb'));
        $manufacturer = data_get($deviceData, 'Manufacturer', data_get($deviceData, 'manufacturer'));
        $model = data_get($deviceData, 'Model', data_get($deviceData, 'model'));
        $serialNumber = data_get($deviceData, 'SerialNumber', data_get($deviceData, 'serialNumber'));
        $biosVersion = data_get($deviceData, 'BiosVersion', data_get($deviceData, 'biosVersion'));
        $domain = data_get($deviceData, 'Domain', data_get($deviceData, 'domain'));
        $userName = data_get($deviceData, 'UserName', data_get($deviceData, 'userName'));
        $collectedAtUtc = data_get($snap, 'CollectedAtUtc', data_get($snap, 'collectedAtUtc'));
        $lastBootTimeUtc = data_get($deviceData, 'LastBootTimeUtc', data_get($deviceData, 'lastBootTimeUtc'));
        $ipAddresses = data_get($deviceData, 'IpAddresses', data_get($deviceData, 'ipAddresses', [])) ?? [];
        $storageVolumes = data_get($deviceData, 'StorageVolumes', data_get($deviceData, 'storageVolumes', [])) ?? [];
        $totalStorageGb = data_get($deviceData, 'TotalStorageGb', data_get($deviceData, 'totalStorageGb'));
        $freeStorageGb = data_get($deviceData, 'FreeStorageGb', data_get($deviceData, 'freeStorageGb'));
        $motherboardManufacturer = data_get($deviceData, 'MotherboardManufacturer', data_get($deviceData, 'motherboardManufacturer'));
        $motherboardProduct = data_get($deviceData, 'MotherboardProduct', data_get($deviceData, 'motherboardProduct'));
        $motherboardSerialNumber = data_get($deviceData, 'MotherboardSerialNumber', data_get($deviceData, 'motherboardSerialNumber'));

        $softwareList = collect($software)
            ->map(fn ($item) => data_get($item, 'Name', data_get($item, 'name')))
            ->filter()->sort()->values();

        $healthTone = match(data_get($health, 'overallStatus')) {
            'critical' => 'danger',
            'warning'  => 'warning',
            'ok'       => 'success',
            default    => 'neutral',
        };

        // Heartbeat stats para el monitor
        $hbCount24h = count($heartbeats);
        $expectedBeats = 24 * 12; // cada 5 min = 12 por hora
        $uptimePct = $expectedBeats > 0 ? round(($hbCount24h / $expectedBeats) * 100) : 0;
        $uptimePct = min($uptimePct, 100);

        // Preparo los slots de las últimas 24h (1 slot = 5 min)
        $nowTs      = now()->timestamp;
        $windowSecs = 24 * 3600;
        $slotSecs   = 300; // 5 minutos
        $totalSlots = (int) ($windowSecs / $slotSecs); // 288
        $startTs    = $nowTs - $windowSecs;

        // Marcar qué slots tienen heartbeat
        $beatenSlots = [];
        foreach ($heartbeats as $hb) {
            $slotIdx = (int) floor(($hb['ts'] - $startTs) / $slotSecs);
            if ($slotIdx >= 0 && $slotIdx < $totalSlots) {
                $beatenSlots[$slotIdx] = true;
            }
        }
    @endphp

    <x-ui.page-header
        :title="$agentDevice->hostname ?? $agentDevice->uuid"
        :description="$agentDevice->asset?->name ?? 'Sin activo vinculado'"
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm" href="{{ route('agents.index') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Volver
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- ══════════════════════════════════════════════════════ MONITOR VITAL --}}
    <div class="bg-slate-950 rounded-2xl border border-slate-800 mb-5 overflow-hidden shadow-xl transition-all duration-700"
         :class="flashNew ? 'ring-1 ring-emerald-500/40 shadow-emerald-900/40 shadow-2xl' : ''"
         x-data="{
            heartbeatData: {{ json_encode($heartbeats) }},
            lastHeartbeatAt: {{ $agentDevice->last_heartbeat_at?->timestamp ?? 'null' }},
            isOnline: {{ $agentDevice->isOnline() ? 'true' : 'false' }},
            nowTs: Math.floor(Date.now() / 1000),
            flashNew: false,
            startTs: {{ $startTs }},
            slotSecs: 300,
            totalSlots: 288,
            statusUrl: '{{ route('agents.status', $agentDevice) }}',

            init() {
                setInterval(() => { this.nowTs = Math.floor(Date.now() / 1000); }, 1000);
                setInterval(() => { this.fetchStatus(); }, 30000);
                this.$nextTick(() => this.renderEcg());
            },

            get heartbeatTs() { return this.heartbeatData.map(h => h.ts); },
            get hbCount24h()  { return this.heartbeatData.length; },
            get uptimePct()   { return Math.min(Math.round((this.hbCount24h / 288) * 100), 100); },
            get uptimeColor() {
                return this.uptimePct >= 90 ? 'text-emerald-400' : (this.uptimePct >= 70 ? 'text-amber-400' : 'text-rose-400');
            },
            get lastBeatAgo() {
                if (!this.lastHeartbeatAt) return null;
                return this.nowTs - this.lastHeartbeatAt;
            },
            get lastBeatLabel() {
                const s = this.lastBeatAgo;
                if (s === null) return 'Nunca';
                if (s < 60)    return s + 's';
                if (s < 3600)  return Math.floor(s / 60) + 'm ' + (s % 60) + 's';
                return Math.floor(s / 3600) + 'h ' + Math.floor((s % 3600) / 60) + 'm';
            },
            get lastBeatColor() {
                const s = this.lastBeatAgo;
                if (s === null) return 'text-slate-500';
                return s < 360 ? 'text-emerald-400' : (s < 660 ? 'text-amber-400' : 'text-rose-400');
            },
            get beatenSlotsArray() {
                const arr = new Array(this.totalSlots).fill(false);
                const start = this.startTs, slot = this.slotSecs;
                for (const ts of this.heartbeatTs) {
                    const idx = Math.floor((ts - start) / slot);
                    if (idx >= 0 && idx < this.totalSlots) arr[idx] = true;
                }
                return arr;
            },
            get recentHeartbeats() {
                return [...this.heartbeatData].sort((a, b) => b.ts - a.ts).slice(0, 6);
            },

            formatTs(ts) {
                return new Date(ts * 1000).toLocaleTimeString('es-PE', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
                });
            },

            async fetchStatus() {
                try {
                    const res = await fetch(this.statusUrl, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) return;
                    const data = await res.json();
                    const prevTs = this.lastHeartbeatAt;
                    this.isOnline = data.is_online;
                    this.lastHeartbeatAt = data.last_heartbeat_at;
                    if (data.last_heartbeat_at && data.last_heartbeat_at !== prevTs) {
                        this.heartbeatData = data.heartbeats;
                        this.startTs = data.server_ts - 24 * 3600;
                        this.flashNew = true;
                        setTimeout(() => { this.flashNew = false; }, 3000);
                        this.$nextTick(() => this.renderEcg());
                    }
                } catch(e) {}
            },

            renderEcg() {
                const pathEl = document.getElementById('ecg-path');
                if (!pathEl) return;
                const svgW = 1440, base = 60, spikeH = 45;
                const slotW = svgW / this.totalSlots;
                const arr = this.beatenSlotsArray;
                let path = '';
                for (let i = 0; i < this.totalSlots; i++) {
                    const cx = i * slotW;
                    if (arr[i]) {
                        const mid = cx + slotW * 0.5;
                        path += path ? ` L ${cx},${base}` : `M ${cx},${base}`;
                        path += ` L ${mid-slotW*.15},${base} L ${mid},${base-spikeH} L ${mid+slotW*.15},${base} L ${cx+slotW},${base}`;
                    } else {
                        path += path ? ` L ${cx+slotW},${base}` : `M ${cx},${base} L ${cx+slotW},${base}`;
                    }
                }
                pathEl.setAttribute('d', path);
                const dotEl = document.getElementById('ecg-dot');
                if (dotEl && this.heartbeatTs.length) {
                    const lastTs   = Math.max(...this.heartbeatTs);
                    const lastSlot = Math.floor((lastTs - this.startTs) / this.slotSecs);
                    if (lastSlot >= 0 && lastSlot < this.totalSlots) {
                        dotEl.setAttribute('cx', (lastSlot + 0.5) * slotW);
                        dotEl.setAttribute('cy', base - spikeH);
                    }
                }
            }
         }">

        {{-- Header del monitor --}}
        <div class="flex items-center justify-between px-5 pt-4 pb-3 border-b border-slate-800">
            <div class="flex items-center gap-3">
                {{-- Indicador de pulso animado — dinámico --}}
                <div class="relative w-3 h-3">
                    <template x-if="isOnline">
                        <span class="flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    </template>
                    <template x-if="!isOnline">
                        <span class="inline-flex rounded-full h-3 w-3 bg-slate-600"></span>
                    </template>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-semibold text-slate-200 tracking-widest uppercase">Monitor de signos vitales</p>
                        {{-- Badge "nuevo latido" que aparece en flash --}}
                        <span x-show="flashNew" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                              class="inline-flex items-center gap-1 bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs px-2 py-0.5 rounded-full font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Nuevo latido
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">Heartbeat · cada 5 min · últimas 24 h · polling cada 30 s</p>
                </div>
            </div>
            <div class="flex items-center gap-6 text-right">
                <div>
                    <p class="text-2xl font-bold font-mono text-emerald-400" x-text="hbCount24h"></p>
                    <p class="text-xs text-slate-500">latidos / 24h</p>
                </div>
                <div>
                    <p class="text-2xl font-bold font-mono" :class="uptimeColor" x-text="uptimePct + '%'"></p>
                    <p class="text-xs text-slate-500">disponibilidad</p>
                </div>
                <div>
                    {{-- Contador que se actualiza cada segundo --}}
                    <p class="text-xl font-bold font-mono tabular-nums" :class="lastBeatColor" x-text="lastBeatLabel"></p>
                    <p class="text-xs text-slate-500">último latido</p>
                </div>
            </div>
        </div>

        {{-- ECG Waveform con SVG --}}
        <div class="px-5 py-4">
            <div class="relative">
                <div class="flex justify-between text-xs text-slate-600 mb-1.5 px-0.5">
                    <span>-24h</span><span>-18h</span><span>-12h</span><span>-6h</span><span>Ahora</span>
                </div>

                <svg id="ecg-wave" viewBox="0 0 1440 80" class="w-full h-16" preserveAspectRatio="none">
                    <line x1="0" y1="60" x2="1440" y2="60" stroke="#1e293b" stroke-width="1"/>
                    <path id="ecg-path" d="" stroke="#10b981" stroke-width="2" fill="none" stroke-linejoin="round"/>
                    <circle id="ecg-dot" cx="1440" cy="15" r="4" fill="#10b981">
                        <animate attributeName="opacity" values="0;1;0" dur="1s" repeatCount="indefinite"/>
                    </circle>
                </svg>

                {{-- Grid de slots — renderizado con x-for para actualizarse en tiempo real --}}
                <div class="mt-3">
                    <p class="text-xs text-slate-600 mb-1.5">288 slots · 5 min c/u · últimas 24 h</p>
                    <div class="flex gap-px" style="flex-wrap: wrap">
                        <template x-for="(beaten, idx) in beatenSlotsArray" :key="idx">
                            <div :class="beaten ? 'bg-emerald-500/80' : 'bg-slate-800'"
                                 class="h-3 rounded-sm transition-colors duration-300"
                                 style="flex: 0 0 calc(0.347% - 1px)"></div>
                        </template>
                    </div>
                    <div class="flex items-center gap-4 mt-2">
                        <span class="flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="w-3 h-2 rounded-sm bg-emerald-500/80 inline-block"></span> Latido recibido
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-slate-500">
                            <span class="w-3 h-2 rounded-sm bg-slate-800 inline-block"></span> Sin señal
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Últimos latidos — dinámicos con x-for --}}
        <div class="border-t border-slate-800 px-5 py-3">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Últimos latidos recibidos</p>
            <div class="flex gap-3 overflow-x-auto pb-1">
                <template x-for="(hb, i) in recentHeartbeats" :key="hb.ts">
                    <div class="shrink-0 rounded-xl px-3 py-2 border min-w-[130px] transition-all duration-500"
                         :class="flashNew && i === 0 ? 'bg-emerald-950/40 border-emerald-500/40' : 'bg-slate-900 border-slate-800'">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" :class="flashNew && i === 0 ? 'animate-ping' : ''"></span>
                            <span class="text-xs font-mono text-emerald-400" x-text="formatTs(hb.ts)"></span>
                        </div>
                        <p class="text-xs font-mono text-slate-500" x-show="hb.ip" x-text="hb.ip"></p>
                        <p class="text-xs text-slate-600" x-show="hb.version" x-text="'v' + hb.version"></p>
                    </div>
                </template>
                <div x-show="recentHeartbeats.length === 0" class="text-xs text-slate-600 py-3 italic">
                    Sin latidos registrados en las últimas 24 h.
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

        <div class="xl:col-span-1 space-y-4">

            {{-- Estado del agente --}}
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-slate-900">Estado del agente</h3>
                    <x-ui.badge :tone="$agentTone" :dot="true">{{ $agentDevice->status->label() }}</x-ui.badge>
                </x-slot:header>

                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-xs text-slate-500 mb-0.5">UUID</p>
                        <p class="font-mono text-xs text-slate-700 break-all">{{ $agentDevice->uuid }}</p>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Versión</span>
                        <span class="font-medium text-slate-800">{{ $agentDevice->agent_version ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">IP</span>
                        <span class="font-mono text-slate-700 text-right">{{ $agentDevice->last_ip ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Último heartbeat</span>
                        <span class="{{ $agentDevice->isOnline() ? 'text-emerald-600 font-medium' : 'text-slate-700' }} text-right">
                            {{ $agentDevice->last_heartbeat_at ? $agentDevice->last_heartbeat_at->diffForHumans() : 'Nunca' }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Fecha heartbeat</span>
                        <span class="text-slate-700 text-right">{{ $agentDevice->last_heartbeat_at?->format('d/m/Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Registrado</span>
                        <span class="text-slate-700 text-right">{{ $agentDevice->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </x-ui.card>

            {{-- Salud del equipo --}}
            @if ($health)
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-slate-900">Salud del equipo</h3>
                    <x-ui.badge :tone="$healthTone" :dot="true">{{ strtoupper(data_get($health, 'overallStatus', 'unknown')) }}</x-ui.badge>
                </x-slot:header>

                @php
                    $ramUsedPct = (float) (data_get($health, 'memory.usedPercent') ?? 0);
                    $uptime = data_get($health, 'uptimeHours');
                @endphp

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Generado</span>
                        <span class="text-slate-700 text-right">
                            {{ data_get($health, 'generatedAtUtc') ? \Illuminate\Support\Carbon::parse(data_get($health, 'generatedAtUtc'))->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}
                        </span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-slate-500">Uptime</span>
                        <span class="text-slate-700 text-right">{{ $uptime !== null ? number_format((float) $uptime, 1) . ' h' : '—' }}</span>
                    </div>

                    {{-- RAM bar --}}
                    @if ($ramUsedPct > 0)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-slate-500">RAM usada</span>
                            <span class="font-semibold {{ $ramUsedPct > 85 ? 'text-rose-600' : ($ramUsedPct > 70 ? 'text-amber-600' : 'text-emerald-600') }}">{{ number_format($ramUsedPct, 1) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full {{ $ramUsedPct > 85 ? 'bg-rose-500' : ($ramUsedPct > 70 ? 'bg-amber-400' : 'bg-emerald-500') }}"
                                 style="width: {{ min($ramUsedPct, 100) }}%"></div>
                        </div>
                    </div>
                    @endif

                    @php $warnings = collect(data_get($health, 'warnings', []))->filter()->values(); @endphp
                    @if ($warnings->isNotEmpty())
                    <div class="pt-1 space-y-1.5">
                        @foreach ($warnings as $warning)
                        <div class="flex items-start gap-2 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2">
                            <svg class="w-3.5 h-3.5 text-amber-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L1 21h22L12 2zm0 3.5L20.5 19h-17L12 5.5zm-1 5.5v4h2V11h-2zm0 6v2h2v-2h-2z"/></svg>
                            <p class="text-xs text-amber-700">{{ $warning }}</p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-sm text-emerald-600 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Sin alertas registradas
                    </p>
                    @endif
                </div>
            </x-ui.card>
            @endif

            {{-- Activo vinculado --}}
            @if ($agentDevice->asset)
            <x-ui.card>
                <x-slot:header><h3 class="text-sm font-semibold text-slate-900">Activo vinculado</h3></x-slot:header>
                <div class="space-y-2 text-sm">
                    <p class="font-semibold text-slate-800">{{ $agentDevice->asset->name }}</p>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Código</span>
                        <span class="font-mono text-slate-700">{{ $agentDevice->asset->internal_code }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Tipo</span>
                        <span class="text-slate-700">{{ $agentDevice->asset->asset_type->label() }}</span>
                    </div>
                </div>
                @can('asset.view')
                <x-slot:footer>
                    <x-ui.button variant="ghost" size="xs" href="{{ route('assets.show', $agentDevice->asset) }}">Ver activo →</x-ui.button>
                </x-slot:footer>
                @endcan
            </x-ui.card>
            @endif
        </div>

        <div class="xl:col-span-2 space-y-4">

            {{-- Último snapshot --}}
            @if ($agentDevice->last_snapshot_json)
            <x-ui.card>
                <x-slot:header>
                    <h3 class="text-sm font-semibold text-slate-900">Último snapshot</h3>
                    @if ($collectedAtUtc)
                    <span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($collectedAtUtc)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                    @endif
                </x-slot:header>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Hostname</span>
                            <span class="text-slate-700 text-right font-medium">{{ $hostname ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Sistema operativo</span>
                            <span class="text-slate-700 text-right">{{ trim(($operatingSystem ?? '') . ' ' . ($osVersion ?? '')) ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Arquitectura</span>
                            <span class="text-slate-700 text-right">{{ $osArchitecture ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">CPU</span>
                            <span class="text-slate-700 text-right text-xs">{{ $processorName ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">RAM</span>
                            <span class="text-slate-700 text-right">{{ $totalMemoryGb !== null ? $totalMemoryGb . ' GB' : '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Almacenamiento</span>
                            <span class="text-slate-700 text-right">
                                @if ($totalStorageGb !== null)
                                    {{ $freeStorageGb }} GB libre / {{ $totalStorageGb }} GB
                                @else —
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Software</span>
                            <span class="font-semibold text-sigat-600">{{ is_countable($software) ? count($software) : 0 }} programas</span>
                        </div>
                    </div>

                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Fabricante</span>
                            <span class="text-slate-700 text-right">{{ $manufacturer ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Modelo</span>
                            <span class="text-slate-700 text-right">{{ $model ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Serie</span>
                            <span class="text-slate-700 text-right">{{ $serialNumber ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">BIOS</span>
                            <span class="text-slate-700 text-right">{{ $biosVersion ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Placa madre</span>
                            <span class="text-slate-700 text-right">{{ trim(($motherboardManufacturer ?? '') . ' ' . ($motherboardProduct ?? '')) ?: '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Dominio</span>
                            <span class="text-slate-700 text-right">{{ $domain ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Usuario</span>
                            <span class="text-slate-700 text-right">{{ $userName ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Último arranque</span>
                            <span class="text-slate-700 text-right">
                                {{ $lastBootTimeUtc ? \Illuminate\Support\Carbon::parse($lastBootTimeUtc)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- IPs + Volúmenes --}}
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase mb-2">IPs detectadas</p>
                        @if (!empty($ipAddresses))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ipAddresses as $ip)
                            <span class="font-mono text-xs bg-slate-100 px-2.5 py-1 rounded-full text-slate-700">{{ $ip }}</span>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-slate-400">Sin IPs registradas.</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase mb-2">Almacenamiento</p>
                        @if (!empty($storageVolumes))
                        <div class="space-y-2">
                            @foreach ($storageVolumes as $vol)
                            @php
                                $volUsed = data_get($vol, 'UsedPercent', data_get($vol, 'usedPercent', 0));
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="font-medium text-slate-800 text-sm">
                                        {{ data_get($vol, 'Name', data_get($vol, 'name', '—')) }}
                                        @if (data_get($vol, 'Label', data_get($vol, 'label')))
                                        <span class="text-slate-400 font-normal">· {{ data_get($vol, 'Label', data_get($vol, 'label')) }}</span>
                                        @endif
                                    </p>
                                    <span class="text-xs font-semibold {{ $volUsed > 85 ? 'text-rose-600' : ($volUsed > 70 ? 'text-amber-600' : 'text-emerald-600') }}">{{ $volUsed }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mb-1">
                                    <div class="h-full rounded-full {{ $volUsed > 85 ? 'bg-rose-500' : ($volUsed > 70 ? 'bg-amber-400' : 'bg-emerald-500') }}"
                                         style="width: {{ min($volUsed, 100) }}%"></div>
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ data_get($vol, 'FileSystem', data_get($vol, 'fileSystem', '—')) }}
                                    · Libre {{ data_get($vol, 'FreeGb', data_get($vol, 'freeGb', '—')) }} GB
                                    / {{ data_get($vol, 'TotalGb', data_get($vol, 'totalGb', '—')) }} GB
                                </p>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-sm text-slate-400">Sin volúmenes.</p>
                        @endif
                    </div>
                </div>

                {{-- Binding administrativo --}}
                @if ($binding)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase mb-2">Binding administrativo</p>
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <div class="bg-slate-50 rounded-xl px-3 py-2">
                            <p class="text-xs text-slate-400">Código activo</p>
                            <p class="font-mono font-medium text-slate-700">{{ data_get($binding, 'AssetCode', data_get($binding, 'assetCode')) ?? '—' }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl px-3 py-2">
                            <p class="text-xs text-slate-400">Unidad ID</p>
                            <p class="font-mono font-medium text-slate-700">{{ data_get($binding, 'OrganizationalUnitId', data_get($binding, 'organizationalUnitId')) ?? '—' }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl px-3 py-2">
                            <p class="text-xs text-slate-400">Responsable ID</p>
                            <p class="font-mono font-medium text-slate-700">{{ data_get($binding, 'ResponsibleEmployeeId', data_get($binding, 'responsibleEmployeeId')) ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Software instalado completo --}}
                @if ($softwareList->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-slate-100" x-data="{ swQ: '' }">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold tracking-wide text-slate-400 uppercase">Software instalado ({{ $softwareList->count() }})</p>
                        <input type="text" x-model="swQ" placeholder="Buscar..."
                               class="text-xs rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 focus:outline-none focus:border-sigat-400 w-40">
                    </div>
                    <div class="max-h-64 overflow-y-auto grid grid-cols-2 gap-x-4 gap-y-0.5 pr-1">
                        @foreach ($softwareList as $sw)
                        <div x-show="!swQ || '{{ addslashes(strtolower($sw)) }}'.includes(swQ.toLowerCase())"
                             class="flex items-center gap-1.5 py-1 px-1.5 rounded-lg hover:bg-slate-50 group">
                            <span class="w-1.5 h-1.5 rounded-full bg-sigat-300 shrink-0"></span>
                            <span class="text-xs text-slate-600 group-hover:text-slate-900 truncate" title="{{ $sw }}">{{ $sw }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </x-ui.card>
            @endif

            {{-- Historial de sincronizaciones --}}
            <x-ui.data-table>
                <x-slot:toolbar>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Últimas sincronizaciones</h3>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $totalSyncsCount ?? $agentDevice->syncs()->count() }} total · mostrando 5</p>
                    </div>
                </x-slot:toolbar>

                <x-slot:head>
                    <x-ui.th>Tipo</x-ui.th>
                    <x-ui.th>Estado</x-ui.th>
                    <x-ui.th>IP</x-ui.th>
                    <x-ui.th>Versión</x-ui.th>
                    <x-ui.th>Fecha</x-ui.th>
                </x-slot:head>

                @forelse (($recentSyncs ?? $agentDevice->syncs()->latest('synced_at')->take(5)->get()) as $sync)
                @php
                    $syncStatus  = $sync->status?->value ?? (string) $sync->status;
                    $syncType    = $sync->sync_type?->value ?? (string) $sync->sync_type;
                    $syncTone    = match($syncStatus) {
                        'procesado' => 'success', 'recibido' => 'neutral', 'error' => 'danger', default => 'neutral',
                    };
                    $syncPayload = $sync->payload_json ?? [];
                    $syncIp      = data_get($syncPayload, 'last_ip', data_get($syncPayload, 'lastIp'));
                    $syncVersion = data_get($syncPayload, 'agent_version', data_get($syncPayload, 'agentVersion'));
                @endphp
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <x-ui.td>
                        <x-ui.badge tone="{{ $syncType === 'heartbeat' ? 'neutral' : ($syncType === 'snapshot' ? 'info' : 'primary') }}" size="sm">
                            {{ $sync->sync_type->label() }}
                        </x-ui.badge>
                    </x-ui.td>
                    <x-ui.td><x-ui.badge :tone="$syncTone" size="sm">{{ $sync->status->label() }}</x-ui.badge></x-ui.td>
                    <x-ui.td><span class="font-mono text-sm text-slate-600">{{ $syncIp ?? '—' }}</span></x-ui.td>
                    <x-ui.td><span class="text-sm text-slate-600">{{ $syncVersion ?? '—' }}</span></x-ui.td>
                    <x-ui.td><span class="text-sm text-slate-500">{{ $sync->created_at->format('d/m/Y H:i') }}</span></x-ui.td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-slate-400 text-sm">Sin sincronizaciones registradas.</td>
                </tr>
                @endforelse
            </x-ui.data-table>
        </div>
    </div>

</x-layouts.app>
