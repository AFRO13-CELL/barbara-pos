@extends('layouts.boutique')
@section('title', 'POS / New Sale')
@section('content')
@php $sym = \App\Models\Setting::get('currency_symbol', '₵'); $inp = 'rounded-lg border-stone-300 text-sm'; @endphp

<div x-data="pos()" x-init="init()" class="grid lg:grid-cols-5 gap-4">

    {{-- LEFT: search + products --}}
    <section class="lg:col-span-3 space-y-3">
        <input x-ref="q" x-model="q" @input="typed()" @keydown.enter.prevent="enter()" type="search" autocomplete="off"
               placeholder="Search or scan: name, SKU, barcode, size, color..."
               class="w-full rounded-xl border-2 border-stone-300 focus:border-amber-500 text-base px-4 py-3">
        <div class="flex gap-2 overflow-x-auto pb-1">
            <button type="button" @click="cat=''; search()" :class="cat==='' ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300'" class="shrink-0 rounded-full px-3 py-1.5 text-sm">All</button>
            @foreach ($categories as $c)
            <button type="button" @click="cat='{{ $c->id }}'; search()" :class="cat==='{{ $c->id }}' ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300'" class="shrink-0 rounded-full px-3 py-1.5 text-sm">{{ $c->name }}</button>
            @endforeach
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <template x-for="v in results" :key="v.id">
                <button type="button" @click="add(v)" :disabled="v.stock<=0"
                        class="text-left bg-white border border-stone-200 rounded-xl p-3 hover:border-amber-400 disabled:opacity-50 active:scale-[0.98]">
                    <div class="font-medium text-sm leading-tight" x-text="v.name"></div>
                    <div class="text-xs text-stone-500" x-text="v.label"></div>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="font-semibold text-sm" x-text="money(v.price)"></span>
                        <span class="text-xs rounded-full px-2 py-0.5" :class="v.stock<=0 ? 'bg-red-100 text-red-700' : 'bg-stone-100 text-stone-600'" x-text="v.stock<=0 ? 'Out' : v.stock + ' left'"></span>
                    </div>
                </button>
            </template>
        </div>
        <p x-show="results.length===0" class="text-sm text-stone-500">No matching items.</p>
    </section>

    {{-- RIGHT: cart + payment --}}
    <section class="lg:col-span-2 bg-white border border-stone-200 rounded-xl p-4 space-y-4 h-fit">
        <div x-show="error" x-cloak class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm" x-text="error"></div>

        <div>
            <h2 class="font-semibold mb-2">Cart</h2>
            <p x-show="cart.length===0" class="text-sm text-stone-500">Tap a product or scan a barcode to add it.</p>
            <template x-for="l in cart" :key="l.id">
                <div class="flex items-center gap-2 py-2 border-b border-stone-100">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate" x-text="l.name"></div>
                        <div class="text-xs text-stone-500"><span x-text="l.label"></span> &middot; <span x-text="money(l.price)"></span></div>
                    </div>
                    <button type="button" @click="inc(l,-1)" class="w-9 h-9 rounded-lg border border-stone-300 text-lg">-</button>
                    <span class="w-6 text-center text-sm" x-text="l.qty"></span>
                    <button type="button" @click="inc(l,1)" class="w-9 h-9 rounded-lg border border-stone-300 text-lg">+</button>
                    <div class="w-20 text-right text-sm font-medium" x-text="money(l.price*l.qty)"></div>
                    <button type="button" @click="remove(l)" class="text-stone-400 hover:text-red-600 px-1" aria-label="Remove">&times;</button>
                </div>
            </template>
        </div>

        <div class="space-y-2">
            <label class="text-xs block">Customer (optional for fully paid sales)
                <select x-model="customerId" class="{{ $inp }} w-full">
                    <option value="">Walk-in customer</option>
                    @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' - '.$c->phone : '' }}</option>@endforeach
                </select></label>
            <div class="grid grid-cols-2 gap-2" x-show="!customerId">
                <input x-model="newName" placeholder="New customer name" class="{{ $inp }}">
                <input x-model="newPhone" placeholder="Phone" class="{{ $inp }}">
            </div>
        </div>

        <div class="flex gap-2 items-center">
            <select x-model="discType" class="{{ $inp }}">
                <option value="none">No discount</option><option value="percent">Discount %</option><option value="fixed">Discount {{ $sym }}</option>
            </select>
            <input x-show="discType!=='none'" x-model="discValue" type="number" min="0" step="0.01" class="{{ $inp }} w-28">
            <span class="text-xs text-stone-500" x-show="discType!=='none'">Your limit: {{ $maxDiscount }}%</span>
        </div>

        <div class="space-y-2">
            <div class="text-sm font-medium">Payment</div>
            <template x-for="(p,i) in payments" :key="i">
                <div class="grid grid-cols-12 gap-2">
                    <select x-model="p.method" class="{{ $inp }} col-span-5">
                        <option value="cash">Cash</option><option value="mobile_money">Mobile Money</option>
                        <option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="store_credit">Store credit</option>
                    </select>
                    <input x-model="p.amount" type="number" min="0" step="0.01" placeholder="Amount" class="{{ $inp }} col-span-4">
                    <button type="button" @click="fillRemaining(p)" class="col-span-2 rounded-lg border border-stone-300 text-xs">Rest</button>
                    <button type="button" x-show="payments.length>1" @click="payments.splice(i,1)" class="col-span-1 text-stone-400 hover:text-red-600">&times;</button>
                    <input x-show="p.method==='mobile_money' || p.method==='bank_transfer'" x-model="p.provider"
                           :placeholder="p.method==='bank_transfer' ? 'Bank name' : 'Provider (MTN, Telecel, AT)'" class="{{ $inp }} col-span-6">
                    <input x-show="p.method!=='cash' && p.method!=='store_credit'" x-model="p.reference" placeholder="Reference" class="{{ $inp }} col-span-6">
                </div>
            </template>
            <button type="button" @click="payments.push({method:'cash',amount:'',provider:'',reference:''})" class="text-xs underline">+ Add another payment method (mixed payment)</button>
        </div>

        <div x-show="balance>0" x-cloak class="space-y-1">
            @if ($canCredit)
            <label class="text-xs block">Unpaid balance is credit. Due date<input x-model="dueDate" type="date" class="{{ $inp }} w-full"></label>
            @else
            <p class="text-xs text-red-700">The sale must be paid in full. You are not allowed to sell on credit.</p>
            @endif
        </div>

        <div class="border-t border-stone-200 pt-3 space-y-1 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
            <div class="flex justify-between text-stone-500" x-show="discount>0"><span>Discount</span><span x-text="'- ' + money(discount)"></span></div>
            <div class="flex justify-between text-lg font-semibold"><span>Total</span><span x-text="money(total)"></span></div>
            <div class="flex justify-between"><span>Paid</span><span x-text="money(paid)"></span></div>
            <div class="flex justify-between text-amber-700" x-show="balance>0"><span>Balance remaining</span><span x-text="money(balance)"></span></div>
            <div class="flex justify-between text-emerald-700" x-show="change>0"><span>Change to give</span><span x-text="money(change)"></span></div>
        </div>

        <button type="button" @click="checkout()" :disabled="busy || cart.length===0"
                class="w-full rounded-xl bg-stone-900 text-white py-3.5 text-base font-medium disabled:opacity-50">
            <span x-show="!busy">Complete sale</span><span x-show="busy" x-cloak>Saving sale...</span>
        </button>
    </section>
