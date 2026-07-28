<div class="flex h-full flex-col border-r border-slate-800 bg-slate-950 px-4 py-5 text-white shadow-2xl shadow-slate-950/30">
    <div class="flex items-center justify-between px-2">
        <a href="{{ route('dashboard') }}" class="focus-ring flex items-center gap-3 rounded-xl">
            <x-application-logo class="h-11 w-11 text-blue-400" />
            <span>
                <span class="block text-xl font-black tracking-tight">RentaDrive</span>
                <span class="block text-[10px] font-semibold uppercase tracking-[.2em] text-slate-500">Fleet management</span>
            </span>
        </a>

        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-900 hover:text-white lg:hidden" @click="sidebarOpen = false">
            <span class="sr-only">Cerrar menú</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <nav class="mt-8 flex-1 space-y-7 overflow-y-auto px-1" aria-label="Navegación principal">
        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">General</p>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h6V4H4v9Zm10 7h6V11h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/>
                </svg>
                Dashboard
            </a>
        </div>

        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Operaciones</p>
            @foreach (['Clientes', 'Flota', 'Reservas', 'Alquileres', 'Inspecciones'] as $item)
                <span class="nav-item nav-item-disabled justify-between">
                    <span class="flex items-center gap-3">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                        {{ $item }}
                    </span>
                    <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[9px] uppercase tracking-wider text-slate-600">Próx.</span>
                </span>
            @endforeach
        </div>

        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Finanzas</p>
            @foreach (['Facturación', 'Pagos', 'Reportes'] as $item)
                <span class="nav-item nav-item-disabled justify-between">
                    <span class="flex items-center gap-3">
                        <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                        {{ $item }}
                    </span>
                    <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[9px] uppercase tracking-wider text-slate-600">Próx.</span>
                </span>
            @endforeach
        </div>

        @can('manage users')
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Administración</p>
                @foreach (['Usuarios', 'Configuración', 'Auditoría'] as $item)
                    <span class="nav-item nav-item-disabled justify-between">
                        <span class="flex items-center gap-3">
                            <span class="h-1.5 w-1.5 rounded-full bg-slate-600"></span>
                            {{ $item }}
                        </span>
                        <span class="rounded bg-slate-900 px-1.5 py-0.5 text-[9px] uppercase tracking-wider text-slate-600">Próx.</span>
                    </span>
                @endforeach
            </div>
        @endcan
    </nav>

    <div class="mt-4 rounded-2xl border border-blue-500/20 bg-blue-500/10 p-4">
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-blue-300">Fase 1 de 10</span>
            <span class="text-slate-500">10%</span>
        </div>
        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-800">
            <div class="h-full w-[10%] rounded-full bg-blue-500"></div>
        </div>
        <p class="mt-2 text-[11px] leading-relaxed text-slate-500">Fundación técnica y control de acceso.</p>
    </div>
</div>
