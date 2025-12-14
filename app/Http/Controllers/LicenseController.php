<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use App\Services\SejoliService;
use Illuminate\Support\Facades\Log;
class LicenseController extends Controller
{
    public function showActivate(LicenseService $licenseService)
    {
        $validation = $licenseService->validateLicense();
        if ($validation['valid'] ?? false) {
            return redirect()->route('dashboard');
        }
        return view('license.activate');
    }
    public function showLogin(LicenseService $licenseService)
    {
        return view('license.login', ['hardwareId' => $licenseService->getHardwareId()]);
    }
    public function processActivate(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processActivate hit', ['email' => $request->input('email')]);
        $request->validate(['email' => 'required|email', 'password' => 'required|string', 'license' => 'required|string',]);
        $email = trim($request->email);
        $password = $request->password;
        $licenseKey = trim($request->license);
        $result = $licenseService->activate($email, $password, $licenseKey);
        if ($licenseService->messageContains($result, 'already registered')) {
            return back()->withErrors(['msg' => 'Lisensi sudah terdaftar, silakan login atau reset.'])->withInput();
        }
        $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'activate');
        if ($isValid) {
            $licenseService->saveLocalLicense([
                'license_key' => $licenseKey,
                'status' => 'active',
                'hardware_id' => $licenseService->getHardwareId(),
                'email' => $email,
                'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
                'last_check_at' => now()->toIso8601String(),
                'message' => 'Registered via activation',
            ]);
            return redirect()->route('dashboard');
        }
        Log::warning('[License] activation failed', ['email' => $email, 'license' => $licenseKey, 'response' => $result]);
        return back()->withErrors(['msg' => 'Lisensi tidak valid atau kredensial salah.']);
    }
    public function processLogin(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processLogin hit', ['license' => $request->input('license')]);
        $request->validate(['license' => 'required|string',]);
        $licenseKey = trim($request->license);
        $local = $licenseService->loadLocalLicense();
        if (!$local || empty($local['license_key'])) {
            return redirect()->route('license.activate.form')->withErrors(['msg' => 'Lisensi belum terdaftar. Silakan aktivasi terlebih dahulu.']);
        }
        $result = $licenseService->validateRemote($licenseKey);
        Log::info('[License] validateLicense response', ['license' => $licenseKey, 'response' => $result]);
        $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'validate');
        if ($isValid) {
            $licenseService->saveLocalLicense(['license_key' => $licenseKey, 'status' => 'active', 'hardware_id' => $licenseService->getHardwareId(), 'last_check_at' => now()->toIso8601String(), 'message' => 'Validated via login',]);
            Log::info('[License] login successful, saved license.json', ['license' => $licenseKey]);
            return redirect()->route('dashboard');
        }
        Log::warning('[License] login failed - invalid license', ['license' => $licenseKey]);
        return back()->withErrors(['msg' => 'License key tidak valid.']);
    }
    public function processAuthLogin(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processAuthLogin hit', ['email' => $request->input('email')]);
        $request->validate(['email' => 'required|email', 'password' => 'required|string',]);
        $email = trim($request->email);
        $password = $request->password;
        $local = $licenseService->loadLocalLicense();
        $licenseKey = $local['license_key'] ?? null;
        if (!$licenseKey) {
            return back()->withErrors(['msg' => 'File lisensi tidak ditemukan di perangkat ini. Silakan Aktivasi ulang untuk mendaftarkan License Key.'])->withInput();
        }
        // 1. Local Validation
        if (($local['email'] ?? '') !== $email) {
            Log::warning('[License] Local email mismatch', ['expected' => $local['email'] ?? '', 'got' => $email]);
            return redirect()->route('license.activate.form')->withErrors(['msg' => 'Email tidak sesuai dengan data lisensi di perangkat ini.']);
        }

        if (!\Illuminate\Support\Facades\Hash::check($password, $local['password_hash'] ?? '')) {
            Log::warning('[License] Local password mismatch');
            return redirect()->route('license.activate.form')->withErrors(['msg' => 'Password salah.']);
        }

        // 2. Remote Validation (Connectivity Check only)
        // Only send license and hardware_id to server, no credentials
        $sejoli = app(SejoliService::class);
        $result = $sejoli->validateLicense($licenseKey, null, null, null); // Pass null for email/pass
        Log::info('[License] authLogin remote check', ['license' => $licenseKey, 'response' => $result]);

        // 3. Check for valid:true
        $isValid = $licenseService->isRemoteValid($result, $licenseKey, 'validate');

        if ($isValid) {
            $licenseService->saveLocalLicense(array_merge($local, [
                'status' => 'active',
                'last_check_at' => now()->toIso8601String(),
                'message' => 'Validated via auth login',
            ]));
            return redirect()->route('dashboard');
        }
        return redirect()->route('license.activate.form')->withErrors(['msg' => 'Validasi server gagal. Silakan aktivasi ulang.']);
    }
    public function processAuthReset(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] processAuthReset hit', ['email' => $request->input('email')]);
        $request->validate(['email' => 'required|email', 'password' => 'required|string',]);
        $email = trim($request->email);
        $password = $request->password;
        $local = $licenseService->loadLocalLicense();
        $licenseKey = $local['license_key'] ?? null;
        if (!$licenseKey) {
            return back()->withErrors(['msg' => 'Tidak ada lisensi yang tersimpan untuk direset.'])->withInput();
        }
        $sejoli = app(SejoliService::class);
        $result = $sejoli->resetLicense($email, $password, $licenseKey);
        Log::info('[License] authReset response', ['license' => $licenseKey, 'response' => $result]);
        if ($licenseService->messageContains($result, ['tidak ditemukan', "doesn't exist"])) {
            return back()->withErrors(['msg' => 'Lisensi tidak ditemukan.'])->withInput();
        }
        $success = $licenseService->isRemoteValid($result, $licenseKey, 'reset');
        if ($success) {
            $licenseService->revokeLocalLicense();
            return redirect()->route('license.activate.form')->with('success', 'Lisensi berhasil direset. Silakan aktivasi ulang.');
        }
        return back()->withErrors(['msg' => 'Reset gagal. Kredensial tidak valid.'])->withInput();
    }
    public function logout()
    {
        app(LicenseService::class)->revokeLocalLicense();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('license.activate.form');
    }
    public function reset(Request $request, LicenseService $licenseService)
    {
        Log::info('[License] reset called');
        $request->validate(['email' => 'required|email', 'password' => 'required|string', 'license' => 'required|string',]);
        $email = trim($request->email);
        $password = $request->password;
        $licenseKey = trim($request->license);
        $resp = $licenseService->resetRemote($email, $password, $licenseKey);
        Log::info('[License] reset response', ['license' => $licenseKey, 'response' => $resp]);
        if ($licenseService->messageContains($resp, ['tidak ditemukan', "doesn't exist"])) {
            return back()->withErrors(['msg' => 'Lisensi tidak terdaftar.'])->withInput();
        }
        $success = $licenseService->isRemoteValid($resp, $licenseKey, 'reset');
        if ($success) {
            $licenseService->revokeLocalLicense();
            Log::info('[License] reset successful, license revoked');
            return back()->with('success', 'Lisensi sudah direset. Silakan aktivasi ulang di perangkat lain.');
        }
        Log::warning('[License] reset failed', ['response' => $resp]);
        return back()->withErrors(['msg' => 'Reset lisensi gagal: ' . json_encode($resp)]);
    }
    public function revalidate(LicenseService $licenseService)
    {
        $info = $licenseService->loadLocalLicense();
        if (!$info || empty($info['license_key'])) {
            return redirect()->route('license.blocked')->withErrors(['msg' => 'Lisensi belum terdaftar atau hilang.']);
        }
        $licenseKey = $info['license_key'];
        $hardwareId = $info['hardware_id'] ?? $info['string'] ?? null;
        if (!$hardwareId) {
            $hardwareId = $licenseService->getHardwareId();
        }
        $resp = $licenseService->validateRemote($licenseKey, $hardwareId);
        $valid = $licenseService->isRemoteValid($resp, $licenseKey, 'validate');
        if ($valid) {
            $licenseService->saveLocalLicense(array_merge($info, ['license_key' => $licenseKey, 'status' => 'active', 'hardware_id' => $hardwareId, 'string' => $hardwareId, 'email' => $info['email'] ?? null, 'last_check_at' => now()->toIso8601String(), 'message' => 'Revalidate successful',]));
            return redirect()->route('dashboard')->with('success', 'Lisensi tervalidasi ulang. Anda bisa lanjut menggunakan aplikasi.');
        }
        $licenseService->blockLocalLicense('Lisensi gagal divalidasi ulang.');
        return redirect()->route('license.blocked')->withErrors(['msg' => 'Validasi gagal atau offline. Aktifkan internet lalu coba lagi.']);
    }
    public function blocked()
    {
        return view('license.blocked');
    }

    public function locked()
    {
        return view('license.locked');
    }
}