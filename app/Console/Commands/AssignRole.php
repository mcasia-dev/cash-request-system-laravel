<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignRole extends Command
{
    protected $email = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assign:role';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign role to a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->email = $this->ask('Enter the email of the user you want to assign a role to');

        $user = User::whereEmail($this->email)->first();

        if (! $user) {
            $this->error('User not found');
            return;
        }

        $role = $this->ask('Enter the role you want to assign to the user');

        if (! Role::whereName($role)->first()) {
            $this->error('Role not found');
            return;
        }

        $user->assignRole($role);

        $this->info('Role assigned successfully');
    }
}
