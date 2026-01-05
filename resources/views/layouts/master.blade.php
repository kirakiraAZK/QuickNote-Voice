<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'QuickNote Voice')</title>
    
    <!-- CSRF Token (Penting untuk Laravel Ajax nantinya) -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS (Dipanggil dari public/css/quicknote.css) -->
    <link rel="stylesheet" href="{{ asset('css/quicknote.css') }}">
    
    <!-- Stack untuk CSS tambahan jika diperlukan di page tertentu -->
    @stack('styles')
</head>
<body>
    
    <!-- Konten Utama -->
    @yield('content')

    <!-- Stack untuk Scripts -->
    @stack('scripts')
</body>
</html>