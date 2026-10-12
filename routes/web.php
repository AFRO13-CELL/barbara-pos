
<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| TEMPORARY OWNER ACCOUNT SETUP
|--------------------------------------------------------------------------
| Remove these routes after the Owner account has been created.
*/

Route::get('/owner-setup', function () {
    abort_unless(config('services.owner_setup.token'), 404);

    return response(
        '<!DOCTYPE html><html><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Owner Setup</title></head><body>'
        . '<h2>Owner Account Setup</h2>'
        . '<form method="POST" action="/owner-setup">'
        . '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">'
        . '<label>Setup token: '
        . '<input type="password" name="token" required autocomplete="off">'
        . '</label> '
        . '<button type="submit">Run Owner Setup</button>'
        . '</form></body></html>'
    );
})->middleware('throttle:3,1');

Route::post('/owner-setup', function (Request $request) {
    $expected = (string) config('services.owner_setup.token');
    $provided = (string) $request->input('token', '');

    abort_unless(
        $expected !== '' && hash_equals($expected, $provided),
        403
    );

    Artisan::call('db:seed', [
        '--class' => \Database\Seeders\OwnerSeeder::class,
        '--force' => true,
    ]);

    return response('Owner setup command completed. Check the deployment logs before logging in.');
})->middleware('throttle:3,1');

require __DIR__.'/auth.php';

require __DIR__.'/boutique.php';
