<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Lockie Portal' }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="{{ $bodyClass ?? 'bg-slate-100 min-h-screen antialiased' }}">
    {{ $slot }}
</body>
</html>
