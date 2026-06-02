<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - IT服务管理</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
            margin: 1rem;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-header p {
            margin-top: 0.5rem;
            opacity: 0.9;
            font-size: 0.875rem;
        }

        .login-body {
            padding: 2rem;
        }

        .login-tab {
            display: flex;
            background: #f3f4f6;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 1.5rem;
        }

        .login-tab button {
            flex: 1;
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: #6b7280;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .login-tab button:hover { background: #e5e7eb; }

        .login-tab button.active {
            background: white;
            color: #667eea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .form-group { margin-bottom: 1rem; }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            cursor: pointer;
        }

        .ad-badge {
            display: inline-flex;
            align-items: center;
            background: #eff6ff;
            color: #1e40af;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 1.5rem;
        }

        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.4);
        }

        .login-btn:active { transform: translateY(0); }

        .version-badge {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.9);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            color: #6b7280;
            backdrop-filter: blur(8px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            @php $logoUrl = config('services.logo.url') ? asset('storage/' . config('services.logo.url')) : null; @endphp
            @if($logoUrl)
            <div style="margin-bottom:12px"><img src="{{ $logoUrl }}" style="height:48px;max-width:100%"></div>
            @endif
            <h1>IT服务管理</h1>
            <p>V1.3 - 企业IT服务管理平台</p>
        </div>

        <div class="login-body">
            {{-- 登录模式切换 --}}
            <div class="login-tab">
                <button id="tab-local"
                    class="{{ $loginMode === 'ad' ? '' : 'active' }}"
                    onclick="switchTab('local')">本地账号</button>
                <button id="tab-ad"
                    class="{{ $loginMode === 'ad' ? 'active' : '' }}"
                    onclick="switchTab('ad')">AD 域账号</button>
            </div>

            {{-- 错误提示 --}}
            @if ($errors->any())
                <div class="alert-error">{{ $errors->first() }}</div>
            @endif

            @if (session('error'))
                <div class="alert-error">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <input type="hidden" name="ad_login" id="ad_login" value="{{ $loginMode === 'ad' ? '1' : '0' }}">

                <div class="form-group">
                    <label for="username">{{ $loginMode === 'ad' ? 'AD 用户名' : '用户名 / 邮箱' }}</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="{{ old('username') }}"
                        placeholder="{{ $loginMode === 'ad' ? '例如: zhangsan' : '输入用户名或邮箱' }}"
                        required
                        autofocus>
                </div>

                <div class="form-group">
                    <label for="password">密码</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="{{ $loginMode === 'ad' ? 'AD 域密码' : '输入登录密码' }}"
                        required>
                </div>

                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        记住我
                    </label>

                    @if($loginMode === 'ad')
                        <div class="ad-badge">
                            <svg style="width:14px;height:14px;margin-right:4px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            AD 域认证
                        </div>
                    @endif
                </div>

                <button type="submit" class="login-btn">
                    {{ $loginMode === 'ad' ? 'AD 域登录' : '登录' }}
                </button>
            </form>
        </div>
    </div>

    <div class="version-badge">ITSM v1.2.1</div>

    <script>
        function switchTab(mode) {
            document.getElementById('tab-local').classList.toggle('active', mode === 'local');
            document.getElementById('tab-ad').classList.toggle('active', mode === 'ad');
            document.getElementById('ad_login').value = mode === 'ad' ? 1 : 0;
            document.getElementById('username').placeholder = mode === 'ad' ? '例如: zhangsan' : '输入用户名或邮箱';
            document.getElementById('password').placeholder = mode === 'ad' ? 'AD 域密码' : '输入登录密码';
            window.location.href = '/login?mode=' + mode;
        }
    </script>
</body>
</html>
