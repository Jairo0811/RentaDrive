<div class="flex h-full flex-col border-r border-slate-800 bg-slate-950 px-4 py-5 text-white shadow-2xl shadow-slate-950/30">
    <div class="flex items-center justify-between px-2">
        <a href="{{ route('dashboard') }}" class="focus-ring rounded-xl">
            <img
                src="{{ asset('images/rentadrive-logo-dark.png') }}"
                alt="RentaDrive"
                class="h-16 w-52 object-contain object-left drop-shadow-[0_8px_20px_rgba(5,104,245,.25)]"
            >
        </a>

        <button type="button" class="rounded-lg p-2 text-slate-400 hover:bg-slate-900 hover:text-white lg:hidden" @click="sidebarOpen = false">
            <span class="sr-only">Cerrar menú</span>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/>
            </svg>
        </button>
    </div>

    <nav class="mt-5 flex-1 space-y-6 overflow-y-auto px-1" aria-label="Navegación principal">
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
            @can('view customers')
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">CL</span> Clientes
                </a>
            @endcan
            @can('view vehicles')
                <a href="{{ route('vehicles.index') }}" class="nav-item {{ request()->routeIs('vehicles.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">FL</span> Flota
                </a>
                <a href="{{ route('fleet.catalogs') }}" class="nav-item {{ request()->routeIs('fleet.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">CA</span> Catálogos
                </a>
            @endcan
            @can('view reservations')
                <a href="{{ route('reservations.index') }}" class="nav-item {{ request()->routeIs('reservations.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">RE</span> Reservas
                </a>
            @endcan
            @can('view rentals')
                <a href="{{ route('rentals.index') }}" class="nav-item {{ request()->routeIs('rentals.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">AL</span> Alquileres
                </a>
            @endcan
            @can('manage inspections')
                <a href="{{ route('inspections.index') }}" class="nav-item {{ request()->routeIs('inspections.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">IN</span> Inspecciones
                </a>
            @endcan
        </div>

        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Finanzas</p>
            @can('view invoices')
                <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">FA</span> Facturación
                </a>
            @endcan
            @can('view payments')
                <a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">PA</span> Pagos
                </a>
            @endcan
            @can('view reports')
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon">RP</span> Reportes
                </a>
            @endcan
        </div>

        @canany(['manage users', 'manage configuration', 'view audit log'])
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Administración</p>
                @can('manage users')
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon">US</span> Usuarios
                    </a>
                @endcan
                @can('manage configuration')
                    <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon">CO</span> Configuración
                    </a>
                @endcan
                @can('view audit log')
                    <a href="{{ route('audit.index') }}" class="nav-item {{ request()->routeIs('audit.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon">AU</span> Auditoría
                    </a>
                @endcan
            </div>
        @endcanany
    </nav>

    <div class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-4">
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-emerald-300">Versión 1.0</span>
            <span class="text-emerald-400">Operativa</span>
        </div>
        <p class="mt-2 text-[11px] leading-relaxed text-slate-400">Gestión integral de renta y flota.</p>
    </div>
</div>
