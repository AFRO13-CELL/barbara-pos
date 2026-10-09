<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $t) {
            $t->decimal('refunded_amount', 12, 2)->default(0)->after('balance');
        });
        Schema::table('customers', function (Blueprint $t) {
            $t->decimal('store_credit', 12, 2)->default(0)->after('notes');
        });

        Schema::create('suppliers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('contact_person')->nullable();
            $t->string('phone')->nullable()->index();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->string('purchase_number')->unique();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->date('purchase_date')->index();
            $t->string('status')->default('ordered')->index();   // ordered, received
            $t->decimal('total', 12, 2);
            $t->decimal('amount_paid', 12, 2)->default(0);
            $t->decimal('balance', 12, 2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamp('received_at')->nullable();
            $t->timestamps();
        });
        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variation_id')->constrained()->restrictOnDelete();
            $t->string('product_name');
            $t->string('variation_label')->nullable();
            $t->string('sku');
            $t->integer('quantity');
            $t->decimal('cost_price', 12, 2);
            $t->decimal('line_total', 12, 2);
            $t->timestamps();
        });
        Schema::create('purchase_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('method');
            $t->decimal('amount', 12, 2);
            $t->string('reference')->nullable();
            $t->timestamps();
        });
        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->string('description')->index();
            $t->string('category')->index();
            $t->decimal('amount', 12, 2);
            $t->date('expense_date')->index();
            $t->string('payment_method');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('attachment_path')->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('returns', function (Blueprint $t) {
            $t->id();
            $t->string('return_number')->unique();
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');                                   // refund, exchange, store_credit
            $t->decimal('value', 12, 2);                          // value of goods returned
            $t->decimal('balance_reduced', 12, 2)->default(0);    // part that cancelled an unpaid balance
            $t->decimal('refund_amount', 12, 2)->default(0);      // money paid out or credit given
            $t->string('refund_method')->nullable();
            $t->string('refund_reference')->nullable();
            $t->string('reason');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('created_at');
        });
        Schema::create('return_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $t->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_variation_id')->constrained()->restrictOnDelete();
            $t->string('product_name');
            $t->string('variation_label')->nullable();
            $t->integer('quantity');
            $t->decimal('unit_price', 12, 2);
            $t->decimal('cost_price', 12, 2);
            $t->decimal('amount', 12, 2);
            $t->string('item_condition');                         // sellable, damaged
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('store_credit'));
        Schema::table('sales', fn (Blueprint $t) => $t->dropColumn('refunded_amount'));
    }
};
