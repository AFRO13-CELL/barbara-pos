<?php
use App\Http\Controllers\{AuditController, BackupController, CategoryController, CustomerController, ExpenseController, InventoryController, NotificationController, PosController, ProductController, PurchaseController, ReportController, ReturnController, SaleController, SettingsController, StaffController, SupplierController, VariationController};
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    // Products & categories
    Route::middleware('permission:products.view')->group(function () {
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
    });
    Route::middleware('permission:products.create')->group(function () {
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::post('products/{product}/duplicate', [ProductController::class, 'duplicate'])->whereNumber('product')->name('products.duplicate');
        Route::post('products/{product}/variations', [VariationController::class, 'store'])->whereNumber('product')->name('variations.store');
    });
    Route::middleware('permission:products.edit')->group(function () {
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])->whereNumber('product')->name('products.edit');
        Route::put('products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
        Route::post('products/{id}/restore', [ProductController::class, 'restore'])->whereNumber('id')->name('products.restore');
        Route::put('variations/{variation}', [VariationController::class, 'update'])->name('variations.update');
    });
    Route::middleware('permission:products.delete')->group(function () {
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::delete('products/{product}', [ProductController::class, 'archive'])->whereNumber('product')->name('products.archive');
        Route::delete('variations/{variation}', [VariationController::class, 'destroy'])->name('variations.destroy');
    });

    // Inventory
    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    });
    Route::post('inventory/{variation}/adjust', [InventoryController::class, 'adjust'])
        ->middleware('permission:stock.adjust')->name('inventory.adjust');

    // POS
    Route::middleware('permission:pos.view')->group(function () {
        Route::get('pos', [PosController::class, 'index'])->name('pos.index');
        Route::get('pos/search', [PosController::class, 'search'])->name('pos.search');
    });
    Route::post('pos/checkout', [PosController::class, 'checkout'])->middleware('permission:pos.create')->name('pos.checkout');

    // Sales
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->whereNumber('sale')->name('sales.show');
    });
    Route::post('sales/{sale}/payments', [SaleController::class, 'addPayment'])->whereNumber('sale')
        ->middleware('permission:credit.edit')->name('sales.payments.store');

    // Customers
    Route::middleware('permission:customers.view')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
    });
    Route::post('customers', [CustomerController::class, 'store'])->middleware('permission:customers.create')->name('customers.store');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.edit')->name('customers.update');

    // Suppliers
    Route::middleware('permission:suppliers.view')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->whereNumber('supplier')->name('suppliers.show');
    });
    Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create')->name('suppliers.store');
    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.edit')->name('suppliers.update');

    // Purchases
    Route::middleware('permission:purchases.view')->group(function () {
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    });
    Route::middleware('permission:purchases.create')->group(function () {
        Route::get('purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::get('purchases/search', [PurchaseController::class, 'search'])->name('purchases.search');
        Route::post('purchases', [PurchaseController::class, 'store'])->name('purchases.store');
    });
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->whereNumber('purchase')->middleware('permission:purchases.view')->name('purchases.show');
    Route::middleware('permission:purchases.edit')->group(function () {
        Route::post('purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->whereNumber('purchase')->name('purchases.receive');
        Route::post('purchases/{purchase}/payments', [PurchaseController::class, 'addPayment'])->whereNumber('purchase')->name('purchases.payments.store');
    });

    // Expenses
    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('expenses/{expense}/attachment', [ExpenseController::class, 'attachment'])->whereNumber('expense')->name('expenses.attachment');
    });
    Route::post('expenses', [ExpenseController::class, 'store'])->middleware('permission:expenses.create')->name('expenses.store');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->middleware('permission:expenses.edit')->name('expenses.update');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('permission:expenses.delete')->name('expenses.destroy');

    // Returns
    Route::get('returns', [ReturnController::class, 'index'])->middleware('permission:returns.view')->name('returns.index');
    Route::middleware('permission:returns.create')->group(function () {
        Route::get('returns/create', [ReturnController::class, 'create'])->name('returns.create');
        Route::get('returns/find', [ReturnController::class, 'find'])->name('returns.find');
        Route::post('returns', [ReturnController::class, 'store'])->name('returns.store');
    });

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');

    // Notifications
    Route::middleware('permission:notifications.view')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->whereNumber('notification')->name('notifications.read');
    });

    // Staff
    Route::get('staff', [StaffController::class, 'index'])->middleware('permission:staff.view')->name('staff.index');
    Route::post('staff', [StaffController::class, 'store'])->middleware('permission:staff.create')->name('staff.store');
    Route::put('staff/{user}', [StaffController::class, 'update'])->middleware('permission:staff.edit')->name('staff.update');
    Route::delete('staff/{user}', [StaffController::class, 'destroy'])->middleware('permission:staff.delete')->name('staff.destroy');

    // Settings, roles, audit log
    Route::get('settings', [SettingsController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
    Route::post('settings', [SettingsController::class, 'update'])->middleware('permission:settings.edit')->name('settings.update');
    Route::get('settings/roles', [SettingsController::class, 'roles'])->middleware('permission:settings.edit')->name('settings.roles');
    Route::post('settings/roles', [SettingsController::class, 'saveRoles'])->middleware('permission:settings.edit')->name('settings.roles.save');
    Route::get('audit-log', [AuditController::class, 'index'])->middleware('permission:settings.view')->name('audit.index');

    // Backups
    Route::get('backups', [BackupController::class, 'index'])->middleware('permission:backups.view')->name('backups.index');
    Route::post('backups/run', [BackupController::class, 'run'])->middleware('permission:backups.create')->name('backups.run');
    Route::post('backups/schedule', [BackupController::class, 'schedule'])->middleware('permission:backups.edit')->name('backups.schedule');
    Route::get('backups/{backup}/download', [BackupController::class, 'download'])->whereNumber('backup')->middleware('permission:backups.view')->name('backups.download');
    Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])->whereNumber('backup')->middleware('permission:backups.restore')->name('backups.restore');
});
