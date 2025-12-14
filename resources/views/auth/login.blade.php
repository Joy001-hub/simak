<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body
    class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 flex items-center justify-center p-4 font-sans">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl border border-white/30 overflow-hidden">
            {{-- Header --}}
            <div class="px-8 pt-8 pb-6 text-center border-b border-slate-200">
                <img class="app-logo-img mx-auto mb-4" src="{{ asset('/logo-app.png') }}" alt="SIMAK Logo"
                    onerror="this.onerror=null;this.src='{{ asset('/logo-simak.svg') }}';"
                    style="width:120px; height:auto;">
                <h1 class="text-2xl font-bold text-slate-900">Login</h1>
                <p class="text-sm text-slate-600 mt-2">Masukkan email dan password untuk masuk.</p>
            </div>

            @if (session('success'))
                <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 text-sm"
                    role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 text-sm" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form id="login-form" action="{{ route('auth.login') }}" method="POST" class="px-8 py-8 space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" required autocomplete="email"
                        class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                        placeholder="email@domain.com" value="{{ old('email') }}">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2" for="password">Password</label>
                    <input type="password" name="password" id="password" required autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                        placeholder="Password akun">
                </div>

                <button type="submit"
                    class="w-full bg-[#b91c3b] hover:bg-[#a01835] text-white font-semibold py-3 rounded-lg transition duration-200 shadow-md hover:shadow-lg">
                    Masuk
                </button>

                <p class="text-center text-sm text-slate-600 mt-4">
                    Reset akun <a href="/reset" class="text-[#b91c3b] font-semibold hover:underline">disini</a>
                </p>
            </form>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.3s ease-out';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>

</html>