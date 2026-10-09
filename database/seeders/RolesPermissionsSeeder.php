<?php
namespace Database\Seeders;
use App\Models\{Permission, Role, Setting};
use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = ['products','inventory','sales','pos','customers','suppliers','purchases','expenses','returns','reports','staff','notifications','backups','settings','credit'];
        $perms = [];
        foreach ($modules as $m) {
            foreach (['view','create','edit','delete'] as $a) {
                $perms[] = Permission::firstOrCreate(['key' => "$m.$a"], ['label' => ucfirst($a).' '.$m, 'group' => $m]);
            }
        }
        foreach (['costs.view' => 'See cost prices', 'profit.view' => 'See profit figures', 'stock.adjust' => 'Adjust stock', 'backups.restore' => 'Restore backups'] as $k => $l) {
            $perms[] = Permission::firstOrCreate(['key' => $k], ['label' => $l, 'group' => 'special']);
        }
        $all = collect($perms);

        $owner   = Role::firstOrCreate(['name' => 'owner'],   ['label' => 'Owner']);
        $manager = Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager']);
        $cashier = Role::firstOrCreate(['name' => 'cashier'], ['label' => 'Cashier']);

        $owner->permissions()->sync($all->pluck('id'));
        $manager->permissions()->sync($all->filter(fn ($p) =>
            in_array($p->group, ['products','inventory','sales','pos','customers','suppliers','purchases','expenses','returns','reports','credit','notifications'])
            || in_array($p->key, ['costs.view','profit.view','stock.adjust']))->pluck('id'));
        $cashier->permissions()->sync($all->filter(fn ($p) =>
            in_array($p->key, ['pos.view','pos.create','sales.view','customers.view','customers.create','notifications.view']))->pluck('id'));

        $defaults = [
            'business_name' => "Barbara's Boutique", 'currency_code' => 'GHS', 'currency_symbol' => '₵',
            'country' => 'Ghana', 'low_stock_threshold' => '3', 'allow_negative_stock' => '0',
            'receipt_footer' => 'Thank you for shopping with us.',
            'backup_daily_time' => '02:00', 'backup_weekly_day' => 'sunday', 'backup_retention_days' => '30',
        ];
        foreach ($defaults as $k => $v) if (Setting::where('key', $k)->doesntExist()) Setting::set($k, $v);
    }
}
