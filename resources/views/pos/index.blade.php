{{-- =====================================================================
     POS / NEW SALE PAGE (responsive)
     ---------------------------------------------------------------------
     HOW THIS PAGE WORKS
       1. Staff search for or scan a product and tap it to add it to the cart.
       2. They choose a customer (optional), a discount (optional) and how
          the customer is paying (one or several payment methods).
       3. "Complete sale" sends everything to the server. The SERVER checks
          prices, stock and discount limits again, saves the sale, and sends
          back the receipt page.

     READING THE CSS CLASSES (Tailwind)
       Classes with no prefix apply on phones.
       "md:"  = tablets and bigger  (768px and wider)
       "lg:"  = laptops and bigger  (1024px and wider)
       "xl:"  = large screens       (1280px and wider)
       Example: "grid-cols-2 sm:grid-cols-3" = 2 columns on a phone, 3 on a bigger one.

     LAYOUT
       Phone:            products fill the screen; a bar at the bottom shows the
                         cart total; tapping it slides the cart up from the bottom.
       Tablet/computer:  products on the left, cart always visible on the right.
     ===================================================================== --}}
@extends('layouts.boutique')
@section('title', 'POS / New Sale')
@section('content')
@php
    // Currency symbol from Settings (for example the Ghana cedi sign). Used in the JavaScript below.
    $sym = \App\Models\Setting::get('currency_symbol', '₵');

    // Shared look for text boxes and drop-downs.
    // "text-base" = 16px text on phones. Smaller text makes iPhones zoom in when you tap a field.
    $inp = 'rounded-lg border-stone-300 text-base sm:text-sm py-2.5';
@endphp

{{-- Hides anything marked x-cloak until the page's scripts have started.
     Without this, the cart panel could flash on screen for a moment while loading. --}}
<style>[x-cloak] { display: none !important; }</style>

{{-- x-data="pos()"  : connects this whole block to the pos() function in the script at the bottom.
     x-init          : runs init() once when the page opens.
     @keydown.escape : pressing Esc closes the cart panel.
     "pb-28"         : extra space at the bottom on phones so the bottom bar never covers the last product. --}}
