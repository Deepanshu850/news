<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PaymentGuideSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::create(['key' => 'manual_payment_guide', 'value' => '']);
        $permissions = [
            'name' => 'cash_payment',
            'display_name' => 'Cash Payment',
        ];
        $permission = Permission::create($permissions);
        /** @var Role $adminRole */
        $adminRole = Role::whereName('admin')->first();

        if (isset($adminRole)) {
            $staffPermission = Permission::whereIn('name', ['cash_payment'])->pluck('name', 'id');
            $adminRole->givePermissionTo($staffPermission);
        }
    }
}
