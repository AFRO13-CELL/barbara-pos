@extends('layouts.boutique')
@section('title', $product->name)
@section('content')
@php $me = auth()->user(); $canCost = $me->can_do('costs.view'); @endphp

<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded-xl p-4 mb-6">
    @csrf @method('PUT')
    <h2 class="font-semibold mb-3">Product details</h2>
    @include('products._product_fields', ['p' => $product])
    @if ($product->image_path)<img src="{{ asset('storage/'.$product->image_path) }}" alt="" class="mt-3 h-24 rounded-lg border">@endif
    <div class="mt-4 flex flex-wrap gap-2">
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Save details</button>
    </div>
</form>

<div class="flex flex-wrap gap-2 mb-6">
    @if ($me->can_do('products.create'))
    <form method="POST" action="{{ route('products.duplicate', $product) }}">@csrf<button class="rounded-lg border border-stone-300 px-3 py-2 text-sm">Duplicate</button></form>
    @endif
    @if ($me->can_do('products.delete'))
    <form method="POST" action="{{ route('products.archive', $product) }}" onsubmit="return confirm('Archive this product? It will be hidden from the POS but its sales history is kept.')">
        @csrf @method('DELETE')<button class="rounded-lg border border-red-300 text-red-700 px-3 py-2 text-sm">Archive product</button>
    </form>
    @endif
    <a href="{{ route('products.index') }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">Back to products</a>
</div>

<h2 class="font-semibold mb-2">Variations</h2>
<div class="space-y-3 mb-8">
@foreach ($product->variations as $v)
    <div class="bg-white border border-stone-200 rounded-xl p-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="font-medium">{{ $v->options_label }}
                <span class="ml-2 inline-block rounded-full px-2 py-0.5 text-xs
                    {{ $v->status === 'out' ? 'bg-red-100 text-red-700' : ($v->status === 'low' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') }}">
                    {{ $v->stock_qty }} in stock</span>
            </div>
            @if ($me->can_do('inventory.view'))<a href="{{ route('inventory.index', ['q' => $v->sku]) }}" class="text-xs underline">Adjust stock</a>@endif
        </div>
        <form method="POST" action="{{ route('variations.update', $v) }}">
            @csrf @method('PUT')
            @include('products._variation_fields', ['v' => $v, 'uid' => 'v'.$v->id])
            <button class="mt-3 rounded-lg border border-stone-300 px-3 py-2 text-sm">Save variation</button>
        </form>
        @if ($me->can_do('products.delete'))
        <form method="POST" action="{{ route('variations.destroy', $v) }}" class="mt-2" onsubmit="return confirm('Remove this variation?')">
            @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Remove variation</button>
        </form>
        @endif
    </div>
@endforeach
</div>

@if ($me->can_do('products.create'))
<section class="bg-white border border-stone-200 rounded-xl p-4">
    <h2 class="font-semibold mb-3">Add another size / color</h2>
    <form method="POST" action="{{ route('variations.store', $product) }}">
        @csrf
        @include('products._variation_fields', ['uid' => 'add'])
        <button class="mt-3 rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Add variation</button>
    </form>
</section>
@endif
@endsection
