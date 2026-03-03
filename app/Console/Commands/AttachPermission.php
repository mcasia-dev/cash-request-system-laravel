<?php
namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;

class AttachPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attach:permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attach permission to a role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $role = $this->ask('Enter the role you want to assign a permission to');

        if (! Role::whereName($role)->first()) {
            $this->error('Role not found');
            return;
        }

        $permission = $this->ask('Enter the permission you want to assign to the role');
        if (! Permission::whereName($permission)->first()) {
            $this->error('Permission not found');
            return;
        }

        Role::whereName($role)->first()->givePermissionTo($permission);
        $this->info('Permission assigned successfully');
    }
}
