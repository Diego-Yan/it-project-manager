<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LdapAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm(Request $request)
    {
        // 支持 ?mode=ad 或 ?mode=local 切换登录模式
        $loginMode = $request->query('mode', 'local');
        if (!in_array($loginMode, ['local', 'ad'])) {
            $loginMode = 'local';
        }
        return view('auth.login', compact('loginMode'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $isAdLogin = $request->boolean('ad_login');

        // ── AD 域账号登录 ──────────────────────────────────────────
        if ($isAdLogin && config('ad-auth.enabled')) {
            return $this->handleAdLogin($request, $username, $password);
        }

        // ── 本地账号登录 ───────────────────────────────────────────
        return $this->handleLocalLogin($request, $username, $password);
    }

    /**
     * AD 域登录逻辑
     */
    private function handleAdLogin(Request $request, string $username, string $password)
    {
        try {
            $ldap = app(LdapAuthService::class);
            $user = $ldap->authenticate($username, $password);

            if (!$user) {
                Log::warning('AD 登录失败', ['username' => $username]);
                return back()
                    ->with('error', '域账号或密码错误，请确认后重试。')
                    ->withInput(['username' => $username]);
            }

            if (!$user->is_active) {
                return back()->with('error', '您的账号已被禁用，请联系管理员。');
            }

            Auth::login($user, $request->boolean('remember'));
            $user->update(['last_login_at' => now()]);

            Log::info('AD 登录成功', ['username' => $username, 'user_id' => $user->id]);
            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            Log::error('AD 登录异常', ['username' => $username, 'error' => $e->getMessage()]);
            return back()
                ->with('error', 'AD 认证服务暂时不可用，请使用本地账号登录或联系管理员。')
                ->withInput(['username' => $username]);
        }
    }

    /**
     * 本地账号登录逻辑
     */
    private function handleLocalLogin(Request $request, string $username, string $password)
    {
        // 支持用户名或邮箱登录
        $user = User::where('username', $username)
                    ->orWhere('email', $username)
                    ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return back()->with('error', '用户名或密码错误，请重试。')->withInput(['username' => $username]);
        }

        if (!$user->is_active) {
            return back()->with('error', '您的账号已被禁用，请联系管理员。');
        }

        Auth::login($user, $request->boolean('remember'));
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
