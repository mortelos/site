<?php

use App\Actions\Docs\BuildDocsSearchIndex;
use App\Actions\Docs\ResolveDocsPage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts::docs')]
class extends Component {
    public string $version = '0';

    public string $slug = 'index';

    public array $page = [];

    #[Url(as: 'q')]
    public string $search = '';

    public function mount(string $version, ?string $slug = null): void
    {
        $this->version = $version;
        $this->slug = trim($slug ?: 'index', '/');
        $this->page = app(ResolveDocsPage::class)->execute($this->version, $this->slug);
    }

    public function render()
    {
        return $this->view()
            ->title($this->page['title'].' - MortelOS Docs')
            ->layoutData([
                'canonical' => $this->page['canonical_url'],
                'description' => $this->page['description'],
            ]);
    }

    #[Computed]
    public function searchResults(): array
    {
        return app(BuildDocsSearchIndex::class)->execute($this->version, $this->search);
    }
}; ?>

<main class="min-h-screen bg-white">
    <div class="border-b border-zinc-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-[1680px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="/docs/0" wire:navigate class="flex items-center gap-2 text-sm font-semibold text-zinc-950">
                <span class="flex size-6 items-center justify-center border border-teal-700 bg-teal-700 text-xs font-bold text-white">M</span>
                <span>MortelOS Docs</span>
            </a>

            <div class="relative hidden w-full max-w-md md:block">
                <input
                    type="search"
                    wire:model.live.debounce.150ms="search"
                    placeholder="Search docs"
                    class="h-9 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                >

                @if (trim($search) !== '')
                    <div class="absolute right-0 top-11 z-50 w-full overflow-hidden rounded-md border border-zinc-200 bg-white shadow-xl">
                        @forelse ($this->searchResults as $result)
                            <a href="/docs/{{ $version }}/{{ $result['slug'] }}" wire:navigate class="block border-b border-zinc-100 px-3 py-3 last:border-b-0 hover:bg-zinc-50">
                                <span class="block text-sm font-medium text-zinc-950">{{ $result['title'] }}</span>
                                <span class="mt-1 block text-xs text-zinc-500">{{ $result['section'] }} / {{ $result['excerpt'] }}</span>
                            </a>
                        @empty
                            <div class="px-3 py-3 text-sm text-zinc-500">No results</div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto grid max-w-[1680px] grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,1fr)_240px]">
        <aside class="hidden border-r border-zinc-200 px-6 py-8 lg:block">
            <nav class="sticky top-8 space-y-7">
                @foreach (($page['navigation']['sections'] ?? []) as $section)
                    <div>
                        <div class="mb-2 text-xs font-semibold uppercase text-zinc-500">{{ $section['title'] }}</div>
                        <div class="space-y-1">
                            @foreach (($section['items'] ?? []) as $item)
                                <a
                                    href="/docs/{{ $version }}/{{ $item['slug'] }}"
                                    wire:navigate
                                    @class([
                                        'block border-l-2 py-1.5 pl-3 text-sm transition',
                                        'border-teal-600 font-medium text-teal-700' => $item['slug'] === $slug,
                                        'border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-950' => $item['slug'] !== $slug,
                                    ])
                                >
                                    {{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        <section class="min-w-0 px-4 py-8 sm:px-6 lg:px-10 xl:px-14">
            <div class="mb-8 lg:hidden">
                <label for="docs-mobile-nav" class="mb-2 block text-xs font-semibold uppercase text-zinc-500">Section</label>
                <select
                    id="docs-mobile-nav"
                    class="h-11 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm"
                    onchange="if (this.value) window.location.href = this.value"
                >
                    @foreach (($page['navigation']['sections'] ?? []) as $section)
                        <optgroup label="{{ $section['title'] }}">
                            @foreach (($section['items'] ?? []) as $item)
                                <option value="/docs/{{ $version }}/{{ $item['slug'] }}" @selected($item['slug'] === $slug)>
                                    {{ $item['title'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <div class="relative mt-4">
                    <input
                        type="search"
                        wire:model.live.debounce.150ms="search"
                        placeholder="Search docs"
                        class="h-11 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                    >

                    @if (trim($search) !== '')
                        <div class="mt-2 overflow-hidden rounded-md border border-zinc-200 bg-white">
                            @forelse ($this->searchResults as $result)
                                <a href="/docs/{{ $version }}/{{ $result['slug'] }}" wire:navigate class="block border-b border-zinc-100 px-3 py-3 last:border-b-0">
                                    <span class="block text-sm font-medium text-zinc-950">{{ $result['title'] }}</span>
                                    <span class="mt-1 block text-xs text-zinc-500">{{ $result['section'] }}</span>
                                </a>
                            @empty
                                <div class="px-3 py-3 text-sm text-zinc-500">No results</div>
                            @endforelse
                        </div>
                    @endif
                </div>
            </div>

            <article class="docs-prose max-w-[72ch]">
                <div class="mb-7 border-l-2 border-teal-600 pl-4">
                    <p class="text-sm font-medium text-teal-700">Version {{ $version }}</p>
                    <h1>{{ $page['title'] }}</h1>
                    @if ($page['description'] !== '')
                        <p class="lead">{{ $page['description'] }}</p>
                    @endif
                </div>

                {!! $page['html'] !!}
            </article>

            <nav class="mt-12 grid gap-3 border-t border-zinc-200 pt-6 sm:grid-cols-2">
                @if ($page['previous'])
                    <a href="/docs/{{ $version }}/{{ $page['previous']['slug'] }}" wire:navigate class="rounded-md border border-zinc-200 p-4 text-sm hover:border-teal-600">
                        <span class="block text-xs uppercase text-zinc-500">Previous</span>
                        <span class="mt-1 block font-medium text-zinc-950">{{ $page['previous']['title'] }}</span>
                    </a>
                @endif

                @if ($page['next'])
                    <a href="/docs/{{ $version }}/{{ $page['next']['slug'] }}" wire:navigate class="rounded-md border border-zinc-200 p-4 text-sm hover:border-teal-600 sm:text-right">
                        <span class="block text-xs uppercase text-zinc-500">Next</span>
                        <span class="mt-1 block font-medium text-zinc-950">{{ $page['next']['title'] }}</span>
                    </a>
                @endif
            </nav>
        </section>

        <aside class="hidden border-l border-zinc-200 px-6 py-8 xl:block">
            <div class="sticky top-8">
                <div class="mb-3 text-xs font-semibold uppercase text-zinc-500">On this page</div>
                <nav class="space-y-2">
                    @forelse ($page['headings'] as $heading)
                        <a href="#{{ $heading['id'] }}" @class([
                            'block text-sm text-zinc-600 hover:text-teal-700',
                            'pl-3' => $heading['level'] === 3,
                        ])>
                            {{ $heading['text'] }}
                        </a>
                    @empty
                        <p class="text-sm text-zinc-500">No headings</p>
                    @endforelse
                </nav>
            </div>
        </aside>
    </div>
</main>
