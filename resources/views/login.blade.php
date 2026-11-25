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
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-sky-600/20 text-sky-400 text-2xl mb-4">
        <i class="fa-solid fa-gas-pump"></i>
      </div>
      <h1 class="text-2xl font-bold tracking-tight">SPBU Monitoring</h1>
      <p class="text-sm text-slate-400 mt-1">Smart City Hub — masuk untuk melanjutkan</p>
    </div>

    @if ($errors->any())
      <div class="mb-5 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">
        @foreach ($errors->all() as $error)
          <div>{{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form id="loginForm" method="POST" action="{{ route('login.store') }}" class="rounded-2xl border border-slate-800 bg-slate-900 p-6 space-y-5">
      @csrf
      <div>
        <label for="email" class="block text-xs font-semibold text-slate-400 mb-2">Email</label>
        <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}"
          class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-500">
      </div>
      <div>
        <label for="password" class="block text-xs font-semibold text-slate-400 mb-2">Password</label>
        <input id="password" name="password" type="password" required
          class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white outline-none focus:border-sky-500">
      </div>
      <button type="submit" class="w-full rounded-lg bg-sky-600 hover:bg-sky-500 px-4 py-2.5 text-sm font-bold transition">
        <i class="fa-solid fa-right-to-bracket mr-2"></i>Masuk
      </button>
    </form>

    <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
      <p class="text-xs font-semibold text-slate-400 mb-3">Akun demo — password: <span class="text-sky-400 font-mono">password</span></p>
      <div class="space-y-2">
        @foreach ($demoAccounts as $account)
          <button type="button" class="w-full flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-left hover:border-sky-600 transition demo-account" data-email="{{ $account['email'] }}">
            <span class="text-xs font-semibold text-slate-200">{{ $account['label'] }}</span>
            <span class="text-[10px] font-mono text-slate-500">{{ $account['role'] }}</span>
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
