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
    <body class="min-h-screen bg-[#fffdf8] font-sans text-zinc-950 antialiased">
        {{ $slot }}

        @livewireScripts

        <script>
            const copyDocsText = async (text) => {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);

                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                textarea.style.top = '0';

                document.body.append(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
            };

            const setupDocsCopyButtons = () => {
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
                        try {
                            await copyDocsText(block.querySelector('code')?.innerText ?? '');
                            button.textContent = 'Copied';
                        } catch {
                            button.textContent = 'Failed';
                        }

                        window.setTimeout(() => {
                            button.textContent = 'Copy';
                        }, 1200);
                    });

                    block.append(button);
                });
            };

            document.addEventListener('DOMContentLoaded', setupDocsCopyButtons);
            document.addEventListener('livewire:navigated', setupDocsCopyButtons);
        </script>
    </body>
</html>
