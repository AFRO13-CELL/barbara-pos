<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('phone')->nullable()->index();
            $t->string('email')->nullable()->index();
            $t->string('address')->nullable();
            $t->text('notes')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('receipt_number')->unique();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount_percent', 5, 2)->default(0);
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->decimal('amount_paid', 12, 2)->default(0);
            $t->decimal('balance', 12, 2)->default(0);
            $t->decimal('gross_profit', 12, 2)->default(0);   // total - cost of goods, fixed at time of sale
            $t->date('due_date')->nullable()->index();
            $t->string('payment_status')->index();            // paid, partial, credit
            $t->string('status')->default('completed')->index();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('created_at');
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variation_id')->constrained()->restrictOnDelete();
            $t->string('product_name');                        // snapshots, so history never changes
            $t->string('variation_label')->nullable();
            $t->string('sku');
            $t->integer('quantity');
            $t->decimal('cost_price', 12, 2);                  // cost at time of sale
            $t->decimal('unit_price', 12, 2);
            $t->decimal('line_total', 12, 2);
            $t->decimal('line_profit', 12, 2);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('method')->index();                     // cash, mobile_money, card, bank_transfer
            $t->decimal('amount', 12, 2);
            $t->string('provider')->nullable();                // MoMo provider or bank
            $t->string('reference')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
