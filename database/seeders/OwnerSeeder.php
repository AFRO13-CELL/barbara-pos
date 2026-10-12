
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('services.owner_setup.email');
        $password = config('services.owner_setup.password');

        if (!$email || !$password) {
            $this->command->error(
                'OWNER_EMAIL and OWNER_PASSWORD must be configured.'
            );
            return;
        }

        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['label' => 'Owner']
        );

        $user = User::withTrashed()
            ->where('email', $email)
            ->first();

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
        } else {
            $user = new User();
            $user->email = $email;
        }

        $user->name = 'RUBBENTECH';
        $user->password = Hash::make($password);
        $user->role_id = $ownerRole->id;
        $user->is_active = true;
        $user->max_discount_percent = 100;
        $user->save();

        cache()->forget("user.{$user->id}.perms");

        $this->command->info(
            'Owner account has been created or updated.'
        );
    }
}
