@extends('layouts.boutique')
@section('title', 'Add product')
@section('content')
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    <section class="bg-white border border-stone-200 rounded-xl p-4">
        <h2 class="font-semibold mb-3">Product</h2>
        @include('products._product_fields')
    </section>
    <section class="bg-white border border-stone-200 rounded-xl p-4">
        <h2 class="font-semibold mb-1">First variation</h2>
        <p class="text-xs text-stone-500 mb-3">Add more sizes and colors after saving. Each has its own stock and price.</p>
        @include('products._variation_fields', ['uid' => 'new'])
    </section>
    <button class="rounded-lg bg-stone-900 text-white px-5 py-3 text-sm">Save product</button>
    <a href="{{ route('products.index') }}" class="px-3 text-sm underline">Cancel</a>
</form>
@endsection
