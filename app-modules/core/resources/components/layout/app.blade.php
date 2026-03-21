<!DOCTYPE html>
<html lang="en">
    <head>
    	<meta charset="UTF-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1.0">

    	<meta name="csrf-token" content="{{ csrf_token() }}">

    	<title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    	@vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body>
        <main class="h-screen bg-gray-600">
            {{ $slot }}
        </main>
    </body>
</html>