</div>

<script>
function pos() {
    const SYM = @json($sym);
    return {
        q: '', cat: '', results: [], cart: [], customers: [], customerId: '', newName: '', newPhone: '',
        discType: 'none', discValue: 0, payments: [{ method: 'cash', amount: '', provider: '', reference: '' }],
        dueDate: '', notes: '', busy: false, error: '', timer: null,
        init() { this.search(); this.$refs.q.focus(); },
        async search() {
            const url = @json(route('pos.search')) + '?q=' + encodeURIComponent(this.q) + '&category=' + this.cat;
            try { const r = await fetch(url, { headers: { 'Accept': 'application/json' } }); this.results = (await r.json()).data; }
            catch (e) { this.error = 'Unable to search. Check your connection.'; }
        },
        typed() { clearTimeout(this.timer); this.timer = setTimeout(() => this.search(), 250); },
        async enter() {
            clearTimeout(this.timer); await this.search();
            const q = this.q.trim().toLowerCase(); if (!q) return;
            const hit = this.results.find(v => (v.barcode || '').toLowerCase() === q || v.sku.toLowerCase() === q)
                     || (this.results.length === 1 ? this.results[0] : null);
            if (hit) { this.add(hit); this.q = ''; this.search(); }
        },
        add(v) {
            if (v.stock <= 0) { this.error = 'That item is out of stock.'; return; }
            const l = this.cart.find(c => c.id === v.id);
            if (l) { if (l.qty >= v.stock) { this.error = 'Only ' + v.stock + ' in stock.'; return; } l.qty++; }
            else this.cart.push({ id: v.id, name: v.name, label: v.label, price: v.price, stock: v.stock, qty: 1 });
            this.error = '';
        },
        inc(l, d) { const n = l.qty + d; if (n < 1) return this.remove(l); if (n > l.stock) { this.error = 'Only ' + l.stock + ' in stock.'; return; } l.qty = n; this.error = ''; },
        remove(l) { this.cart = this.cart.filter(c => c !== l); },
        get subtotal() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },
        get discount() {
            const v = parseFloat(this.discValue) || 0;
            if (this.discType === 'percent') return Math.min(this.subtotal * Math.min(v, 100) / 100, this.subtotal);
            if (this.discType === 'fixed') return Math.min(v, this.subtotal);
            return 0;
        },
        get total() { return Math.round((this.subtotal - this.discount) * 100) / 100; },
        get paid() { return Math.round(this.payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0) * 100) / 100; },
        get balance() { return Math.max(0, Math.round((this.total - this.paid) * 100) / 100); },
        get change() { return Math.max(0, Math.round((this.paid - this.total) * 100) / 100); },
        money(n) { return SYM + ' ' + Number(n).toFixed(2); },
        fillRemaining(p) {
            const others = this.paid - (parseFloat(p.amount) || 0);
            p.amount = Math.max(0, this.total - others).toFixed(2);
        },
        async checkout() {
            this.error = '';
            if (!this.cart.length) { this.error = 'The cart is empty.'; return; }
            this.busy = true;
            const body = {
                items: this.cart.map(l => ({ variation_id: l.id, qty: l.qty })),
                customer_id: this.customerId || null,
                new_customer: { name: this.newName, phone: this.newPhone },
                discount_type: this.discType, discount_value: this.discValue || 0,
                payments: this.payments.filter(p => parseFloat(p.amount) > 0),
                due_date: this.dueDate || null, notes: this.notes,
            };
            try {
                const r = await fetch(@json(route('pos.checkout')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(body),
                });
                const d = await r.json();
                if (!r.ok) {
                    this.error = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Unable to complete the sale. Please try again.');
                    this.busy = false; return;
                }
                window.location = d.receipt_url;
            } catch (e) { this.error = 'Unable to complete the sale. Please try again.'; this.busy = false; }
        },
    };
}
</script>
@endsection
