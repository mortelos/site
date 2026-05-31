<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description ?? 'MortelOS public documentation' }}">
        <link rel="canonical" href="{{ $canonical ?? rtrim((string) config('docs.site_url', 'https://mortelos.nl'), '/').'/docs/0' }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title ?? 'MortelOS Docs' }}">
        <meta property="og:description" content="{{ $description ?? 'MortelOS public documentation' }}">
        <meta property="og:url" content="{{ $canonical ?? rtrim((string) config('docs.site_url', 'https://mortelos.nl'), '/').'/docs/0' }}">

        <title>{{ $title ?? config('app.name', 'MortelOS').' Docs' }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-950 antialiased">
        {{ $slot }}

        @livewireScripts

        <script>
            document.addEventListener('livewire:navigated', () => {
                document.querySelectorAll('.docs-prose pre').forEach((block) => {
                    if (block.dataset.copyReady === 'true') {
                        return;
                    }

                    block.dataset.copyReady = 'true';

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'docs-copy-button';
                    button.textContent = 'Copy';
                    button.addEventListener('click', async () => {
                        await navigator.clipboard.writeText(block.querySelector('code')?.innerText ?? '');
                        button.textContent = 'Copied';
                        window.setTimeout(() => {
                            button.textContent = 'Copy';
                        }, 1200);
                    });

                    block.append(button);
                });
            });
        </script>
    </body>
</html>
