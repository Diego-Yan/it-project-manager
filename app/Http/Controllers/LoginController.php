<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\LdapAuthService;

class LoginController extends Controller
{
    protected $ldapService;

    public function __construct(LdapAuthService $ldapService)
    {
        $this->ldapService = $ldapService;
        $this->middleware('guest')->except('logout');
    }

    /**
     * 显示登录页面
     */
    public function showLoginForm()
    {
        $loginMode = request()->query('mode', 'local');
        return view('auth.login', compact('loginMode'));
    }

    /**
     * 处理登录请求
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'ad_login' => 'nullable|boolean', // 是否使用 AD 登录
        ]);

        $username = $credentials['username'];
        $password = $credentials['password'];
        $useAd = $request->boolean('ad_login', false);

        Log::info('登录尝试', [
            'username' => $username,
            'use_ad' => $useAd,
            'ip' => $request->ip()
        ]);

        $user = null;

        // 方式1: AD 认证
        if ($useAd) {
            $user = $this->ldapService->authenticate($username, $password);
            if ($user) {
                Auth::login($user, $request->boolean('remember', false));
                return $this->authenticated($request, $user);
            }
        }

        // 方式2: AD 认证失败，回退到本地认证（如果配置允许）
        if ($useAd && config('ad-auth.fallback_to_local')) {
            $user = $this->attemptLocalAuth($username, $password);
            if ($user) {
                Auth::login($user, $request->boolean('remember', false));
                return $this->authenticated($request, $user);
            }
        }

        // 方式3: 直接本地认证
        if (!$useAd) {
            $user = $this->attemptLocalAuth($username, $password);
            if ($user) {
                Auth::login($user, $request->boolean('remember', false));
                return $this->authenticated($request, $user);
            }
        }

        // 认证失败
        return back()
            ->withInput($request->only('username', 'remember', 'ad_login'))
            ->withErrors([
                'username' => '用户名或密码错误',
            ]);
    }

    /**
     * 本地认证
     */
    private function attemptLocalAuth(string $username, string $password): ?\App\Models\User
    {
        // 尝试用用户名登录
        $user = \App\Models\User::where('name', $username)->first();

        // 如果用户名没找到，尝试用邮箱登录
        if (!$user) {
            $user = \App\Models\User::where('email', $username)->first();
        }

        if ($user && \Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * 认证成功后处理
     */
    protected function authenticated(Request $request, $user)
    {
        Log::info('登录成功', [
            'user_id' => $user->id,
            'username' => $user->name,
            'ad_authenticated' => $user->ad_authenticated,
            'ip' => $request->ip()
        ]);

        // 记录登录日志
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended('/dashboard');
    }

    /**
     * 退出登录
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
