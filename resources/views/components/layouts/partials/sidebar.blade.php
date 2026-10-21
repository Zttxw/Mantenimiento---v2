{{-- Ultraminimalist Flat Navigation Sidebar (Linear / Vercel style) --}}
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-950 border-r border-white/10 flex flex-col transition-all duration-300">

    {{-- Brand Header --}}
    <div class="px-6 py-6 border-b border-white/5 flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <div class="w-8 h-8 rounded-lg bg-white text-slate-950 font-bold text-sm flex items-center justify-center tracking-tight shadow-sm transition-transform group-hover:scale-105">
                S
            </div>
            <div class="leading-none">
                <span class="text-white font-semibold text-sm tracking-tight block">SIGAT OTI</span>
                <span class="text-[11px] text-zinc-400 font-normal tracking-wide block mt-1">MDSJ • San Jerónimo</span>
            </div>
        </a>
    </div>

    {{-- Flat Navigation List (1 Click Direct Access) --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">

        {{-- 1. Dashboard --}}
        @php
            $isDashboard = request()->routeIs('dashboard');
        @endphp
        <a href="{{ route('dashboard') }}"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 {{ $isDashboard ? 'bg-white text-slate-950 font-semibold shadow-sm' : 'text-zinc-400 hover:text-white hover:bg-white/5 font-medium' }}">
            {{-- Icono: Matriz de Control / Dashboard --}}
            <svg class="w-4 h-4 {{ $isDashboard ? 'text-slate-950' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
            </svg>
            <span>Dashboard</span>
        </a>

        {{-- 2. Soporte y Tickets --}}
        @php
            $isSoporte = request()->routeIs('maintenance-cases.*', 'tecnico.*');
        @endphp
        <a href="{{ route('maintenance-cases.index') }}"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 {{ $isSoporte ? 'bg-white text-slate-950 font-semibold shadow-sm' : 'text-zinc-400 hover:text-white hover:bg-white/5 font-medium' }}">
            {{-- Icono: Ticket Oficial de Mesa de Ayuda --}}
            <svg class="w-4 h-4 {{ $isSoporte ? 'text-slate-950' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
            </svg>
            <span>Soporte y Tickets</span>
        </a>

        {{-- 3. Campañas Preventivas --}}
        @php
            $isCampanas = request()->routeIs('campaigns.*', 'maintenance-templates.*');
        @endphp
        <a href="{{ route('campaigns.index') }}"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 {{ $isCampanas ? 'bg-white text-slate-950 font-semibold shadow-sm' : 'text-zinc-400 hover:text-white hover:bg-white/5 font-medium' }}">
            {{-- Icono: Calendario / Cronograma Programado --}}
            <svg class="w-4 h-4 {{ $isCampanas ? 'text-slate-950' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/>
            </svg>
            <span>Campañas</span>
        </a>

        {{-- 4. Inventario de Activos y Equipos --}}
        @php
            $isInventario = request()->routeIs('assets.*', 'asset-movements.*', 'organizational-units.*', 'employees.*');
        @endphp
        <a href="{{ route('assets.index') }}"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 {{ $isInventario ? 'bg-white text-slate-950 font-semibold shadow-sm' : 'text-zinc-400 hover:text-white hover:bg-white/5 font-medium' }}">
            {{-- Icono: Computadora / Hardware TI --}}
            <svg class="w-4 h-4 {{ $isInventario ? 'text-slate-950' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
            </svg>
            <span>Inventario</span>
        </a>

        {{-- 5. Reportes y Documentos Oficiales --}}
        @php
            $isReportes = request()->routeIs('documents.*');
        @endphp
        <a href="{{ route('documents.index') }}"
           class="group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all duration-150 {{ $isReportes ? 'bg-white text-slate-950 font-semibold shadow-sm' : 'text-zinc-400 hover:text-white hover:bg-white/5 font-medium' }}">
            {{-- Icono: Informe / Acta Oficial Escrita --}}
            <svg class="w-4 h-4 {{ $isReportes ? 'text-slate-950' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span>Reportes</span>
        </a>

        {{-- Espaciador --}}
        <div class="pt-4 border-t border-white/5 my-2"></div>

        {{-- 6. Configuración y Administración --}}
        @php
            $isConfig = request()->routeIs('settings.*', 'agents.*', 'users.*', 'admin.*');
        @endphp
        <a href="{{ route('settings.index') }}"
           class="group flex items-center gap-3 px-3 py-2 rounded-lg text-xs transition-all duration-150 {{ $isConfig ? 'bg-white/10 text-white font-medium' : 'text-zinc-400 hover:text-white hover:bg-white/5' }}">
            {{-- Icono: Sliders de Configuración / Ajustes --}}
            <svg class="w-4 h-4 {{ $isConfig ? 'text-white' : 'text-zinc-400 group-hover:text-white' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
            </svg>
            <span>Configuración</span>
        </a>

    </nav>

    {{-- Footer Actions --}}
    <div class="p-3 border-t border-white/5 bg-slate-950 flex items-center justify-between">
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-zinc-400 hover:text-white hover:bg-white/5 transition-colors border border-white/5">
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                <span>Cerrar Sesión</span>
            </button>
        </form>
    </div>

</aside>
