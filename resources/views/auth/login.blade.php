
<x-guest-layout>
    <style>
        body {
            background: #faf6f1 !important;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            margin: 20px auto;
            padding: 20px;
            animation: fadeUp .7s ease-out both;
        }

        .login-card {
            background: #fff;
            border: 1px solid #eadfd2;
            border-radius: 22px;
            padding: 36px;
            box-shadow: 0 20px 60px rgba(80, 55, 30, .09);
        }

        .brand-logo {
            width: 66px;
            height: 66px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 19px;
            background: linear-gradient(135deg, #b8863b, #e7a3a8);
            color: #fff;
            font-family: Georgia, serif;
            font-size: 32px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(184, 134, 59, .2);
            animation: floatLogo 3s ease-in-out infinite;
        }

        .login-title {
            text-align: center;
            font-family: Georgia, serif;
            font-size: 30px;
            color: #1c1917;
            margin-bottom: 6px;
        }

        .login-subtitle {
            text-align: center;
            color: #78716c;
            font-size: 14px;
            margin-bottom: 28px;
        }

        .field-label {
            display: block;
            color: #44403c;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .login-input {
            display: block;
            width: 100%;
            border: 1px solid #e7ddd1;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fdfbf9;
            color: #1c1917;
            outline: none;
            transition: border-color .25s, box-shadow .25s;
        }

        .login-input:focus {
            border-color: #b8863b;
            box-shadow: 0 0 0 3px rgba(184, 134, 59, .12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .login-input {
            padding-right: 76px;
        }

        .show-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #8b6b3d;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .login-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin: 18px 0 24px;
            font-size: 13px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #57534e;
        }

        .remember-label input {
            accent-color: #b8863b;
        }

        .forgot-link {
            color: #9a6b29;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
            display: block;
            width: 100%;
            border: none;
            border-radius: 10px;
            padding: 13px;
            background: #1c1917;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: transform .25s, box-shadow .25s, background .25s;
        }

        .login-button:hover {
            background: #35291f;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(28, 25, 23, .18);
        }

        .login-footer {
            text-align: center;
            color: #a8a29e;
            font-size: 12px;
            margin-top: 24px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes floatLogo {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @media (max-width: 480px) {
            .login-wrapper { padding: 12px; }
            .login-card { padding: 26px 21px; }
            .login-title { font-size: 26px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-wrapper, .brand-logo { animation: none; }
            .login-button, .login-input { transition: none; }
        }
    </style>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="brand-logo">B</div>

            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your boutique workspace</p>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <label for="email" class="field-label">Email Address</label>

                    <input
                        id="email"
                        class="login-input"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your email"
                        required
                        autofocus
                        autocomplete="username"
                    >

                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div style="margin-top: 20px;">
                    <label for="password" class="field-label">Password</label>

                    <div class="password-wrap">
                        <input
                            id="password"
                            class="login-input"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="show-password"
                            id="togglePassword"
                            aria-controls="password"
                            aria-pressed="false"
                        >Show</button>
                    </div>

                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="login-options">
                    <label for="remember_me" class="remember-label">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                        >
                        Remember me
                    </label>

                    @if (Route::has('password.request'))
                        <a class="forgot-link" href="{{ route('password.request') }}">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <button type="submit" class="login-button">
                    Sign In &rarr;
                </button>
            </form>

            <div class="login-footer">
                &copy; {{ date('Y') }} {{ \App\Models\Setting::get('business_name', "Barbara's Boutique") }}
                <br>Secure staff access
            </div>

        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const password = document.getElementById('password');
            const showing = password.type === 'password';

            password.type = showing ? 'text' : 'password';
            this.textContent = showing ? 'Hide' : 'Show';
            this.setAttribute('aria-pressed', String(showing));
        });
    </script>
</x-guest-layout>
