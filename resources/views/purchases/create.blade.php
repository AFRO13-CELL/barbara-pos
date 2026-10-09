@extends('layouts.boutique')
@section('title', 'New Purchase')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; $sym = \App\Models\Setting::get('currency_symbol', '₵'); @endphp
<div x-data="purchase()" x-init="init()" class="grid lg:grid-cols-5 gap-4">
    <section class="lg:col-span-3 space-y-3">
        <div x-show="error" x-cloak class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm" x-text="error"></div>
        <div class="grid sm:grid-cols-2 gap-2">
            <label class="text-xs">Supplier
                <select x-model="supplierId" class="{{ $inp }} w-full"><option value="">Choose supplier...</option>
                    @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></label>
            <label class="text-xs">Purchase date<input type="date" x-model="date" class="{{ $inp }} w-full"></label>
        </div>
        <input x-model="q" @input.debounce.250ms="search()" type="search" placeholder="Search the product to buy: name, SKU, barcode, size, color..." class="w-full rounded-xl border-2 border-stone-300 focus:border-amber-500 px-4 py-3 text-sm">
        <div class="grid sm:grid-cols-2 gap-2">
            <template x-for="v in results" :key="v.id">
                <button type="button" @click="add(v)" class="text-left bg-white border border-stone-200 rounded-xl p-3 hover:border-amber-400">
                    <div class="font-medium text-sm" x-text="v.name"></div>
                    <div class="text-xs text-stone-500"><span x-text="v.label"></span> &middot; <span x-text="v.sku"></span> &middot; in stock <span x-text="v.stock"></span></div>
                </button>
            </template>
        </div>
        <p class="text-xs text-stone-500">New products and sizes are created first on the Products page, then bought here.</p>
    </section>
    <section class="lg:col-span-2 bg-white border border-stone-200 rounded-xl p-4 space-y-3 h-fit">
        <h2 class="font-semibold">Items</h2>
        <p x-show="lines.length===0" class="text-sm text-stone-500">Pick products from the left.</p>
        <template x-for="l in lines" :key="l.id">
            <div class="border-b border-stone-100 pb-2">
                <div class="flex justify-between gap-2"><div class="text-sm font-medium" x-text="l.name + ' - ' + l.label"></div>
                    <button type="button" @click="lines = lines.filter(x => x !== l)" class="text-stone-400 hover:text-red-600">&times;</button></div>
                <div class="grid grid-cols-3 gap-2 mt-1 items-end">
                    <label class="text-xs">Qty<input type="number" min="1" x-model.number="l.qty" class="{{ $inp }} w-full"></label>
                    <label class="text-xs">Cost each<input type="number" min="0" step="0.01" x-model.number="l.cost" class="{{ $inp }} w-full"></label>
                    <div class="text-sm text-right font-medium" x-text="money(l.qty * l.cost)"></div>
                </div>
            </div>
        </template>
        <div class="flex justify-between text-lg font-semibold"><span>Total</span><span x-text="money(total)"></span></div>

        <div class="space-y-2 border-t border-stone-200 pt-3">
            <div class="text-sm font-medium">Payment to supplier now (optional)</div>
            <div class="grid grid-cols-2 gap-2">
                <input type="number" min="0" step="0.01" x-model="payAmount" placeholder="Amount paid" class="{{ $inp }}">
                <select x-model="payMethod" class="{{ $inp }}"><option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option></select>
            </div>
            <input x-show="payMethod!=='cash'" x-model="payRef" placeholder="Payment reference" class="{{ $inp }} w-full">
            <div class="text-sm text-amber-700" x-show="owed>0">Still owed to supplier: <span x-text="money(owed)"></span></div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="receiveNow"> Stock has arrived: add it to inventory now</label>
        <label class="flex items-center gap-2 text-sm" x-show="receiveNow"><input type="checkbox" x-model="updateCost"> Update each product's cost price to this cost</label>
        <button type="button" @click="save()" :disabled="busy || lines.length===0" class="w-full rounded-xl bg-stone-900 text-white py-3 disabled:opacity-50">
            <span x-show="!busy">Save purchase</span><span x-show="busy" x-cloak>Saving purchase...</span></button>
    </section>
</div>
<script>
function purchase() {
    const SYM = @json($sym);
    return {
        supplierId: @json($selected ? (string) $selected : ''), date: new Date().toISOString().slice(0, 10), q: '', results: [], lines: [],
        payAmount: '', payMethod: 'cash', payRef: '', receiveNow: true, updateCost: true, busy: false, error: '',
        init() { this.search(); },
        async search() {
            try { const r = await fetch(@json(route('purchases.search')) + '?q=' + encodeURIComponent(this.q), { headers: { 'Accept': 'application/json' } }); this.results = (await r.json()).data; }
            catch (e) { this.error = 'Unable to search. Check your connection.'; }
        },
        add(v) { const l = this.lines.find(x => x.id === v.id); if (l) l.qty++; else this.lines.push({ id: v.id, name: v.name, label: v.label, qty: 1, cost: v.cost }); },
        get total() { return Math.round(this.lines.reduce((s, l) => s + (l.qty || 0) * (l.cost || 0), 0) * 100) / 100; },
        get owed() { return Math.max(0, Math.round((this.total - (parseFloat(this.payAmount) || 0)) * 100) / 100); },
        money(n) { return SYM + ' ' + Number(n || 0).toFixed(2); },
        async save() {
            this.error = '';
            if (!this.supplierId) { this.error = 'Choose a supplier.'; return; }
            this.busy = true;
            const body = { supplier_id: this.supplierId, purchase_date: this.date,
                items: this.lines.map(l => ({ variation_id: l.id, qty: l.qty, cost: l.cost })),
                pay_amount: parseFloat(this.payAmount) || 0, pay_method: this.payMethod, pay_reference: this.payRef,
                receive_now: this.receiveNow, update_cost: this.receiveNow && this.updateCost };
            try {
                const r = await fetch(@json(route('purchases.store')), { method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify(body) });
                const d = await r.json();
                if (!r.ok) { this.error = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Unable to save the purchase. Please try again.'); this.busy = false; return; }
                window.location = d.url;
            } catch (e) { this.error = 'Unable to save the purchase. Please try again.'; this.busy = false; }
        },
    };
}
</script>
@endsection
