@props(['status'])

@php
    $styles = match ($status) {
        'active', 'available', 'confirmed', 'paid', 'completed', 'closed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300',
        'pending', 'scheduled', 'partial', 'reserved' => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300',
        'open', 'rented', 'in_progress' => 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/50 dark:text-blue-300',
        'cancelled', 'suspended', 'inactive', 'damaged' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-300',
        'maintenance', 'converted' => 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/50 dark:text-violet-300',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300',
    };

    $label = match ($status) {
        'active' => 'Activo',
        'available' => 'Disponible',
        'confirmed' => 'Confirmada',
        'paid' => 'Pagada',
        'completed' => 'Completado',
        'closed' => 'Cerrado',
        'pending' => 'Pendiente',
        'scheduled' => 'Programado',
        'partial' => 'Pago parcial',
        'reserved' => 'Reservado',
        'open' => 'Abierto',
        'rented' => 'Alquilado',
        'in_progress' => 'En proceso',
        'cancelled' => 'Cancelado',
        'suspended' => 'Suspendido',
        'inactive' => 'Inactivo',
        'damaged' => 'Con daños',
        'maintenance' => 'Mantenimiento',
        'converted' => 'Convertida',
        'delivery' => 'Entrega',
        'return' => 'Devolución',
        default => ucfirst(str_replace('_', ' ', (string) $status)),
    };
@endphp

<span {{ $attributes->class("inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {$styles}") }}>
    {{ $label }}
</span>
