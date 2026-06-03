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

<main class="min-h-screen bg-[#fffdf8] text-zinc-950">
    <a href="#docs-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-zinc-950 focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-white">
        Skip to content
    </a>

    <div class="sticky top-0 z-40 border-b border-[#efe6dc] bg-[#fffdf8]/95 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-[1440px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="/docs/0" wire:navigate class="flex items-center gap-2.5 text-sm font-semibold text-zinc-950">
                <span class="flex size-7 items-center justify-center rounded-sm bg-[#ff2d20] text-xs font-bold text-white shadow-sm shadow-red-950/10">M</span>
                <span>MortelOS</span>
                <span class="hidden text-zinc-400 sm:inline">Docs</span>
            </a>

            <div class="relative hidden w-full max-w-[430px] md:block">
                <input
                    type="search"
                    wire:model.live.debounce.150ms="search"
                    placeholder="Search docs"
                    class="h-10 w-full rounded-md border border-[#e2d8cd] bg-white/80 px-3 text-sm outline-none transition placeholder:text-zinc-400 focus:border-[#ff2d20] focus:ring-4 focus:ring-red-100"
                >

                @if (trim($search) !== '')
                    <div class="absolute right-0 top-12 z-50 w-full overflow-hidden rounded-md border border-[#e8ded4] bg-white shadow-xl shadow-zinc-950/10">
                        @forelse ($this->searchResults as $result)
                            <a href="/docs/{{ $version }}/{{ $result['slug'] }}" wire:navigate class="block border-b border-[#f1e8df] px-3 py-3 last:border-b-0 hover:bg-[#fff7ef]">
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

    <div class="mx-auto grid max-w-[1440px] grid-cols-1 lg:grid-cols-[280px_minmax(0,1fr)] xl:grid-cols-[280px_minmax(0,780px)_260px]">
        <aside class="hidden min-h-[calc(100vh-4rem)] border-r border-[#efe6dc] px-5 py-8 lg:block">
            <nav class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-7 overflow-y-auto pr-2">
                @foreach (($page['navigation']['sections'] ?? []) as $section)
                    <div>
                        <div class="mb-2 px-3 text-xs font-semibold uppercase text-zinc-500">{{ $section['title'] }}</div>
                        <div class="space-y-1">
                            @foreach (($section['items'] ?? []) as $item)
                                <a
                                    href="/docs/{{ $version }}/{{ $item['slug'] }}"
                                    wire:navigate
                                    @if ($item['slug'] === $slug) aria-current="page" @endif
                                    @class([
                                        'block rounded-md px-3 py-1.5 text-sm transition',
                                        'bg-red-50 font-medium text-[#e02419]' => $item['slug'] === $slug,
                                        'text-zinc-600 hover:bg-[#fff7ef] hover:text-zinc-950' => $item['slug'] !== $slug,
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

        <section id="docs-content" class="min-w-0 px-4 py-8 sm:px-6 lg:px-10 xl:px-12">
            <div class="mb-8 lg:hidden">
                <label for="docs-mobile-nav" class="mb-2 block text-xs font-semibold uppercase text-zinc-500">Section</label>
                <select
                    id="docs-mobile-nav"
                    class="h-11 w-full rounded-md border border-[#e2d8cd] bg-white px-3 text-sm"
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
                        class="h-11 w-full rounded-md border border-[#e2d8cd] bg-white px-3 text-sm outline-none transition focus:border-[#ff2d20] focus:ring-4 focus:ring-red-100"
                    >

                    @if (trim($search) !== '')
                        <div class="mt-2 overflow-hidden rounded-md border border-[#e8ded4] bg-white">
                            @forelse ($this->searchResults as $result)
                                <a href="/docs/{{ $version }}/{{ $result['slug'] }}" wire:navigate class="block border-b border-[#f1e8df] px-3 py-3 last:border-b-0">
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

            <article class="docs-prose max-w-none">
                <div class="mb-8">
                    <p class="mb-3 text-sm font-medium text-[#ff2d20]">Version {{ $version }}</p>
                    <h1>{{ $page['title'] }}</h1>
                    @if ($page['description'] !== '')
                        <p class="lead">{{ $page['description'] }}</p>
                    @endif
                </div>

                {!! $page['html'] !!}
            </article>

            <nav class="mt-12 grid gap-3 border-t border-[#efe6dc] pt-6 sm:grid-cols-2">
                @if ($page['previous'])
                    <a href="/docs/{{ $version }}/{{ $page['previous']['slug'] }}" wire:navigate class="rounded-md border border-[#e8ded4] bg-white/60 p-4 text-sm transition hover:border-[#ff2d20] hover:bg-white">
                        <span class="block text-xs uppercase text-zinc-500">Previous</span>
                        <span class="mt-1 block font-medium text-zinc-950">{{ $page['previous']['title'] }}</span>
                    </a>
                @endif

                @if ($page['next'])
                    <a href="/docs/{{ $version }}/{{ $page['next']['slug'] }}" wire:navigate class="rounded-md border border-[#e8ded4] bg-white/60 p-4 text-sm transition hover:border-[#ff2d20] hover:bg-white sm:text-right">
                        <span class="block text-xs uppercase text-zinc-500">Next</span>
                        <span class="mt-1 block font-medium text-zinc-950">{{ $page['next']['title'] }}</span>
                    </a>
                @endif
            </nav>
        </section>

        <aside class="hidden min-h-[calc(100vh-4rem)] border-l border-[#efe6dc] px-6 py-8 xl:block">
            <div class="sticky top-24">
                <div class="mb-3 text-xs font-semibold uppercase text-zinc-500">On this page</div>
                <nav class="space-y-1.5">
                    @forelse ($page['headings'] as $heading)
                        <a href="#{{ $heading['id'] }}" @class([
                            'block rounded-sm py-0.5 text-sm leading-6 text-zinc-600 transition hover:text-[#ff2d20]',
                            'pl-4 text-zinc-500' => $heading['level'] === 3,
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
