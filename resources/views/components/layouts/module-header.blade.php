@props(['title', 'description' => null, 'icon' => null, 'actions' => null])

<div class="bg-white dark:bg-zinc-800 shadow">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center">
                    @if ($icon)
                        <div class="mr-4 flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-500 dark:text-indigo-300">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
                            </svg>
                        </div>
                    @endif
                    <div>
                        <div class="flex items-center">
                            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:leading-9 sm:truncate">
                                {{ $title }}
                            </h2>
                            @if ($attributes->has('badge'))
                                <span class="ml-3 inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $attributes->get('badge') }}
                                </span>
                            @endif
                        </div>
                        @if ($description)
                            <div class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $description }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @if ($actions)
                <div class="mt-4 flex sm:mt-0 sm:ml-4">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </div>
</div>
