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
            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="mt-5 flex-1 space-y-6 overflow-y-auto px-1" aria-label="Navegación principal">
        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">General</p>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                <span class="nav-icon"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></span>
                Dashboard
            </a>
        </div>

        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Operaciones</p>
            @can('view customers')
                <a href="{{ route('customers.index') }}" class="nav-item {{ request()->routeIs('customers.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
                    Clientes
                </a>
            @endcan
            @can('view vehicles')
                <a href="{{ route('vehicles.index') }}" class="nav-item {{ request()->routeIs('vehicles.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-car-side" aria-hidden="true"></i></span>
                    Flota
                </a>
                <a href="{{ route('fleet.catalogs') }}" class="nav-item {{ request()->routeIs('fleet.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                    Catálogos
                </a>
            @endcan
            @can('view reservations')
                <a href="{{ route('reservations.index') }}" class="nav-item {{ request()->routeIs('reservations.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
                    Reservas
                </a>
            @endcan
            @can('view rentals')
                <a href="{{ route('rentals.index') }}" class="nav-item {{ request()->routeIs('rentals.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
                    Alquileres
                </a>
            @endcan
            @can('manage inspections')
                <a href="{{ route('inspections.index') }}" class="nav-item {{ request()->routeIs('inspections.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span>
                    Inspecciones
                </a>
            @endcan
        </div>

        <div>
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Finanzas</p>
            @can('view invoices')
                <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></span>
                    Facturación
                </a>
            @endcan
            @can('view payments')
                <a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-credit-card" aria-hidden="true"></i></span>
                    Pagos
                </a>
            @endcan
            @can('view reports')
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                    <span class="nav-icon"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                    Reportes
                </a>
            @endcan
        </div>

        @canany(['manage users', 'manage configuration', 'view audit log'])
            <div>
                <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[.22em] text-slate-600">Administración</p>
                @can('manage users')
                    <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span>
                        Usuarios
                    </a>
                @endcan
                @can('manage configuration')
                    <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>
                        Configuración
                    </a>
                @endcan
                @can('view audit log')
                    <a href="{{ route('audit.index') }}" class="nav-item {{ request()->routeIs('audit.*') ? 'nav-item-active' : 'hover:bg-slate-900 hover:text-white' }}">
                        <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i></span>
                        Auditoría
                    </a>
                @endcan
            </div>
        @endcanany
    </nav>

    <div class="mt-4 rounded-2xl border border-emerald-400/20 bg-emerald-400/10 p-4">
        <div class="flex items-center justify-between text-xs">
            <span class="font-semibold text-emerald-300"><i class="fa-solid fa-code-branch mr-1" aria-hidden="true"></i>Versión 1.0</span>
            <span class="text-emerald-400"><i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>Operativa</span>
        </div>
        <p class="mt-2 text-[11px] leading-relaxed text-slate-400">Gestión integral de renta y flota.</p>
    </div>
</div>
