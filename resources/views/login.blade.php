<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Masuk — SPBU Monitoring</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-[#08090b] text-zinc-100 flex items-center justify-center px-4 antialiased">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/20 to-emerald-600/5 border border-emerald-500/30 text-emerald-400 mb-4 shadow-[0_0_25px_rgba(16,185,129,0.2)]">
        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 22V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v17"/>
          <path d="M2 22h14"/>
          <rect x="6" y="7" width="6" height="4" rx="1"/>
          <path d="M15 9h2a2 2 0 0 1 2 2v5a2 2 0 0 0 2 2 2 2 0 0 0 2-2V9.83a2 2 0 0 0-.59-1.42L19 5"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold tracking-tight text-white">spbu<span class="text-emerald-400">.hub</span></h1>
      <p class="text-sm text-zinc-400 mt-1">Monitoring Pengiriman Logistik BBM — masuk untuk melanjutkan</p>
    </div>

    @if ($errors->any())
      <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('login.store') }}" class="rounded-2xl border border-white/[0.08] bg-[#0e1014] p-6 space-y-5 shadow-2xl">
      @csrf
      <div>
        <label for="email" class="block text-xs font-semibold text-zinc-400 mb-2">Email</label>
        <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
          class="w-full rounded-xl border border-white/[0.08] bg-[#13161c] px-3.5 py-2.5 text-sm text-white outline-none focus:border-emerald-500 transition">
      </div>
      <div>
        <label for="password" class="block text-xs font-semibold text-zinc-400 mb-2">Password</label>
        <input id="password" name="password" type="password" required
          class="w-full rounded-xl border border-white/[0.08] bg-[#13161c] px-3.5 py-2.5 text-sm text-white outline-none focus:border-emerald-500 transition">
      </div>
      <button type="submit" class="w-full rounded-xl bg-emerald-500 hover:bg-emerald-400 text-black px-4 py-2.5 text-sm font-bold shadow-lg shadow-emerald-500/20 transition">
        <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
      </button>
    </form>

    <div class="mt-6 rounded-2xl border border-white/[0.08] bg-[#0e1014]/60 p-5">
      <p class="text-xs font-semibold text-zinc-400 mb-3">Akun demo — password: <span class="text-emerald-400 font-mono">password</span></p>
      <div class="space-y-2">
        @foreach ($demoAccounts as $account)
          <button type="button" class="w-full flex items-center justify-between rounded-xl border border-white/[0.06] bg-[#13161c] px-3.5 py-2 text-left hover:border-emerald-500/40 hover:bg-[#181b22] transition demo-account" data-email="{{ $account['email'] }}">
            <span class="text-xs font-semibold text-zinc-200">{{ $account['label'] }}</span>
            <span class="text-[10px] font-mono text-zinc-500">{{ $account['role'] }}</span>
          </button>
        @endforeach
      </div>
    </div>
  </div>

  <script>
    document.querySelectorAll('.demo-account').forEach((button) => {
      button.addEventListener('click', () => {
        document.getElementById('email').value = button.dataset.email;
        document.getElementById('password').value = 'password';
        document.getElementById('loginForm').requestSubmit();
      });
    });
  </script>
</body>
</html>
