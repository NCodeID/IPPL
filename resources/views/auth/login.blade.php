<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Login - {{ config('app.name', 'Laravel') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen flex items-center justify-center p-6">
        <main class="w-full max-w-sm bg-white rounded-lg p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)]">
            <h1 class="mb-1 text-xl font-medium">Masuk</h1>
            <p class="mb-6 text-sm text-[#706f6c]">Masuk dengan email dan password Anda.</p>

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                @csrf

                <div class="flex flex-col gap-1">
                    <label for="email" class="text-sm font-medium">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm focus:outline-none focus:border-[#1b1b18] @error('email') border-[#f53003] @enderror"
                    >
                </div>

                <div class="flex flex-col gap-1">
                    <label for="password" class="text-sm font-medium">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-sm border border-[#e3e3e0] bg-[#FDFDFC] px-3 py-2 text-sm focus:outline-none focus:border-[#1b1b18]"
                    >
                </div>

                @error('email')
                    <p class="text-sm text-[#f53003]">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    class="mt-2 rounded-sm bg-[#E87924] text-white px-5 py-1.5 text-sm hover:bg-black"
                >
                    Masuk
                </button>
            </form>
        </main>
    </body>
</html>
