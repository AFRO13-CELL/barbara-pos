@extends('layouts.boutique')
@section('title', 'Roles and permissions')
@section('content')
<p class="mb-4 text-sm text-stone-600">Tick what each role may do. <strong>Owner</strong> can always do everything and cannot be changed. Changes apply straight away.</p>
<form method="POST" action="{{ route('settings.roles.save') }}">
    @csrf
    <div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Permission</th><th class="px-3 py-3 text-center">Owner</th><th class="px-3 py-3 text-center">Manager</th><th class="px-3 py-3 text-center">Cashier</th></tr></thead>
        <tbody>
        @foreach ($perms as $group => $items)
            <tr class="bg-stone-50"><td colspan="4" class="px-4 py-2 text-xs font-semibold uppercase text-stone-500">{{ $group === 'special' ? 'Special permissions' : $group }}</td></tr>
            @foreach ($items as $p)
            <tr class="border-t border-stone-100">
                <td class="px-4 py-2">{{ $p->label }} <span class="text-xs text-stone-400">{{ $p->key }}</span></td>
                <td class="px-3 text-center"><input type="checkbox" checked disabled></td>
                @foreach (['manager', 'cashier'] as $r)
                <td class="px-3 text-center"><input type="checkbox" name="perms[{{ $r }}][{{ $p->key }}]" value="1" @checked($roles[$r]->permissions->contains('key', $p->key))></td>
                @endforeach
            </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
    </div>
    <button class="mt-4 rounded-xl bg-stone-900 text-white px-6 py-3 text-sm" onclick="return confirm('Save these permissions? Staff are affected immediately.')">Save permissions</button>
    <a href="{{ route('settings.index') }}" class="ml-3 text-sm underline">Back to settings</a>
</form>
@endsection
