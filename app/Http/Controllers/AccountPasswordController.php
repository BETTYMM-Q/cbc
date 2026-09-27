<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Support\PortalRedirector;

class AccountPasswordController extends Controller
{
    public function edit()
    {
        return view('account.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'different:current_password'],
        ]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.'])->withInput();
        }
        // Invalidate sessions on other devices before changing the stored hash.
        Auth::logoutOtherDevices($data['current_password']);
        $request->user()->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();
        $request->session()->regenerate();

        return redirect()->to(PortalRedirector::destinationFor($request->user()))->with('success', 'Password changed successfully. Other signed-in sessions were ended.');
    }
}
