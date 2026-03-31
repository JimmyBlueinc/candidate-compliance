<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="icons-loading">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'AgencyHQ') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" />

        @php
            $manifest = public_path('build/manifest.json');
            $assets = [];
            if (file_exists($manifest)) {
                $data = json_decode(file_get_contents($manifest), true);
                foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
                    if (isset($data[$entry]['file'])) {
                        $assets[] = $data[$entry]['file'];
                        // Include CSS imports
                        if (!empty($data[$entry]['css'])) {
                            foreach ($data[$entry]['css'] as $css) {
                                $assets[] = $css;
                            }
                        }
                    }
                }
            }
        @endphp
        @foreach($assets as $asset)
            @if(str_ends_with($asset, '.css'))
                <link rel="stylesheet" href="/build/{{ $asset }}" />
            @elseif(str_ends_with($asset, '.js'))
                <script type="module" src="/build/{{ $asset }}"></script>
            @endif
        @endforeach
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
