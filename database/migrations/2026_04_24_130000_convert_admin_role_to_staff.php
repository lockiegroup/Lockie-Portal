<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ALL_PERMISSIONS = [
        'manage_users',
        'print_settings',
        'envelope_settings',
        'cash_flow',
        'policy_settings',
    ];

    public function up(): void
    {
        DB::table('users')->where('role', 'admin')->get()->each(function ($user) {
            $permissions = json_decode($user->permissions, true);

            // null meant "full access" for admins — give all permissions explicitly
            if ($permissions === null) {
                $permissions = self::ALL_PERMISSIONS;
            }

            DB::table('users')->where('id', $user->id)->update([
                'role'        => 'staff',
                'permissions' => json_encode(array_values($permissions)),
            ]);
        });
    }

    public function down(): void
    {
        // Not reversible — would need to know which staff were originally admins
    }
};
