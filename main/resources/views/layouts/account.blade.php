<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title', 'Kody')</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="account-page">
    <header class="account-header"><a href="{{ url('/') }}" class="brand">Kody<span>.</span></a><span>Learn. Practice. Progress.</span></header>
    <main class="account-shell">
        @if (session('status'))
            <p class="notice" role="status">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>
    <footer class="account-footer">Your next programming milestone starts here.</footer>
</body>
</html>
