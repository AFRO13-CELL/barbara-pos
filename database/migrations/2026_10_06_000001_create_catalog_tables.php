<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('sizes', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('colors', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('hex', 9)->nullable();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name')->index();
            $t->text('description')->nullable();
            $t->string('image_path')->nullable();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('product_variations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku')->unique();
            $t->string('barcode')->nullable()->unique();
            $t->string('size')->nullable();
            $t->string('color')->nullable();
            $t->string('material')->nullable();
            $t->string('style')->nullable();
            $t->string('gender')->nullable();
            $t->string('item_condition')->nullable();
            $t->decimal('cost_price', 12, 2)->default(0);
            $t->decimal('selling_price', 12, 2)->default(0);
            $t->decimal('discount_price', 12, 2)->nullable();
            $t->integer('stock_qty')->default(0)->index();
            $t->integer('min_stock')->default(3);
            $t->string('image_path')->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['size', 'color']);
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_variation_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type')->index();           // initial, purchase, sale, return, damage, adjustment
            $t->integer('quantity');                // signed
            $t->integer('previous_stock');
            $t->integer('new_stock');
            $t->string('reason')->nullable();
            $t->text('note')->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('sizes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
