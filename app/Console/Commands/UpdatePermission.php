<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update role permissions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $routes = Route::getRoutes();
        $excludedRoutes = ['ignition.executeSolution', 'ignition.healthCheck', 'ignition.updateConfig', 'sanctum.csrf-cookie'];
        $permissions = [];

        foreach ($routes as $route) {
            $name = trim($route->getName());

            if (! $name || in_array($name, $excludedRoutes)) {
                continue;
            }

            try {
                // throws an exception rather than returning null
                $permission = Permission::findByName($name, 'web');
                array_push($permissions, $permission->name);
                // echo 'find- ' . $permission->name . "\n";
            } catch (\Exception$e) {
                $permission = Permission::create(['name' => $name, 'guard_name' => 'web']);
                array_push($permissions, $permission->name);
                echo 'create- '.$permission->name."\n";
            }
        }

        try {
            echo "Sync super admin permissions...\n";

            $superAdmin = Role::findByName('super admin');
            $superAdmin->syncPermissions(array_unique($permissions));

            echo "Super admin permissions updated.\n";

            echo "Clean up old/outdated permissions.\n";
            // Clean up old and unused permissions
            $allPermissions = Permission::all();
            foreach ($allPermissions as $p) {
                if (! in_array($p->name, array_unique($permissions))) {
                    try {
                        $p->delete();
                        echo 'Delete - '.$p->name."\n";
                    } catch (\Exception$e) {
                        echo $e->getMessage()."\n";
                    }
                }
            }
            echo "All done.\n";
        } catch (\Exception$e) {
            echo $e->getMessage();
        }
    }
}