<div x-data="pos()" x-init="init()" @keydown.escape.window="sheet = false"
     class="md:grid md:grid-cols-5 md:gap-4 pb-28 md:pb-0">

    {{-- =================================================================
         LEFT SIDE: search box, category chips, and the product cards
         (takes 3 of the 5 columns on tablets and computers)
         ================================================================= --}}
    <section class="md:col-span-3 space-y-2">

        {{-- This strip stays visible under the header while you scroll through products ("sticky") --}}
        <div class="sticky top-[4rem] z-10 bg-stone-50 -mx-4 px-4 py-2 lg:-mx-6 lg:px-6 space-y-2">

            {{-- SEARCH / SCAN BOX
                 x-model="q"      : whatever is typed is stored in the variable q
                 @input="typed()" : after each keystroke, search again (with a short delay)
                 @keydown.enter   : Enter (or a barcode scanner finishing) adds the exact match to the cart --}}
            <input x-ref="q" x-model="q" @input="typed()" @keydown.enter.prevent="enter()" type="search" autocomplete="off"
                   inputmode="search" enterkeyhint="search"
                   placeholder="Search or scan: name, SKU, barcode, size, color..."
                   class="w-full rounded-xl border-2 border-stone-300 focus:border-amber-500 text-base px-4 py-3.5">

            {{-- CATEGORY CHIPS: tap one to show only that category. On a phone, swipe sideways for more. --}}
            <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 lg:mx-0 lg:px-0 snap-x">
                {{-- "All" clears the category filter. The dark chip is the one currently selected. --}}
                <button type="button" @click="cat=''; search()" :class="cat==='' ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300'"
                        class="snap-start shrink-0 rounded-full px-4 py-2.5 text-sm min-h-[44px]">All</button>
                {{-- One chip per category from the database --}}
                @foreach ($categories as $c)
                <button type="button" @click="cat='{{ $c->id }}'; search()" :class="cat==='{{ $c->id }}' ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300'"
                        class="snap-start shrink-0 rounded-full px-4 py-2.5 text-sm min-h-[44px]">{{ $c->name }}</button>
                @endforeach
            </div>
        </div>

        {{-- ERROR MESSAGE (phones only): shown here when the cart panel is closed,
             so staff can still see problems such as "out of stock" --}}
        <div x-show="error && !sheet" x-cloak class="md:hidden rounded-lg bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm" x-text="error"></div>

        {{-- PRODUCT CARDS
             Columns: 2 on phones, 3 on small tablets, 2 when beside the cart (md),
             then 3 and 4 on bigger screens. One card is drawn for every item in "results". --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
            <template x-for="v in results" :key="v.id">
                {{-- Tapping a card adds it to the cart. Out-of-stock items are greyed out and cannot be tapped. --}}
                <button type="button" @click="add(v)" :disabled="v.stock<=0"
                        class="text-left bg-white border border-stone-200 rounded-xl p-3 min-h-[92px] flex flex-col justify-between hover:border-amber-400 disabled:opacity-50 active:scale-[0.97] active:border-amber-500 transition">
                    <div>
                        <div class="font-medium text-sm leading-tight" x-text="v.name"></div>   {{-- product name --}}
                        <div class="text-xs text-stone-500 mt-0.5" x-text="v.label"></div>      {{-- size / color --}}
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-1">
                        <span class="font-semibold text-sm" x-text="money(v.price)"></span>    {{-- selling price --}}
                        {{-- Stock badge: red "Out" when none left, otherwise "5 left" --}}
                        <span class="text-xs rounded-full px-2 py-0.5 whitespace-nowrap" :class="v.stock<=0 ? 'bg-red-100 text-red-700' : 'bg-stone-100 text-stone-600'" x-text="v.stock<=0 ? 'Out' : v.stock + ' left'"></span>
                    </div>
                </button>
            </template>
        </div>
        {{-- Shown only when the search finds nothing --}}
        <p x-show="results.length===0" class="text-sm text-stone-500 py-6 text-center">No matching items.</p>
    </section>

    {{-- PHONE ONLY: dark backdrop behind the open cart panel. Tapping it closes the cart. --}}
    <div x-show="sheet" x-cloak x-transition.opacity @click="sheet=false" class="fixed inset-0 z-40 bg-black/50 md:hidden"></div>

    {{-- =================================================================
         RIGHT SIDE: the cart, customer, discount and payment
         PHONE:            a panel that slides up from the bottom of the screen.
                           "translate-y-full invisible" = pushed off-screen and hidden;
                           "translate-y-0 visible"      = slid into view (when sheet is true).
         TABLET/COMPUTER:  the "md:" classes switch it to a normal column that
                           sticks in view while you scroll the products.
         ================================================================= --}}
    <section x-cloak
             :class="sheet ? 'translate-y-0 visible' : 'translate-y-full invisible'"
             class="fixed inset-x-0 bottom-0 z-50 max-h-[92vh] overflow-y-auto overscroll-contain
                    bg-white rounded-t-2xl border-t border-stone-200 shadow-2xl
                    px-4 pt-2 pb-[calc(1rem+env(safe-area-inset-bottom))] space-y-4
                    transition-[transform,visibility] duration-200
                    md:translate-y-0 md:visible md:static md:z-auto md:col-span-2 md:rounded-xl md:border md:shadow-none
                    md:sticky md:top-20 md:self-start md:max-h-[calc(100vh-6rem)] md:p-4">
        {{-- Note: "env(safe-area-inset-bottom)" keeps buttons clear of the iPhone's bottom swipe bar. --}}

        {{-- PANEL HEADER (phones only): small grab handle, title with item count, and a Close button --}}
        <div class="md:hidden sticky top-0 z-10 -mx-4 px-4 pt-1 pb-2 bg-white border-b border-stone-100">
            <div class="mx-auto h-1.5 w-10 rounded-full bg-stone-300 mb-2"></div>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-lg">Cart <span class="text-stone-500 font-normal text-sm" x-text="'(' + count + ' items)'"></span></h2>
                <button type="button" @click="sheet=false" class="rounded-lg border border-stone-300 px-4 py-2.5 text-sm">Close</button>
            </div>
        </div>

        {{-- ERROR MESSAGE inside the cart (computers, and phones while the cart is open) --}}
        <div x-show="error" x-cloak class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm" x-text="error"></div>

        {{-- ------------------ CART LINES ------------------ --}}
        <div>
            <h2 class="font-semibold mb-2 hidden md:block">Cart</h2>
            <p x-show="cart.length===0" class="text-sm text-stone-500">Tap a product or scan a barcode to add it.</p>
            {{-- One block per item in the cart --}}
            <template x-for="l in cart" :key="l.id">
                <div class="py-2 border-b border-stone-100">
                    {{-- Row 1: item name, size/color, price each, and the remove (x) button --}}
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate" x-text="l.name"></div>
                            <div class="text-xs text-stone-500"><span x-text="l.label"></span> &middot; <span x-text="money(l.price)"></span> each</div>
                        </div>
                        <button type="button" @click="remove(l)" class="w-11 h-11 -mt-1 -mr-2 shrink-0 text-2xl text-stone-400 hover:text-red-600" aria-label="Remove item">&times;</button>
                    </div>
                    {{-- Row 2: minus / quantity / plus, and the line total. 44px buttons are easy to tap. --}}
                    <div class="mt-1 flex items-center justify-between">
                        <div class="flex items-center gap-1">
                            <button type="button" @click="inc(l,-1)" class="w-11 h-11 rounded-lg border border-stone-300 text-xl active:bg-stone-100" aria-label="Less">&minus;</button>
                            <span class="w-9 text-center font-medium" x-text="l.qty"></span>
                            <button type="button" @click="inc(l,1)" class="w-11 h-11 rounded-lg border border-stone-300 text-xl active:bg-stone-100" aria-label="More">+</button>
                        </div>
                        <div class="font-semibold" x-text="money(l.price*l.qty)"></div>
                    </div>
                </div>
            </template>
        </div>

        {{-- ------------------ CUSTOMER ------------------
             Pick an existing customer, or leave it as walk-in. If no customer is picked,
             two boxes appear to add a new one. A customer is required for credit
             (not fully paid) sales. --}}
        <div class="space-y-2">
            <label class="text-xs block">Customer (optional for fully paid sales)
                <select x-model="customerId" class="{{ $inp }} w-full mt-1">
                    <option value="">Walk-in customer</option>
                    @foreach ($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' - '.$c->phone : '' }}</option>@endforeach
                </select></label>
            <div class="grid grid-cols-2 gap-2" x-show="!customerId">
                <input x-model="newName" placeholder="New customer name" class="{{ $inp }}">
                <input x-model="newPhone" placeholder="Phone" inputmode="tel" class="{{ $inp }}">
            </div>
        </div>

        {{-- ------------------ DISCOUNT ------------------
             Choose none, a percentage, or a fixed amount. The server enforces each person's
             limit (set on the Staff page), so a too-large discount is refused when completing the sale. --}}
        <div class="flex flex-wrap gap-2 items-center">
            <select x-model="discType" class="{{ $inp }} flex-1 min-w-[9rem]">
                <option value="none">No discount</option><option value="percent">Discount %</option><option value="fixed">Discount {{ $sym }}</option>
            </select>
            <input x-show="discType!=='none'" x-model="discValue" type="number" inputmode="decimal" min="0" step="0.01" class="{{ $inp }} w-28">
            <span class="text-xs text-stone-500 basis-full" x-show="discType!=='none'">Your limit: {{ $maxDiscount }}%</span>
        </div>

        {{-- ------------------ PAYMENTS ------------------
             Each payment is its own small box, so a customer can pay with several methods
             (mixed payment, for example cash plus Mobile Money). --}}
        <div class="space-y-2">
            <div class="text-sm font-medium">Payment</div>
            <template x-for="(p,i) in payments" :key="i">
                <div class="rounded-xl border border-stone-200 p-2 space-y-2">
                    {{-- Method drop-down, plus a remove button when there is more than one payment --}}
                    <div class="flex gap-2">
                        <select x-model="p.method" class="{{ $inp }} flex-1">
                            <option value="cash">Cash</option><option value="mobile_money">Mobile Money</option>
                            <option value="card">Card</option><option value="bank_transfer">Bank Transfer</option><option value="store_credit">Store credit</option>
                        </select>
                        <button type="button" x-show="payments.length>1" @click="payments.splice(i,1)" class="w-11 shrink-0 rounded-lg border border-stone-300 text-xl text-stone-500" aria-label="Remove payment">&times;</button>
                    </div>
                    {{-- Amount, with a "Rest" button that fills in whatever is still unpaid --}}
                    <div class="flex gap-2">
                        <input x-model="p.amount" type="number" inputmode="decimal" min="0" step="0.01" placeholder="Amount" class="{{ $inp }} flex-1 min-w-0">
                        <button type="button" @click="fillRemaining(p)" class="shrink-0 rounded-lg border border-stone-300 px-4 text-sm active:bg-stone-100">Rest</button>
                    </div>
                    {{-- Mobile Money needs the provider (MTN, Telecel...), bank transfer needs the bank name --}}
                    <input x-show="p.method==='mobile_money' || p.method==='bank_transfer'" x-model="p.provider"
                           :placeholder="p.method==='bank_transfer' ? 'Bank name' : 'Provider (MTN, Telecel, AT)'" class="{{ $inp }} w-full">
                    {{-- Every method except cash and store credit needs a transaction reference --}}
                    <input x-show="p.method!=='cash' && p.method!=='store_credit'" x-model="p.reference" placeholder="Reference" class="{{ $inp }} w-full">
                </div>
            </template>
            <button type="button" @click="payments.push({method:'cash',amount:'',provider:'',reference:''})" class="text-sm underline py-2">+ Add another payment method (mixed payment)</button>
        </div>

        {{-- ------------------ CREDIT SALE ------------------
             Appears only when the customer has paid less than the total.
             Staff who are not allowed to sell on credit see a warning instead. --}}
        <div x-show="balance>0" x-cloak class="space-y-1">
            @if ($canCredit)
            <label class="text-xs block">Unpaid balance is credit. Due date<input x-model="dueDate" type="date" class="{{ $inp }} w-full mt-1"></label>
            @else
            <p class="text-xs text-red-700">The sale must be paid in full. You are not allowed to sell on credit.</p>
            @endif
        </div>

        {{-- ------------------ TOTALS ------------------
             All figures update live. "Change to give" appears when the customer hands over
             more cash than the total. --}}
        <div class="border-t border-stone-200 pt-3 space-y-1 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span x-text="money(subtotal)"></span></div>
            <div class="flex justify-between text-stone-500" x-show="discount>0"><span>Discount</span><span x-text="'- ' + money(discount)"></span></div>
            <div class="flex justify-between text-xl font-semibold"><span>Total</span><span x-text="money(total)"></span></div>
            <div class="flex justify-between"><span>Paid</span><span x-text="money(paid)"></span></div>
            <div class="flex justify-between text-amber-700" x-show="balance>0"><span>Balance remaining</span><span x-text="money(balance)"></span></div>
            <div class="flex justify-between text-emerald-700 text-base font-medium" x-show="change>0"><span>Change to give</span><span x-text="money(change)"></span></div>
        </div>

        {{-- COMPLETE SALE BUTTON
             On a phone it stays pinned to the bottom of the cart panel while the rest scrolls.
             It is disabled while saving or when the cart is empty. --}}
        <div class="sticky bottom-0 -mx-4 px-4 pt-2 pb-1 bg-white border-t border-stone-100 md:static md:mx-0 md:px-0 md:border-0 md:pt-0 md:pb-0">
            <button type="button" @click="checkout()" :disabled="busy || cart.length===0"
                    class="w-full rounded-xl bg-stone-900 text-white py-4 text-lg font-medium disabled:opacity-50 active:bg-stone-700">
                <span x-show="!busy">Complete sale</span><span x-show="busy" x-cloak>Saving sale...</span>
            </button>
        </div>
    </section>

    {{-- =================================================================
         PHONE ONLY: BOTTOM BAR
         Always shows how many items are in the cart and the total.
         Tapping it opens the cart panel. It hides itself while the panel is open.
         ================================================================= --}}
    <div x-show="!sheet" class="fixed inset-x-0 bottom-0 z-30 md:hidden bg-white border-t border-stone-200 px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(0,0,0,0.08)]">
        <button type="button" @click="sheet=true" class="w-full flex items-center justify-between rounded-xl bg-stone-900 text-white px-4 py-3.5 active:bg-stone-700">
            <span class="flex items-center gap-2">
                {{-- Gold circle with the number of items --}}
                <span class="grid place-items-center min-w-[1.75rem] h-7 px-1 rounded-full bg-amber-400 text-stone-900 text-sm font-bold" x-text="count"></span>
                <span class="font-medium">View cart</span>
            </span>
            <span class="font-semibold" x-text="money(total)"></span>
        </button>
    </div>

    {{-- POP-UP MESSAGE ("Added: Black Dress (M / Black)").
         On a phone the cart is closed, so this confirms the tap worked. It disappears after a moment. --}}
    <div x-show="toast" x-cloak x-transition.opacity
         class="fixed left-1/2 -translate-x-1/2 bottom-24 md:bottom-6 z-[60] max-w-[90vw] truncate rounded-full bg-stone-900 text-white text-sm px-4 py-2 shadow-lg" x-text="toast"></div>
</div>

{{-- =====================================================================
     THE SCRIPT: the "brain" of the page
     pos() holds all the information (the cart, payments, etc.) and the actions
     (search, add item, complete sale). The page above reads from it and
     updates itself automatically whenever something in it changes.
     ===================================================================== --}}
<script>
function pos() {
    // Currency symbol from Settings, used when showing money amounts
    const SYM = @json($sym);

    return {
        // ------------------- INFORMATION THE PAGE KEEPS TRACK OF -------------------
        q: '',                 // what is typed in the search box
        cat: '',               // the selected category ('' = all)
        results: [],           // products found by the latest search
        cart: [],              // items added to the sale
        customers: [],         // (not used: the customer list is drawn by the server above)
        customerId: '',        // chosen customer ('' = walk-in)
        newName: '', newPhone: '',   // details for a brand-new customer
        discType: 'none',      // none, percent or fixed
        discValue: 0,          // the discount number typed in
        payments: [{ method: 'cash', amount: '', provider: '', reference: '' }],   // how the customer is paying
        dueDate: '',           // due date for unpaid (credit) balances
        notes: '',
        busy: false,           // true while the sale is being saved
        error: '',             // message shown when something is wrong
        timer: null,           // used to delay searching until typing pauses
        sheet: false,          // is the cart panel open? (phones only)
        toast: '', toastTimer: null,   // the small "Added: ..." pop-up

        // ------------------- WHEN THE PAGE OPENS -------------------
        init() {
            this.search();     // load the first products straight away
            // Put the cursor in the search box only on tablets and computers.
            // On a phone, doing this would pop the keyboard open immediately.
            if (window.matchMedia('(min-width: 768px)').matches) this.$refs.q.focus();
            // Stop the page behind the cart panel from scrolling while the panel is open on a phone
            this.$watch('sheet', open => document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 768));
        },

        // ------------------- SEARCHING -------------------
        // Asks the server for products matching the search text and the selected category
        async search() {
            const url = @json(route('pos.search')) + '?q=' + encodeURIComponent(this.q) + '&category=' + this.cat;
            try { const r = await fetch(url, { headers: { 'Accept': 'application/json' } }); this.results = (await r.json()).data; }
            catch (e) { this.error = 'Unable to search. Check your connection.'; }
        },
        // Waits 250 milliseconds after the last keystroke before searching (saves needless requests)
        typed() { clearTimeout(this.timer); this.timer = setTimeout(() => this.search(), 250); },
        // Enter key or barcode scanner: if the text matches one barcode or SKU exactly
        // (or only one product was found), add it straight to the cart and clear the box
        async enter() {
            clearTimeout(this.timer); await this.search();
            const q = this.q.trim().toLowerCase(); if (!q) return;
            const hit = this.results.find(v => (v.barcode || '').toLowerCase() === q || v.sku.toLowerCase() === q)
                     || (this.results.length === 1 ? this.results[0] : null);
            if (hit) { this.add(hit); this.q = ''; this.search(); }
        },

        // ------------------- THE CART -------------------
        // Adds one of the product to the cart (refuses if none are in stock)
        add(v) {
            if (v.stock <= 0) { this.error = 'That item is out of stock.'; return; }
            const l = this.cart.find(c => c.id === v.id);
            if (l) {
                // Already in the cart: add one more, but never more than the stock available
                if (l.qty >= v.stock) { this.error = 'Only ' + v.stock + ' in stock.'; return; }
                l.qty++;
            } else {
                this.cart.push({ id: v.id, name: v.name, label: v.label, price: v.price, stock: v.stock, qty: 1 });
            }
            this.error = '';
            this.say('Added: ' + v.name + ' (' + v.label + ')');
        },
        // Shows the small pop-up message for one and a half seconds
        say(msg) { this.toast = msg; clearTimeout(this.toastTimer); this.toastTimer = setTimeout(() => this.toast = '', 1500); },
        // + and - buttons. Going below 1 removes the item; going above the stock is refused.
        inc(l, d) {
            const n = l.qty + d;
            if (n < 1) return this.remove(l);
            if (n > l.stock) { this.error = 'Only ' + l.stock + ' in stock.'; return; }
            l.qty = n; this.error = '';
        },
        // Removes an item. If the cart becomes empty, the phone panel closes by itself.
        remove(l) { this.cart = this.cart.filter(c => c !== l); if (!this.cart.length) this.sheet = false; },

        // ------------------- TOTALS (recalculated automatically) -------------------
        get count() { return this.cart.reduce((s, l) => s + l.qty, 0); },                 // number of items in the cart
        get subtotal() { return this.cart.reduce((s, l) => s + l.price * l.qty, 0); },    // total before discount
        // The discount in money. It can never be more than the subtotal, and a percentage can never exceed 100.
        get discount() {
            const v = parseFloat(this.discValue) || 0;
            if (this.discType === 'percent') return Math.min(this.subtotal * Math.min(v, 100) / 100, this.subtotal);
            if (this.discType === 'fixed') return Math.min(v, this.subtotal);
            return 0;
        },
        get total() { return Math.round((this.subtotal - this.discount) * 100) / 100; },                                       // amount to pay
        get paid() { return Math.round(this.payments.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0) * 100) / 100; },    // all payments added up
        get balance() { return Math.max(0, Math.round((this.total - this.paid) * 100) / 100); },    // still unpaid (becomes credit)
        get change() { return Math.max(0, Math.round((this.paid - this.total) * 100) / 100); },     // overpaid: change to give back
        money(n) { return SYM + ' ' + Number(n).toFixed(2); },                                      // formats a number as money
        // The "Rest" button: fills this payment with whatever is still unpaid after the other payments
        fillRemaining(p) {
            const others = this.paid - (parseFloat(p.amount) || 0);
            p.amount = Math.max(0, this.total - others).toFixed(2);
        },

        // ------------------- COMPLETING THE SALE -------------------
        // Sends the cart to the server. The server does not trust these numbers: it looks up
        // every price and cost itself, re-checks stock and discount limits, saves everything
        // in one safe step, and replies with the address of the receipt page.
        async checkout() {
            this.error = '';
            if (!this.cart.length) { this.error = 'The cart is empty.'; return; }
            this.busy = true;      // disables the button so the sale cannot be sent twice
            const body = {
                items: this.cart.map(l => ({ variation_id: l.id, qty: l.qty })),   // only the item and quantity are sent, never prices
                customer_id: this.customerId || null,
                new_customer: { name: this.newName, phone: this.newPhone },
                discount_type: this.discType, discount_value: this.discValue || 0,
                payments: this.payments.filter(p => parseFloat(p.amount) > 0),     // ignore empty payment boxes
                due_date: this.dueDate || null, notes: this.notes,
            };
            try {
                const r = await fetch(@json(route('pos.checkout')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },   // security token
                    body: JSON.stringify(body),
                });
                const d = await r.json();
                if (!r.ok) {
                    // The server refused (not enough stock, discount too big, etc.): show its reason
                    this.error = d.errors ? Object.values(d.errors).flat().join(' ') : (d.message || 'Unable to complete the sale. Please try again.');
                    this.busy = false; return;
                }
                window.location = d.receipt_url;    // success: go to the receipt
            } catch (e) {
                // No internet or the server did not answer
                this.error = 'Unable to complete the sale. Please try again.'; this.busy = false;
            }
        },
    };
}
</script>
@endsection
