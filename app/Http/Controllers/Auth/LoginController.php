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
                    ->with('error', __('域账号或密码错误，请确认后重试。'))
                    ->withInput(['username' => $username]);
            }

            if (!$user->is_active) {
                return back()->with('error', __('您的账号已被禁用，请联系管理员。'));
            }

            Auth::login($user, $request->boolean('remember'));
            $user->update(['last_login_at' => now()]);

            Log::info('AD 登录成功', ['username' => $username, 'user_id' => $user->id]);
            return redirect()->intended(route('dashboard'));

        } catch (\Exception $e) {
            Log::error('AD 登录异常', ['username' => $username, 'error' => $e->getMessage()]);
            return back()
                ->with('error', __('AD 认证服务暂时不可用，请使用本地账号登录或联系管理员。'))
                ->withInput(['username' => $username]);
        }
    }

    /**
     * 本地账号登录逻辑
     */
    private function handleLocalLogin(Request $request, string $username, string $password)
    {
        // 支持用户名或邮箱登录
        // [REVIEW-FIX] L2: 嵌套 where 防止 orWhere 泄漏到外部查询条件
        $user = User::where(function ($q) use ($username) {
            $q->where('username', $username)->orWhere('email', $username);
        })->first();

        if (!$user || !Hash::check($password, $user->password)) {
            // [REVIEW-FIX-R1 #5 P2] 补充本地登录失败审计日志，与 AD 登录保持一致，
            // 便于安全事件追溯（暴力破解探测、撞库尝试等）。仅记录用户名，不记录密码。
            Log::warning('本地登录失败', ['username' => $username, 'ip' => $request->ip()]);
            return back()->with('error', __('用户名或密码错误，请重试。'))->withInput(['username' => $username]);
        }

        if (!$user->is_active) {
            Log::warning('本地登录被拒：账号已禁用', ['username' => $username, 'user_id' => $user->id]);
            return back()->with('error', __('您的账号已被禁用，请联系管理员。'));
        }

        Auth::login($user, $request->boolean('remember'));
        $user->update(['last_login_at' => now()]);

        // [REVIEW-FIX-R1 #5 P2] 补充本地登录成功审计日志，与 AD 登录保持一致
        Log::info('本地登录成功', ['username' => $username, 'user_id' => $user->id, 'ip' => $request->ip()]);

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
