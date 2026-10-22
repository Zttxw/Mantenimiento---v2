<x-layouts.app title="Organigrama Institucional" moduleLabel="Organización" pageTitle="Estructura y Organigrama Municipal">

<div x-data="officialOrganigramViewer({{ json_encode($officialOrganigram, JSON_HEX_APOS | JSON_HEX_QUOT) }})"
     x-init="init()"
     class="flex flex-col h-[calc(100vh-95px)] overflow-hidden select-none">

    {{-- ========================================================================= --}}
    {{-- BARRA SUPERIOR: TÍTULO, MÉTRICAS Y SELECTOR DE VISTA                      --}}
    {{-- ========================================================================= --}}
    <div class="flex items-center justify-between gap-3 pb-2.5 mb-2 border-b border-zinc-200/80 shrink-0">
        {{-- Título y Métricas Rápidas --}}
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 ring-4 ring-blue-100"></span>
                <h1 class="text-lg font-bold tracking-tight text-zinc-900">Oficinas y Dependencias</h1>
            </div>
            <div class="flex items-center gap-2 text-xs font-mono text-zinc-500">
                <span class="px-2 py-0.5 rounded-md bg-zinc-100 text-zinc-700 border border-zinc-200 font-sans font-semibold">
                    Ordenanza N° 015-2023-MDSJ/C
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-zinc-600">
                    <strong class="text-zinc-900 font-mono">{{ $stats['total'] ?? 0 }}</strong> dependencias
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-emerald-600 font-medium">
                    <strong class="font-mono">{{ $stats['active'] ?? 0 }}</strong> activas
                </span>
                <span class="hidden sm:inline text-zinc-300">&bull;</span>
                <span class="hidden sm:inline text-blue-700 font-medium">
                    <strong class="font-mono">{{ $stats['with_assets'] ?? 0 }}</strong> con activos OTI
                </span>
            </div>
        </div>

        {{-- Switch de Vistas (Organigrama Oficial vs Directorio Tabla) y Acciones --}}
        <div class="flex items-center gap-2 shrink-0">
            <div class="inline-flex items-center p-0.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs">
                <button type="button"
                        @click="viewMode = 'organigram'"
                        :class="viewMode === 'organigram' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <span>🏛️</span>
                    <span>Árbol (Organigrama)</span>
                </button>
                <button type="button"
                        @click="viewMode = 'table'"
                        :class="viewMode === 'table' ? 'bg-white text-zinc-900 font-semibold shadow-2xs' : 'text-zinc-600 hover:text-zinc-900'"
                        class="flex items-center gap-1.5 px-3 py-1 rounded-md transition-all">
                    <span>📋</span>
                    <span>Tabla (Directorio)</span>
                </button>
            </div>

            <button type="button"
                    @click="printOrganigram()"
                    class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-white text-zinc-700 border border-zinc-200 hover:bg-zinc-50 transition-colors shadow-2xs"
                    title="Imprimir u organizar en PDF">
                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Imprimir</span>
            </button>

            @can('org-unit.create')
            <a href="{{ route('organizational-units.create') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Oficina</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 1: ORGANIGRAMA OFICIAL FIEL A LA ORDENANZA N° 015-2023-MDSJ/C        --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'organigram'"
         x-ref="canvasContainer"
         class="flex-1 relative bg-slate-50/70 rounded-2xl border border-zinc-200/90 overflow-hidden flex flex-col">

        {{-- Barra de Controles Superior Flotante (Búsqueda + Zoom + Pantalla Completa) --}}
        <div class="absolute top-3 left-3 right-3 z-30 flex items-center justify-between gap-3 pointer-events-none">
            {{-- Buscador en Vivo de Oficinas con Resaltado Beacon --}}
            <div class="pointer-events-auto relative w-72 sm:w-80 shadow-sm">
                <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text"
                       x-model="searchQuery"
                       @input="onSearch()"
                       placeholder="Buscar oficina, subgerencia o responsable..."
                       class="w-full pl-9 pr-7 py-1.5 bg-white/95 backdrop-blur-md rounded-xl border border-zinc-200 text-xs text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-600 shadow-xs">
                <button type="button"
                        x-show="searchQuery.length > 0"
                        @click="searchQuery = ''; onSearch()"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 text-xs">
                    ✕
                </button>
            </div>

            {{-- Controles de Zoom y Ajuste a Pantalla --}}
            <div class="pointer-events-auto flex items-center gap-1.5 bg-white/95 backdrop-blur-md p-1 rounded-xl border border-zinc-200 shadow-xs text-xs">
                <button type="button"
                        @click="fitToScreen()"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 font-semibold text-[11px] transition-colors"
                        title="Ajustar todo el organigrama a una sola pantalla">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    <span>Una Pantalla</span>
                </button>

                <div class="h-4 w-px bg-zinc-200 mx-0.5"></div>

                <button type="button" @click="zoomIn()" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-zinc-100 text-zinc-700 font-bold" title="Acercar (+)">
                    +
                </button>
                <button type="button" @click="zoomOut()" class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-zinc-100 text-zinc-700 font-bold" title="Alejar (-)">
                    &minus;
                </button>
                <button type="button" @click="reset100()" class="px-2 py-1 rounded-lg hover:bg-zinc-100 text-zinc-700 font-medium text-[11px]" title="Vista 100%">
                    100%
                </button>
            </div>
        </div>

        {{-- Leyenda de Colores Institucionales Oficiales --}}
        <div class="absolute bottom-3 left-3 z-30 pointer-events-none bg-white/95 backdrop-blur-md px-3 py-1.5 rounded-xl border border-zinc-200 shadow-xs text-[10px] text-zinc-600 hidden md:flex items-center gap-3">
            <span class="font-bold text-zinc-800 uppercase tracking-wider">Órganos:</span>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#1e40af]"></span> Gobierno / Dirección</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#166534]"></span> Apoyo</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#c2410c]"></span> Asesoramiento</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#0284c7]"></span> Línea</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#334155]"></span> Control / Defensa</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-xs bg-[#fde68a] border border-[#f59e0b]"></span> Coordinación</div>
        </div>

        {{-- LIENZO INTERACTIVO CON PAN (ARRASTRE) Y ZOOM --}}
        <div class="flex-1 w-full h-full relative cursor-grab active:cursor-grabbing overflow-hidden"
             @mousedown="startDrag($event)"
             @mousemove="onDrag($event)"
             @mouseup="stopDrag()"
             @mouseleave="stopDrag()"
             @wheel.prevent="onWheel($event)">

            {{-- Cuadrícula de Fondo Tecnológica Discreta --}}
            <svg class="absolute inset-0 w-full h-full pointer-events-none opacity-20">
                <defs>
                    <pattern id="org-grid-pattern" width="32" height="32" patternUnits="userSpaceOnUse">
                        <circle cx="2" cy="2" r="1" fill="#64748b"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#org-grid-pattern)"/>
            </svg>

            {{-- CONTENEDOR TRANSFORMABLE CON ESCALA Y PAN --}}
            <div class="absolute origin-top-left transition-transform duration-75 p-8"
                 :style="`transform: translate(${panX}px, ${panY}px) scale(${zoom});`">

                {{-- HOJA OFICIAL DEL ORGANIGRAMA --}}
                <div id="official-organigram-sheet"
                     x-ref="chartSheet"
                     class="bg-white border-2 border-zinc-900 rounded-xl p-6 sm:p-8 shadow-md relative w-[1380px] min-h-[960px] text-zinc-900 font-sans">

                    {{-- ENCABEZADO OFICIAL DE LA ORDENANZA --}}
                    <div class="text-center pb-5 mb-5 border-b-2 border-zinc-900/90 relative">
                        <h2 class="text-xl sm:text-2xl font-black uppercase tracking-wider text-zinc-900">
                            ORGANIGRAMA DE LA MUNICIPALIDAD DISTRITAL DE SAN JERONIMO
                        </h2>
                        <p class="text-sm font-bold text-zinc-800 mt-1">
                            Aprobado mediante Ordenanza Municipal N° 015-2023-MDSJ/C
                        </p>
                    </div>

                    {{-- ============================================================= --}}
                    {{-- 1. BLOQUE SUPERIOR: CONCEJO MUNICIPAL, ALCALDÍA Y GERENCIA    --}}
                    {{-- ============================================================= --}}
                    <div class="relative w-full flex flex-col items-center">

                        {{-- FILA 1: ÓRGANOS DE CONTROL (IZQ), CONCEJO (CENTRO), COORDINACIÓN (DER) --}}
                        <div class="w-full grid grid-cols-12 items-center gap-4 relative">

                            {{-- Lateral Izquierdo: Control Institucional & Procuraduría --}}
                            <div class="col-span-4 flex flex-col items-end space-y-2 pr-6 relative">
                                <template x-for="item in organigram.top.control_left" :key="item.name">
                                    <div class="w-60 p-2 rounded bg-slate-800 border-2 border-slate-900 text-white shadow-xs cursor-pointer hover:ring-2 hover:ring-slate-400 transition-all text-center relative"
                                         :class="isMatched(item) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                         @click="openUnitInspector(item.id)">
                                        <p class="text-[11px] font-bold uppercase tracking-tight leading-tight" x-text="item.name"></p>
                                        <div class="flex items-center justify-center gap-2 mt-1 text-[9px] text-slate-300 font-mono">
                                            <span x-text="'👥 ' + item.employees_count"></span>
                                            <span x-text="'💻 ' + item.assets_count"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Centro Superior: CONCEJO MUNICIPAL --}}
                            <div class="col-span-4 flex flex-col items-center justify-center relative">
                                <div class="w-64 p-3 rounded-md bg-[#1e40af] border-2 border-blue-950 text-white shadow-md text-center cursor-pointer hover:bg-blue-800 transition-colors relative"
                                     :class="isMatched(organigram.top.concejo) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.top.concejo.id, 'Concejo Municipal')">
                                    <h3 class="text-sm font-black uppercase tracking-wider leading-snug">
                                        CONCEJO MUNICIPAL
                                    </h3>
                                    <span class="text-[9px] uppercase tracking-widest text-blue-200 font-semibold block mt-0.5">
                                        Órgano de Gobierno y Normativo
                                    </span>
                                </div>
                            </div>

                            {{-- Lateral Derecho: Consejo de Coordinación Local Distrital & Junta de Delegados --}}
                            <div class="col-span-4 flex flex-col items-start space-y-2 pl-6 relative">
                                <template x-for="item in organigram.top.coordination_right" :key="item.name">
                                    <div class="w-64 p-2 rounded bg-[#ffedd5] border-2 border-[#ea580c]/80 text-zinc-900 shadow-xs cursor-pointer hover:ring-2 hover:ring-amber-500 transition-all text-center relative"
                                         :class="isMatched(item) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                         @click="openUnitInspector(item.id, item.name)">
                                        <p class="text-[11px] font-bold uppercase tracking-tight leading-tight text-amber-950" x-text="item.name"></p>
                                        <p class="text-[9px] text-amber-800 mt-0.5 font-medium" x-text="item.note"></p>
                                    </div>
                                </template>
                            </div>

                            {{-- Líneas Conectoras Punteadas (Dashed) de Control y Coordinación hacia el Concejo --}}
                            <svg class="absolute inset-0 w-full h-full pointer-events-none overflow-visible">
                                {{-- Izquierda a Concejo (Línea punteada) --}}
                                <path d="M 370 28 L 450 28 L 450 45" fill="none" stroke="#0f172a" stroke-width="2" stroke-dasharray="4,4"/>
                                <path d="M 370 65 L 450 65 L 450 45" fill="none" stroke="#0f172a" stroke-width="2" stroke-dasharray="4,4"/>
                                {{-- Derecha a Concejo (Línea punteada) --}}
                                <path d="M 1010 28 L 930 28 L 930 45" fill="none" stroke="#0f172a" stroke-width="2" stroke-dasharray="4,4"/>
                                <path d="M 1010 65 L 930 65 L 930 45" fill="none" stroke="#0f172a" stroke-width="2" stroke-dasharray="4,4"/>
                            </svg>
                        </div>

                        {{-- TALLO VERTICAL DESDE CONCEJO HASTA ALCALDÍA --}}
                        <div class="w-0.5 h-6 bg-zinc-900 my-0.5"></div>

                        {{-- FILA 2: ALCALDÍA Y RAMA LATERAL SECRETARÍA GENERAL --}}
                        <div class="w-full flex items-center justify-center relative">
                            {{-- Secretaría General y Gestión Documentaria (Brazo izquierdo de Alcaldía) --}}
                            <div class="absolute left-60 flex items-center">
                                <div class="w-56 p-2 rounded bg-[#dcfce7] border-2 border-[#166534] text-zinc-900 shadow-xs cursor-pointer hover:ring-2 hover:ring-emerald-500 transition-all text-center relative"
                                     :class="isMatched(organigram.top.secretaria_general) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.top.secretaria_general.id)">
                                    <p class="text-[10.5px] font-bold uppercase tracking-tight text-emerald-950 leading-tight">
                                        OFICINA DE SECRETARÍA GENERAL Y GESTIÓN DOCUMENTARIA
                                    </p>
                                    <div class="flex items-center justify-center gap-2 mt-1 text-[9px] text-emerald-800 font-mono">
                                        <span x-text="'👥 ' + organigram.top.secretaria_general.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.top.secretaria_general.assets_count"></span>
                                    </div>
                                </div>
                                <div class="w-16 h-0.5 bg-zinc-900"></div>
                            </div>

                            {{-- Caja Central: ALCALDÍA --}}
                            <div class="w-64 p-3 rounded-md bg-[#1e40af] border-2 border-blue-950 text-white shadow-md text-center cursor-pointer hover:bg-blue-800 transition-colors z-10"
                                 :class="isMatched(organigram.top.alcaldia) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                 @click="openUnitInspector(organigram.top.alcaldia.id)">
                                <h3 class="text-sm font-black uppercase tracking-wider leading-snug">
                                    ALCALDÍA
                                </h3>
                                <div class="flex items-center justify-center gap-2 mt-1 text-[9px] text-blue-200 font-mono">
                                    <span x-text="'👥 ' + organigram.top.alcaldia.employees_count"></span>
                                    <span x-text="'💻 ' + organigram.top.alcaldia.assets_count"></span>
                                </div>
                            </div>
                        </div>

                        {{-- TALLO VERTICAL DESDE ALCALDÍA HASTA GERENCIA MUNICIPAL --}}
                        <div class="w-0.5 h-6 bg-zinc-900 my-0.5"></div>

                        {{-- FILA 3: GERENCIA MUNICIPAL --}}
                        <div class="w-64 p-3 rounded-md bg-[#1e40af] border-2 border-blue-950 text-white shadow-md text-center cursor-pointer hover:bg-blue-800 transition-colors z-10"
                             :class="isMatched(organigram.top.gerencia_municipal) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                             @click="openUnitInspector(organigram.top.gerencia_municipal.id)">
                            <h3 class="text-sm font-black uppercase tracking-wider leading-snug">
                                GERENCIA MUNICIPAL
                            </h3>
                            <div class="flex items-center justify-center gap-2 mt-1 text-[9px] text-blue-200 font-mono">
                                <span x-text="'👥 ' + organigram.top.gerencia_municipal.employees_count"></span>
                                <span x-text="'💻 ' + organigram.top.gerencia_municipal.assets_count"></span>
                            </div>
                        </div>

                    </div>

                    {{-- ============================================================= --}}
                    {{-- 2. BLOQUE DE OFICINAS DE APOYO DIRECTO (MINT GREEN)           --}}
                    {{-- ============================================================= --}}
                    <div class="relative w-full mt-1 pt-6">
                        {{-- Bus horizontal que distribuye desde Gerencia Municipal a las 3 oficinas directas --}}
                        <svg class="absolute top-0 inset-x-0 w-full h-7 pointer-events-none overflow-visible">
                            {{-- Tallo vertical bajando de Gerencia Municipal (centro a ~690px) --}}
                            <path d="M 690 -5 L 690 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Barra horizontal que conecta las 3 oficinas --}}
                            <path d="M 230 12 L 690 12 L 1150 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Bajadas hacia cada oficina --}}
                            <path d="M 230 12 L 230 26" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 430 12 L 430 26" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 630 12 L 630 26" fill="none" stroke="#0f172a" stroke-width="2"/>
                        </svg>

                        <div class="flex items-start gap-4 pl-16">
                            <template x-for="office in organigram.direct_offices" :key="office.name">
                                <div class="w-48 p-2 rounded bg-[#dcfce7] border-2 border-[#166534] text-zinc-900 shadow-xs cursor-pointer hover:ring-2 hover:ring-emerald-500 transition-all text-center relative"
                                     :class="isMatched(office) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(office.id)">
                                    <p class="text-[10px] font-bold uppercase tracking-tight text-emerald-950 leading-tight" x-text="office.name"></p>
                                    <div class="flex items-center justify-center gap-2 mt-1 text-[9px] text-emerald-800 font-mono">
                                        <span x-text="'👥 ' + office.employees_count"></span>
                                        <span x-text="'💻 ' + office.assets_count"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- ============================================================= --}}
                    {{-- 3. BLOQUE DE ÓRGANOS DE APOYO (VERDE) Y ASESORAMIENTO (ÁMBAR) --}}
                    {{-- ============================================================= --}}
                    <div class="relative w-full mt-4 pt-6">
                        {{-- Bus conector horizontal superior para las Oficinas Generales --}}
                        <svg class="absolute top-0 inset-x-0 w-full h-8 pointer-events-none overflow-visible">
                            {{-- Bajada central desde el bus de Gerencia Municipal --}}
                            <path d="M 690 -16 L 690 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Barra transversal que cubre el ancho de los órganos de apoyo y asesoramiento --}}
                            <path d="M 180 12 L 1200 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Bajadas a columnas --}}
                            <path d="M 180 12 L 180 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 430 12 L 430 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 720 12 L 720 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 980 12 L 980 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 1200 12 L 1200 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                        </svg>

                        <div class="grid grid-cols-5 gap-4 items-start">

                            {{-- Columna 1: OFICINA GENERAL DE ADMINISTRACIÓN (Verde) --}}
                            <div class="flex flex-col space-y-1.5">
                                <div class="p-2.5 rounded bg-[#166534] border-2 border-green-950 text-white shadow-xs text-center cursor-pointer hover:bg-green-800 transition-colors"
                                     :class="isMatched(organigram.middle_groups.apoyo[0].header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.middle_groups.apoyo[0].header.id)">
                                    <h4 class="text-[10.5px] font-black uppercase tracking-tight leading-snug">
                                        OFICINA GENERAL DE ADMINISTRACIÓN
                                    </h4>
                                    <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-green-200 font-mono">
                                        <span x-text="'👥 ' + organigram.middle_groups.apoyo[0].header.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.middle_groups.apoyo[0].header.assets_count"></span>
                                    </div>
                                </div>
                                {{-- Sub-oficinas de Administración --}}
                                <div class="flex flex-col space-y-1.5 pl-3 border-l-2 border-zinc-900 ml-4 pt-1">
                                    <template x-for="sub in organigram.middle_groups.apoyo[0].sub_offices" :key="sub.name">
                                        <div class="p-1.5 rounded bg-[#f0fdf4] border border-[#166534] text-zinc-900 shadow-2xs cursor-pointer hover:bg-green-100 transition-colors text-center"
                                             :class="isMatched(sub) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                             @click="openUnitInspector(sub.id)">
                                            <p class="text-[9.5px] font-bold uppercase tracking-tight text-emerald-950 leading-tight" x-text="sub.name"></p>
                                            <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-emerald-800 font-mono">
                                                <span x-text="'👥 ' + sub.employees_count"></span>
                                                <span x-text="'💻 ' + sub.assets_count"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Columna 2: OFICINA GENERAL DE SUPERVISIÓN Y LIQUIDACIÓN (Verde) --}}
                            <div class="flex flex-col space-y-1.5">
                                <div class="p-2.5 rounded bg-[#166534] border-2 border-green-950 text-white shadow-xs text-center cursor-pointer hover:bg-green-800 transition-colors"
                                     :class="isMatched(organigram.middle_groups.apoyo[1].header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.middle_groups.apoyo[1].header.id)">
                                    <h4 class="text-[10.5px] font-black uppercase tracking-tight leading-snug">
                                        OFICINA GENERAL DE SUPERVISIÓN Y LIQUIDACIÓN DE INVERSIONES
                                    </h4>
                                    <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-green-200 font-mono">
                                        <span x-text="'👥 ' + organigram.middle_groups.apoyo[1].header.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.middle_groups.apoyo[1].header.assets_count"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna 3: OFICINA GENERAL DE PLANEAMIENTO Y PRESUPUESTO (Ámbar) - ¡CONTIENE OTI! --}}
                            <div class="flex flex-col space-y-1.5">
                                <div class="p-2.5 rounded bg-[#c2410c] border-2 border-amber-950 text-white shadow-xs text-center cursor-pointer hover:bg-amber-800 transition-colors"
                                     :class="isMatched(organigram.middle_groups.asesoramiento[0].header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.middle_groups.asesoramiento[0].header.id)">
                                    <h4 class="text-[10.5px] font-black uppercase tracking-tight leading-snug">
                                        OFICINA GENERAL DE PLANEAMIENTO Y PRESUPUESTO
                                    </h4>
                                    <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-amber-200 font-mono">
                                        <span x-text="'👥 ' + organigram.middle_groups.asesoramiento[0].header.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.middle_groups.asesoramiento[0].header.assets_count"></span>
                                    </div>
                                </div>
                                {{-- Sub-oficinas de OGPP incluyendo OTI --}}
                                <div class="flex flex-col space-y-1.5 pl-3 border-l-2 border-zinc-900 ml-4 pt-1">
                                    <template x-for="sub in organigram.middle_groups.asesoramiento[0].sub_offices" :key="sub.name">
                                        <div class="p-1.5 rounded border shadow-2xs cursor-pointer transition-all text-center relative"
                                             :class="{
                                                 'bg-blue-50 border-blue-600 ring-2 ring-blue-500/40': sub.code === 'OTI' || sub.name.includes('TECNOLOGÍAS'),
                                                 'bg-[#fff7ed] border-[#ea580c]/60 text-zinc-900 hover:bg-orange-100': !(sub.code === 'OTI' || sub.name.includes('TECNOLOGÍAS')),
                                                 'ring-4 ring-amber-400 animate-pulse': isMatched(sub)
                                             }"
                                             @click="openUnitInspector(sub.id)">
                                            <template x-if="sub.code === 'OTI' || sub.name.includes('TECNOLOGÍAS')">
                                                <span class="absolute -top-1.5 -right-1 px-1 py-0.2 rounded text-[8px] font-extrabold bg-blue-600 text-white">
                                                    NUESTRA ÁREA
                                                </span>
                                            </template>
                                            <p class="text-[9.5px] font-bold uppercase tracking-tight leading-tight"
                                               :class="(sub.code === 'OTI' || sub.name.includes('TECNOLOGÍAS')) ? 'text-blue-950 font-black' : 'text-amber-950'"
                                               x-text="sub.name"></p>
                                            <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] font-mono"
                                                 :class="(sub.code === 'OTI' || sub.name.includes('TECNOLOGÍAS')) ? 'text-blue-700 font-bold' : 'text-amber-800'">
                                                <span x-text="'👥 ' + sub.employees_count"></span>
                                                <span x-text="'💻 ' + sub.assets_count"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Columna 4: OFICINA GENERAL DE ASESORÍA JURÍDICA (Ámbar) --}}
                            <div class="flex flex-col space-y-1.5">
                                <div class="p-2.5 rounded bg-[#c2410c] border-2 border-amber-950 text-white shadow-xs text-center cursor-pointer hover:bg-amber-800 transition-colors"
                                     :class="isMatched(organigram.middle_groups.asesoramiento[1].header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.middle_groups.asesoramiento[1].header.id)">
                                    <h4 class="text-[10.5px] font-black uppercase tracking-tight leading-snug">
                                        OFICINA GENERAL DE ASESORÍA JURÍDICA
                                    </h4>
                                    <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-amber-200 font-mono">
                                        <span x-text="'👥 ' + organigram.middle_groups.asesoramiento[1].header.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.middle_groups.asesoramiento[1].header.assets_count"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Columna 5: OFICINA GENERAL DE GESTIÓN DE INVERSIONES (Ámbar) --}}
                            <div class="flex flex-col space-y-1.5">
                                <div class="p-2.5 rounded bg-[#c2410c] border-2 border-amber-950 text-white shadow-xs text-center cursor-pointer hover:bg-amber-800 transition-colors"
                                     :class="isMatched(organigram.middle_groups.asesoramiento[2].header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                     @click="openUnitInspector(organigram.middle_groups.asesoramiento[2].header.id)">
                                    <h4 class="text-[10.5px] font-black uppercase tracking-tight leading-snug">
                                        OFICINA GENERAL DE GESTIÓN DE INVERSIONES
                                    </h4>
                                    <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-amber-200 font-mono">
                                        <span x-text="'👥 ' + organigram.middle_groups.asesoramiento[2].header.employees_count"></span>
                                        <span x-text="'💻 ' + organigram.middle_groups.asesoramiento[2].header.assets_count"></span>
                                    </div>
                                </div>
                                {{-- Sub-oficinas de Inversiones --}}
                                <div class="flex flex-col space-y-1.5 pl-3 border-l-2 border-zinc-900 ml-4 pt-1">
                                    <template x-for="sub in organigram.middle_groups.asesoramiento[2].sub_offices" :key="sub.name">
                                        <div class="p-1.5 rounded bg-[#fff7ed] border border-[#ea580c]/60 text-zinc-900 shadow-2xs cursor-pointer hover:bg-orange-100 transition-colors text-center"
                                             :class="isMatched(sub) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                             @click="openUnitInspector(sub.id)">
                                            <p class="text-[9.5px] font-bold uppercase tracking-tight text-amber-950 leading-tight" x-text="sub.name"></p>
                                            <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-amber-800 font-mono">
                                                <span x-text="'👥 ' + sub.employees_count"></span>
                                                <span x-text="'💻 ' + sub.assets_count"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- ============================================================= --}}
                    {{-- 4. BLOQUE DE ÓRGANOS DE LÍNEA (LAS 6 GERENCIAS DE LÍNEA)      --}}
                    {{-- ============================================================= --}}
                    <div class="relative w-full mt-6 pt-6">
                        {{-- Bus conector horizontal que conecta las 6 Gerencias de Línea --}}
                        <svg class="absolute top-0 inset-x-0 w-full h-8 pointer-events-none overflow-visible">
                            {{-- Tronco vertical que desciende hacia las gerencias de línea --}}
                            <path d="M 690 -16 L 690 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Barra transversal que cubre las 6 columnas --}}
                            <path d="M 105 12 L 1260 12" fill="none" stroke="#0f172a" stroke-width="2"/>
                            {{-- Bajadas individuales a cada una de las 6 gerencias --}}
                            <path d="M 105 12 L 105 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 336 12 L 336 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 567 12 L 567 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 798 12 L 798 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 1029 12 L 1029 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                            <path d="M 1260 12 L 1260 24" fill="none" stroke="#0f172a" stroke-width="2"/>
                        </svg>

                        <div class="grid grid-cols-6 gap-3 items-start">
                            <template x-for="gerencia in organigram.line_gerencias" :key="gerencia.header.name">
                                <div class="flex flex-col space-y-1.5">
                                    {{-- Cabecera de la Gerencia de Línea (Azul Cerúleo) --}}
                                    <div class="p-2.5 rounded bg-[#0284c7] border-2 border-sky-950 text-white shadow-xs text-center cursor-pointer hover:bg-sky-700 transition-colors relative min-h-[58px] flex flex-col justify-center"
                                         :class="isMatched(gerencia.header) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                         @click="openUnitInspector(gerencia.header.id)">
                                        <h4 class="text-[10px] font-black uppercase tracking-tight leading-snug" x-text="gerencia.header.name"></h4>
                                        <div class="flex items-center justify-center gap-2 mt-0.5 text-[8.5px] text-sky-100 font-mono">
                                            <span x-text="'👥 ' + gerencia.header.employees_count"></span>
                                            <span x-text="'💻 ' + gerencia.header.assets_count"></span>
                                        </div>
                                    </div>

                                    {{-- Subgerencias Dependientes (Apiladas con fondo Celeste Claro) --}}
                                    <div class="flex flex-col space-y-1.5 pl-2.5 border-l-2 border-zinc-900 ml-3 pt-1">
                                        <template x-for="sub in gerencia.sub_gerencias" :key="sub.name">
                                            <div class="p-1.5 rounded bg-[#f0f9ff] border border-[#0284c7]/70 text-zinc-900 shadow-2xs cursor-pointer hover:bg-sky-100 transition-colors text-center relative"
                                                 :class="isMatched(sub) ? 'ring-4 ring-amber-400 animate-pulse' : ''"
                                                 @click="openUnitInspector(sub.id)">
                                                <p class="text-[9.5px] font-bold uppercase tracking-tight text-sky-950 leading-tight" x-text="sub.name"></p>
                                                <div class="flex items-center justify-center gap-1.5 mt-0.5 text-[8.5px] text-sky-800 font-mono">
                                                    <span x-text="'👥 ' + sub.employees_count"></span>
                                                    <span x-text="'💻 ' + sub.assets_count"></span>
                                                    <template x-if="sub.children_count > 0">
                                                        <span class="px-1 rounded bg-sky-200 text-sky-900 font-sans font-bold text-[8px]"
                                                              :title="sub.children_count + ' dependencias subordinadas en sistema'"
                                                              x-text="'+' + sub.children_count"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- PANEL LATERAL INSPECTOR (SLIDE-OVER AL TOCAR CUALQUIER CAJA) --}}
        <div x-show="inspectorOpen"
             x-cloak
             class="absolute inset-y-0 right-0 z-40 w-80 sm:w-96 bg-white/95 backdrop-blur-md border-l border-zinc-200 shadow-2xl flex flex-col transition-all duration-300"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full">

            {{-- Encabezado del Inspector --}}
            <div class="p-4 border-b border-zinc-200 bg-zinc-50/70 flex items-start justify-between">
                <div class="min-w-0 pr-2">
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono uppercase bg-zinc-200 text-zinc-700 font-semibold"
                          x-text="selectedUnit ? selectedUnit.type_label : 'Dependencia'"></span>
                    <h3 class="text-base font-bold text-zinc-900 mt-1 leading-snug"
                        x-text="selectedUnit ? selectedUnit.name : fallbackTitle"></h3>
                    <p class="text-[11px] text-zinc-500 mt-0.5 truncate" x-text="selectedUnit ? selectedUnit.full_path : ''"></p>
                </div>
                <button type="button" @click="closeInspector()" class="p-1.5 rounded-lg text-zinc-400 hover:text-zinc-700 hover:bg-zinc-200/50">
                    ✕
                </button>
            </div>

            {{-- Contenido Scrollable del Inspector --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scrollbar text-xs">
                {{-- Responsable Titular --}}
                <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 block mb-1.5">Responsable Titular</span>
                    <template x-if="selectedUnit && selectedUnit.responsible">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-700 text-white font-bold text-xs flex items-center justify-center shrink-0"
                                 x-text="selectedUnit.responsible.name.charAt(0)"></div>
                            <div class="min-w-0">
                                <p class="font-semibold text-zinc-900 truncate" x-text="selectedUnit.responsible.name"></p>
                                <p class="text-[11px] text-zinc-500 truncate" x-text="selectedUnit.responsible.position || 'Responsable'"></p>
                                <p class="text-[10px] font-mono text-zinc-400" x-text="'DNI: ' + selectedUnit.responsible.dni"></p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!selectedUnit || !selectedUnit.responsible">
                        <p class="text-zinc-400 italic">Sin responsable asignado actualmente.</p>
                    </template>
                </div>

                {{-- Métricas Rápidas --}}
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Personal</span>
                        <span class="text-xl font-bold font-mono text-zinc-900 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.employees ? selectedUnit.employees.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">trabajadores</span>
                    </div>
                    <div class="p-3 rounded-xl bg-zinc-50 border border-zinc-200/80">
                        <span class="text-[10px] uppercase font-bold text-zinc-400 block">Equipos OTI</span>
                        <span class="text-xl font-bold font-mono text-blue-700 mt-0.5 block"
                              x-text="selectedUnit && selectedUnit.assets ? selectedUnit.assets.length : 0"></span>
                        <span class="text-[10px] text-zinc-400">activos en custodia</span>
                    </div>
                </div>

                {{-- Subunidades dependientes directas en DB --}}
                <template x-if="selectedUnit && selectedUnit.children && selectedUnit.children.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Subunidades y Áreas (' + selectedUnit.children.length + ')'"></span>
                        <div class="space-y-1 max-h-40 overflow-y-auto pr-1">
                            <template x-for="child in selectedUnit.children" :key="child.id">
                                <div @click="openUnitInspector(child.id)"
                                     class="p-2 rounded-lg bg-zinc-50 hover:bg-zinc-100 border border-zinc-200/60 cursor-pointer flex items-center justify-between transition-colors">
                                    <span class="font-medium text-zinc-800 truncate" x-text="child.name"></span>
                                    <span class="text-[10px] font-mono text-zinc-400" x-text="child.type_label"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Personal Adscrito --}}
                <template x-if="selectedUnit && selectedUnit.employees && selectedUnit.employees.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Personal en esta dependencia (' + selectedUnit.employees.length + ')'"></span>
                        <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                            <template x-for="emp in selectedUnit.employees" :key="emp.id">
                                <div class="p-2 rounded-lg bg-zinc-50 border border-zinc-200/60 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <p class="font-semibold text-zinc-900 truncate" x-text="emp.name"></p>
                                        <p class="text-[10px] text-zinc-400 truncate" x-text="emp.position || 'Servidor'"></p>
                                    </div>
                                    <a :href="emp.show_url" class="text-blue-600 hover:text-blue-800 text-[10px] font-medium shrink-0">Ficha &rarr;</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Activos Informáticos OTI --}}
                <template x-if="selectedUnit && selectedUnit.assets && selectedUnit.assets.length > 0">
                    <div>
                        <span class="text-[11px] font-bold text-zinc-400 uppercase tracking-wider block mb-2"
                              x-text="'Equipos OTI Asignados (' + selectedUnit.assets.length + ')'"></span>
                        <div class="space-y-1 max-h-36 overflow-y-auto pr-1">
                            <template x-for="asset in selectedUnit.assets" :key="asset.id">
                                <div class="p-2 rounded-lg bg-zinc-50 border border-zinc-200/60 flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <p class="font-semibold text-zinc-900 truncate" x-text="asset.name"></p>
                                        <p class="text-[10px] font-mono text-zinc-400" x-text="(asset.internal_code || 'S/C') + ' • ' + asset.type_label"></p>
                                    </div>
                                    <a :href="asset.show_url" class="text-blue-600 hover:text-blue-800 text-[10px] font-medium shrink-0">Ver &rarr;</a>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Pie del Inspector con Acciones Directas --}}
            <div class="p-3 border-t border-zinc-200 bg-zinc-50/70 flex items-center gap-2">
                <template x-if="selectedUnit && selectedUnit.show_url">
                    <a :href="selectedUnit.show_url"
                       class="flex-1 text-center py-2 px-3 rounded-lg text-xs font-medium bg-zinc-900 text-white hover:bg-zinc-800 transition-colors shadow-xs">
                        Ver Ficha Completa &rarr;
                    </a>
                </template>
                <template x-if="selectedUnit && selectedUnit.edit_url">
                    @can('org-unit.edit')
                    <a :href="selectedUnit.edit_url"
                       class="py-2 px-3 rounded-lg text-xs font-medium bg-white border border-zinc-200 text-zinc-700 hover:bg-zinc-50 transition-colors">
                        Editar
                    </a>
                    @endcan
                </template>
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- MODO 2: DIRECTORIO TABULAR (LISTA CON FILTROS Y PAGINACIÓN)               --}}
    {{-- ========================================================================= --}}
    <div x-show="viewMode === 'table'" class="flex-1 overflow-y-auto space-y-4 custom-scrollbar">

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

        {{-- Tabla de Oficinas --}}
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
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 border border-zinc-200/70">
                                    {{ $unit->type->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-zinc-600 truncate max-w-[180px] block">{{ $unit->parent?->name ?? '—' }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if ($unit->responsible)
                                    <span class="font-medium text-zinc-800">{{ $unit->responsible->full_name }}</span>
                                @else
                                    <span class="text-zinc-400 text-[11px]">Sin responsable</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php $empCount = $unit->employees_count ?? $unit->empoyees?->count() ?? 0; @endphp
                                <span class="font-mono text-[11px] {{ $empCount > 0 ? 'text-zinc-800 font-medium' : 'text-zinc-300' }}">{{ $empCount }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @php $assetCount = $unit->assets_count ?? $unit->assets?->count() ?? 0; @endphp
                                <span class="font-mono text-[11px] {{ $assetCount > 0 ? 'text-blue-700 font-medium' : 'text-zinc-300' }}">{{ $assetCount }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center gap-1.5 text-[11px] {{ $unit->is_active ? 'text-zinc-700' : 'text-zinc-400' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $unit->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}"></span>
                                    {{ $unit->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('organizational-units.show', $unit) }}" class="px-2 py-1 text-xs font-medium text-zinc-600 hover:text-zinc-950 rounded hover:bg-zinc-100 transition-colors">
                                    Ver &rarr;
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-zinc-400">
                                No se encontraron oficinas o dependencias.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($units->hasPages())
            <div class="p-4 border-t border-zinc-200/80 bg-zinc-50/40">
                {{ $units->links() }}
            </div>
            @endif
        </div>

    </div>

</div>

{{-- Script Alpine.js para Control del Organigrama --}}
<script>
    function officialOrganigramViewer(organigramData) {
        return {
            viewMode: 'organigram',
            organigram: organigramData,

            // Panning y Zoom
            panX: 20,
            panY: 20,
            zoom: 0.72,
            isDragging: false,
            dragStartX: 0,
            dragStartY: 0,

            // Inspector Slide-over
            inspectorOpen: false,
            selectedUnit: null,
            fallbackTitle: '',

            // Búsqueda
            searchQuery: '',

            init() {
                // Auto ajustar a la pantalla tras renderizar
                this.$nextTick(() => {
                    this.fitToScreen();
                });
            },

            fitToScreen() {
                const container = this.$refs.canvasContainer;
                const sheet = this.$refs.chartSheet;
                if (!container || !sheet) return;

                const cWidth = container.clientWidth - 40;
                const cHeight = container.clientHeight - 40;
                const sWidth = sheet.offsetWidth || 1380;
                const sHeight = sheet.offsetHeight || 980;

                const scaleX = cWidth / sWidth;
                const scaleY = cHeight / sHeight;
                const fitScale = Math.min(scaleX, scaleY);

                // Asignar zoom y centrar el organigrama exactamente en pantalla
                this.zoom = Math.max(Math.min(fitScale, 1.0), 0.35);
                this.panX = Math.max(0, (container.clientWidth - (sWidth * this.zoom)) / 2);
                this.panY = Math.max(10, (container.clientHeight - (sHeight * this.zoom)) / 2);
            },

            reset100() {
                this.zoom = 1.0;
                const container = this.$refs.canvasContainer;
                const sheet = this.$refs.chartSheet;
                if (container && sheet) {
                    this.panX = Math.max(20, (container.clientWidth - (sheet.offsetWidth || 1380)) / 2);
                    this.panY = 20;
                }
            },

            zoomIn() {
                this.zoom = Math.min(this.zoom * 1.15, 2.0);
            },

            zoomOut() {
                this.zoom = Math.max(this.zoom * 0.85, 0.30);
            },

            startDrag(e) {
                if (e.target.closest('button, a, input, select, .cursor-pointer')) return;
                this.isDragging = true;
                this.dragStartX = e.clientX - this.panX;
                this.dragStartY = e.clientY - this.panY;
            },

            onDrag(e) {
                if (!this.isDragging) return;
                this.panX = e.clientX - this.dragStartX;
                this.panY = e.clientY - this.dragStartY;
            },

            stopDrag() {
                this.isDragging = false;
            },

            onWheel(e) {
                const zoomFactor = e.deltaY < 0 ? 1.08 : 0.92;
                this.zoom = Math.min(Math.max(this.zoom * zoomFactor, 0.30), 2.0);
            },

            async openUnitInspector(unitId, fallbackName = null) {
                if (!unitId) {
                    this.selectedUnit = null;
                    this.fallbackTitle = fallbackName || 'Órgano Institucional';
                    this.inspectorOpen = true;
                    return;
                }

                this.inspectorOpen = true;
                this.fallbackTitle = 'Cargando...';

                try {
                    const res = await fetch('/organizational-units/' + unitId + '/quick-info');
                    if (res.ok) {
                        this.selectedUnit = await res.json();
                    }
                } catch (e) {
                    console.error('Error fetching unit info:', e);
                }
            },

            closeInspector() {
                this.inspectorOpen = false;
            },

            onSearch() {
                // El buscador actualiza searchQuery en reactividad
            },

            isMatched(item) {
                if (!this.searchQuery || this.searchQuery.trim().length < 2) return false;
                const q = this.searchQuery.trim().toLowerCase();
                const name = (item.name || '').toLowerCase();
                const code = (item.code || '').toLowerCase();
                const dbName = (item.db_name || '').toLowerCase();
                const titular = item.responsible ? (item.responsible.name || '').toLowerCase() : '';
                return name.includes(q) || code.includes(q) || dbName.includes(q) || titular.includes(q);
            },

            printOrganigram() {
                window.print();
            }
        };
    }
</script>

<style>
    @media print {
        header, aside, .no-print, [x-ref="canvasContainer"] > div:first-child {
            display: none !important;
        }
        #official-organigram-sheet {
            box-shadow: none !important;
            border: 1px solid #000 !important;
            width: 100% !important;
            transform: scale(0.95);
        }
    }
</style>

</x-layouts.app>
