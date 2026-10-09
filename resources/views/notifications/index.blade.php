@extends('layouts.boutique')
@section('title', 'Notifications')
@section('content')
@php $icons = ['low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock', 'credit_overdue' => 'Credit overdue', 'supplier_payment' => 'Supplier payment', 'large_discount' => 'Large discount']; @endphp
<div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
    <a href="{{ route('notifications.index') }}" class="rounded-full px-4 py-2 {{ request()->boolean('unread') ? 'bg-white border border-stone-300' : 'bg-stone-900 text-white' }}">All</a>
    <a href="{{ route('notifications.index', ['unread' => 1]) }}" class="rounded-full px-4 py-2 {{ request()->boolean('unread') ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300' }}">Unread ({{ $unread }})</a>
    @if ($unread > 0)
    <form method="POST" action="{{ route('notifications.readAll') }}" class="ml-auto">@csrf<button class="rounded-lg border border-stone-300 bg-white px-3 py-2">Mark all as read</button></form>
    @endif
</div>
<div class="space-y-2">
@forelse ($notifications as $n)
    <div class="bg-white border rounded-xl p-3 flex flex-wrap items-center gap-3 {{ $n->read_at ? 'border-stone-200 opacity-70' : 'border-amber-300' }}">
        <div class="flex-1 min-w-[220px]">
            <div class="text-xs text-stone-500">{{ $icons[$n->type] ?? ucwords(str_replace('_', ' ', $n->type)) }} &middot; {{ $n->created_at->diffForHumans() }}</div>
            <div class="font-medium text-sm">{{ $n->title }}</div>
            <div class="text-sm text-stone-600">{{ $n->message }}</div>
        </div>
        <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf
            <button class="rounded-lg border border-stone-300 px-3 py-2 text-sm">{{ $n->url ? ($n->read_at ? 'Open' : 'Open and mark read') : 'Mark read' }}</button></form>
    </div>
@empty
    <div class="bg-white border border-stone-200 rounded-xl p-10 text-center text-stone-500">No notifications.</div>
@endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
