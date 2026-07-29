@extends(config('page-versioning.layout', 'layouts.app'))

@section('title', $version->title ?? $page->slug)

@section('content')
    <div class="py-12 bg-gray-50 min-h-screen">
        <div
            class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sm:p-12">
            <!-- Page Header -->
            <div class="border-b border-gray-200 pb-6 mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-2">
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ ucfirst($page->type) }}
                    </span>

                    @if ($version->published_at)
                        <div class="text-xs text-gray-500 font-medium">
                            Effective Date: {{ $version->published_at->format('F d, Y') }}
                        </div>
                    @endif
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 tracking-tight">
                    {{ $version->title }}
                </h1>

                <!-- Version Reference Meta -->
                <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                    <span class="font-medium text-gray-700 bg-gray-100 px-2.5 py-1 rounded-md">
                        Version: {{ $version->version_name ?: $version->version_code }} ({{ $version->version_code }})
                    </span>
                    @if ($version->change_summary)
                        <span class="italic">
                            "{{ $version->change_summary }}"
                        </span>
                    @endif
                </div>
            </div>

            <!-- Page Rich Content -->
            <div class="prose prose-emerald max-w-none text-gray-700 leading-relaxed space-y-6">
                {!! $version->content !!}
            </div>
        </div>
    </div>
@endsection
