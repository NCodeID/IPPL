<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Dashboard - {{ config('app.name', 'Laravel') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#FDFDFC] text-[#1b1b18] min-h-screen flex items-center justify-center p-6">
        <main class="w-full max-w-sm bg-white rounded-lg p-8 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)]">
            <p>Data User Yang Login:</p>
            <p class="text-sm text-[#706f6c]">Username</p>
            <h1 class="mb-4 text-xl font-medium">{{ auth()->user()?->name }}</h1>

            <p class="text-sm text-[#706f6c]">Role</p>
            <h2 class="text-base font-medium">{{ auth()->user()?->role?->name ?? '-' }}</h2>
        </main>
    </body>
</html>
