<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Login') — webAwanna</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @php $manifestExists = file_exists(public_path('build/manifest.json')); @endphp
    @if($manifestExists)
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="{{ asset('css/clay.css') }}">
    @endif

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #FFF5F5 0%, #FFF0F8 50%, #F0F8FF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        /* Decorative blobs */
        .blob {
            position: fixed;
            border-radius: 50%;
            pointer-events: none;
            filter: blur(70px);
            opacity: .18;
            z-index: 0;
        }
        .blob-1 { width: 380px; height: 380px; background: #FF6B6B; top: -100px; left: -100px; }
        .blob-2 { width: 300px; height: 300px; background: #4ECDC4; top: 50%; right: -120px; }
        .blob-3 { width: 260px; height: 260px; background: #A78BFA; bottom: -80px; left: 30%; }

        /* Responsive card */
        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }
        @media (max-width: 479px) {
            .login-wrap { max-width: 100%; }
            .login-card  { padding: 20px !important; }
        }

        /* Utility for login */
        .hidden { display: none !important; }
    </style>

    @stack('styles')
</head>
<body id="app-body">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    @yield('content')

    @stack('scripts')
</body>
</html>
