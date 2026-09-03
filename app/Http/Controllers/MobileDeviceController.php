<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\MobileDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CRUD web untuk mobile devices (manajemen credential mobile API).
 * Hanya role owner/super_admin/admin/keuangan.
 */
class MobileDeviceController extends Controller
{
    private function abortUnlessAllowed(): void
    {
        abort_unless(auth()->user()->hasRole(['owner', 'super_admin', 'admin', 'keuangan']), 403);
    }

    public function index(): View
    {
        $this->abortUnlessAllowed();

        $devices = MobileDevice::with('account:id,name')
            ->withCount('account')
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        $accounts = Account::aktif()->orderBy('name')->get();

        return view('mobile_devices.index', compact('devices', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'account_id' => ['required', 'exists:accounts,id'],
            'name'       => ['required', 'string', 'max:100'],
        ]);

        // Generate token
        $plainToken = 'ak_live_' . Str::random(32);
        $tokenHash = hash('sha256', $plainToken);

        $device = MobileDevice::create([
            'account_id' => $data['account_id'],
            'name'       => $data['name'],
            'token_hash' => $tokenHash,
            'status'     => 'active',
        ]);

        return redirect()->route('mobile-device.index')
            ->with('success', "Device \"{$device->name}\" berhasil dibuat.")
            ->with('plain_token', $plainToken)
            ->with('device_id', $device->id);
    }

    public function update(Request $request, MobileDevice $mobileDevice): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $mobileDevice->update($data);

        return redirect()->route('mobile-device.index')->with('success', 'Device berhasil diperbarui.');
    }

    public function destroy(MobileDevice $mobileDevice): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $mobileDevice->delete();

        return redirect()->route('mobile-device.index')->with('success', 'Device berhasil dihapus.');
    }

    public function toggle(MobileDevice $mobileDevice): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $newStatus = $mobileDevice->status === 'active' ? 'revoked' : 'active';
        $mobileDevice->update(['status' => $newStatus]);

        return back()->with('success', $newStatus === 'active'
            ? 'Device diaktifkan kembali.'
            : 'Device dicabut (revoked).');
    }

    public function regenerate(MobileDevice $mobileDevice): RedirectResponse
    {
        $this->abortUnlessAllowed();

        $plainToken = 'ak_live_' . Str::random(32);
        $tokenHash = hash('sha256', $plainToken);

        $mobileDevice->update([
            'token_hash' => $tokenHash,
            'status'     => 'active',
        ]);

        return redirect()->route('mobile-device.index')
            ->with('success', "Token untuk \"{$mobileDevice->name}\" berhasil di-regenerate.")
            ->with('plain_token', $plainToken)
            ->with('device_id', $mobileDevice->id);
    }
}
