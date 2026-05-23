<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileSettings extends Component
{
    use WithFileUploads;

    public $name;
    public $email;
    public $phone;
    public $avatar;

    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    private function getAuthenticatedUser()
    {
        return Auth::guard('staff')->user() ?? Auth::user() ?? \App\Modules\Auth\Models\StaffUser::first();
    }

    public function mount()
    {
        $user = $this->getAuthenticatedUser();
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone;
        } else {
            // Dummy data if not logged in (e.g. for preview)
            $this->name = 'System Admin';
            $this->email = 'admin@isp.net';
        }
    }

    public function updateProfile()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) return;

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:staff_users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($this->avatar) {
            $avatarPath = $this->avatar->store('avatars', 'public');
            $user->avatar = $avatarPath;
        }

        $user->name = $this->name;
        $user->email = $this->email;
        $user->phone = $this->phone;
        $user->save();

        session()->flash('profile_message', 'Profile information updated successfully.');
        
        // Redirect to refresh the global layout header
        return redirect()->route('tenant.profile');
    }

    public function updatePassword()
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) return;

        $this->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user->password = Hash::make($this->new_password);
        $user->save();

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('password_message', 'Password updated successfully.');
    }

    public function render()
    {
        return view('tenant::livewire.profile-settings');
    }
}
