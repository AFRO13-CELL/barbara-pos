{{-- =====================================================================
     MAIN LAYOUT: used by every screen (dashboard, products, sales, etc.)
     It draws the sidebar on the left, the header on top, and the page
     content in the middle.
     ===================================================================== --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    {{-- Makes the page fit phones and tablets properly --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Security token that forms and the POS need in order to save data --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Browser tab title: the page name plus the business name from Settings --}}
    <title>@yield('title', 'Dashboard') - {{ \App\Models\Setting::get('business_name', "Barbara's Boutique") }}</title>
    {{-- Loads the compiled styles and scripts (created by "npm run build") --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    // The person who is logged in
    $me = auth()->user();

    // Block accounts the owner has switched off, even if they are still logged in
    abort_unless($me->is_active, 403, 'Your account has been switched off. Please contact the owner.');

    // Only people allowed to see notifications get the bell
    $canNotify = $me->can_do('notifications.view');

    // How many notifications have not been read yet (shown as the red number on the bell)
    $unread = $canNotify ? \App\Models\Notification::whereNull('read_at')->count() : 0;

    // The sidebar menu: [label shown, page name, permission needed to see it]
    // A menu item with null as its permission is shown to everyone.
    // Staff only see the items their role is allowed to use.
    $nav = [
        ['Dashboard', 'dashboard', null],
        ['POS / New Sale', 'pos.index', 'pos.view'],
        ['Products', 'products.index', 'products.view'],
        ['Categories', 'categories.index', 'products.view'],
        ['Inventory', 'inventory.index', 'inventory.view'],
        ['Stock Movements', 'inventory.movements', 'inventory.view'],
        ['Sales', 'sales.index', 'sales.view'],
        ['Customers', 'customers.index', 'customers.view'],
        ['Suppliers', 'suppliers.index', 'suppliers.view'],
        ['Purchases', 'purchases.index', 'purchases.view'],
        ['Expenses', 'expenses.index', 'expenses.view'],
        ['Returns', 'returns.index', 'returns.view'],
        ['Reports', 'reports.index', 'reports.view'],
        ['Staff', 'staff.index', 'staff.view'],
        ['Backups', 'backups.index', 'backups.view'],
        ['Settings', 'settings.index', 'settings.view'],
        ['Audit Log', 'audit.index', 'settings.view'],
        // Notifications is not listed here: it is the bell in the header instead.
    ];
@endphp
{{-- "open" remembers whether the phone menu is showing --}}
<body class="bg-stone-50 text-stone-800 antialiased" x-data="{ open: false }">
<div class="min-h-screen lg:flex">

    {{-- Dark overlay behind the menu on phones. Tapping it closes the menu. --}}
    <div x-show="open" x-cloak @click="open=false" class="fixed inset-0 bg-black/40 z-30 lg:hidden"></div>

    {{-- ================= SIDEBAR =================
         On a computer it stays fixed on the left.
         On a phone it slides in when you tap the menu button. --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-stone-900 text-stone-200 flex flex-col transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="open ? 'translate-x-0' : '-translate-x-full'">

        {{-- Business name at the top (change it in Settings) --}}
        <div class="px-5 py-5 text-lg font-semibold tracking-wide text-amber-200 shrink-0">
            {{ \App\Models\Setting::get('business_name', "Barbara's Boutique") }}
        </div>

        {{-- Menu links. "flex-1" lets this area take the free space, and
             "overflow-y-auto" makes it scroll if the list is long, so the
             Log out section below always stays at the bottom. --}}
        <nav class="flex-1 overflow-y-auto px-3 pb-4 space-y-1 text-sm">
            @foreach ($nav as [$label, $route, $perm])
                {{-- Skip menu items this person is not allowed to use --}}
                @continue($perm && !$me->can_do($perm))
                {{-- The page you are on is highlighted in gold --}}
                <a href="{{ route($route) }}"
                   class="block rounded-lg px-3 py-2.5 {{ request()->routeIs($route) ? 'bg-amber-200 text-stone-900 font-medium' : 'hover:bg-stone-800' }}">{{ $label }}</a>
            @endforeach
        </nav>

        {{-- Bottom of the sidebar: who is logged in, and the Log out button --}}
        <div class="shrink-0 border-t border-stone-800 p-3">
            <div class="px-3 pb-2 text-xs text-stone-400 truncate">{{ $me->name }}</div>
            {{-- Logging out must be a form (POST) for security reasons --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full rounded-lg px-3 py-2.5 text-left text-sm text-red-300 hover:bg-stone-800">Log out</button>
            </form>
        </div>
    </aside>

    {{-- ================= RIGHT SIDE: header + page content ================= --}}
    <div class="flex-1 min-w-0">

        {{-- Header bar: stays at the top while you scroll --}}
        <header class="sticky top-0 z-20 flex items-center gap-3 bg-white border-b border-stone-200 px-4 py-3">
            {{-- Menu button: only visible on phones and tablets --}}
            <button @click="open=true" class="lg:hidden rounded-lg border border-stone-300 px-3 py-2 text-sm" aria-label="Menu">&#9776;</button>

            {{-- Page title (each screen sets its own) --}}
            <h1 class="text-lg font-semibold">@yield('title', 'Dashboard')</h1>

            <div class="ml-auto flex items-center gap-3 text-sm">
                {{-- Name of the logged-in person (hidden on very small screens) --}}
                <span class="hidden sm:inline text-stone-500">{{ $me->name }}</span>

                {{-- Notification bell: opens the Notifications page --}}
                @if ($canNotify)
                <a href="{{ route('notifications.index') }}" aria-label="Notifications"
                   class="relative rounded-lg border px-3 py-2 hover:bg-stone-100 {{ request()->routeIs('notifications.*') ? 'border-amber-400 bg-amber-50' : 'border-stone-300' }}">
                    {{-- The bell picture --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                    {{-- Red number badge: only shown when something is unread --}}
                    @if ($unread > 0)
                        <span class="absolute -top-1.5 -right-1.5 min-w-[1.25rem] rounded-full bg-red-600 text-white text-[11px] leading-5 text-center px-1">{{ $unread > 99 ? '99+' : $unread }}</span>
                    @endif
                </a>
                @endif
            </div>
        </header>

        {{-- Main area where each page puts its content --}}
        <main class="p-4 lg:p-6 max-w-7xl">
            {{-- Green message after something is saved --}}
            @if (session('status'))
                <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif
            {{-- Red message listing anything that went wrong --}}
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            {{-- The actual page (products, sales, reports, ...) is placed here --}}
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>