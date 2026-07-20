<article>
    <a class="card card-border card-side bg-base-200 h-full overflow-hidden shadow-sm transition-shadow hover:shadow-lg md:flex-col"
        href="{{ $post['url'] }}">
        <figure
            class="bg-base-300 md:aspect-16/10 aspect-square w-2/5 shrink-0 md:w-full">
            <x-img class="h-full w-full object-cover" :src="$post['image']"
                :alt="$post['image_alt'] ?? $post['title']" :responsive="$post['image_responsive']"
                sizes="(min-width: 48rem) 33vw, 40vw" />
        </figure>

        <div class="card-body w-3/5 gap-2 p-4 md:w-full md:gap-3 md:p-5 lg:p-6">
            <div class="flex flex-wrap gap-2">
                <span class="badge badge-outline badge-sm">{{ $post['content_type_label'] }}</span>
                <span class="badge badge-ghost badge-sm">{{ $post['category_label'] }}</span>
            </div>

            @if ($headingLevel === 2)
                <h2
                    class="card-title wrap-anywhere text-md line-clamp-4 text-ellipsis leading-tight md:line-clamp-3 md:text-lg lg:text-xl">
                    {{ $post['title'] }}
                </h2>
            @else
                <h3
                    class="card-title wrap-anywhere text-md line-clamp-4 text-ellipsis leading-tight md:line-clamp-3 md:text-lg lg:text-xl">
                    {{ $post['title'] }}
                </h3>
            @endif

            @if ($post['excerpt'])
                <p
                    class="text-base-content/70 line-clamp-3 text-sm max-md:hidden">
                    {{ $post['excerpt'] }}</p>
            @endif
        </div>
    </a>
</article>
