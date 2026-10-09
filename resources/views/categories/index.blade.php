@extends('layouts.boutique')
@section('title', 'Categories')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; $me = auth()->user(); @endphp

@if ($me->can_do('products.create'))
<form method="POST" action="{{ route('categories.store') }}" class="mb-6 flex flex-wrap gap-2 bg-white border border-stone-200 rounded-xl p-4">
    @csrf
    <input name="name" required maxlength="100" placeholder="New category name" class="{{ $inp }} flex-1 min-w-[200px]">
    <input name="sort_order" type="number" min="0" placeholder="Order" class="{{ $inp }} w-24">
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Add category</button>
</form>
@endif

<div class="space-y-2">
@foreach ($categories as $c)
    <div class="bg-white border border-stone-200 rounded-xl p-3 {{ $c->is_active ? '' : 'opacity-60' }}">
        <form method="POST" action="{{ route('categories.update', $c) }}" class="flex flex-wrap items-center gap-2">
            @csrf @method('PUT')
            <input name="name" value="{{ $c->name }}" required class="{{ $inp }} flex-1 min-w-[160px]" @disabled(!$me->can_do('products.edit'))>
            <input name="sort_order" type="number" min="0" value="{{ $c->sort_order }}" class="{{ $inp }} w-20" title="Order" @disabled(!$me->can_do('products.edit'))>
            <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) @disabled(!$me->can_do('products.edit'))> Active</label>
            <span class="text-xs text-stone-500">{{ $c->products_count }} products</span>
            @if ($me->can_do('products.edit'))<button class="rounded-lg border border-stone-300 px-3 py-2 text-sm">Save</button>@endif
        </form>
        @if ($me->can_do('products.delete'))
        <form method="POST" action="{{ route('categories.destroy', $c) }}" class="mt-1" onsubmit="return confirm('Delete category {{ addslashes($c->name) }}?')">
            @csrf @method('DELETE')
            <button class="text-xs text-red-600 hover:underline">Delete</button>
        </form>
        @endif
    </div>
@endforeach
</div>
@endsection
