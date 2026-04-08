<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SelectRole extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $roles = auth()->user()->roles;

        return view('livewire.auth.select-role', [
            'roles' => $roles,
        ]);
    }

    public function selectRole(int $roleId): void
    {
        $user = auth()->user();
        $role = $user->roles()->find($roleId);

        if ($role) {
            Session::put('active_role_id', $role->id);
            $this->redirectIntended(default: route('dashboard'), navigate: true);
        }
    }
}
