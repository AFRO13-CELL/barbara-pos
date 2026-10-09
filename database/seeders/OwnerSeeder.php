<?php
namespace Database\Seeders;
use App\Models\{Role, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // Set OWNER_EMAIL / OWNER_PASSWORD in .env before seeding. No default password is shipped.
        $email = env('OWNER_EMAIL');
        $pass  = env('OWNER_PASSWORD');
        if (!$email || !$pass) { $this->command->warn('Set OWNER_EMAIL and OWNER_PASSWORD in .env, then re-run.'); return; }
        User::firstOrCreate(['email' => $email], [
            'name' => 'Barbara', 'password' => Hash::make($pass),
            'role_id' => Role::where('name', 'owner')->value('id'), 'is_active' => true, 'max_discount_percent' => 100,
        ]);
    }
}
