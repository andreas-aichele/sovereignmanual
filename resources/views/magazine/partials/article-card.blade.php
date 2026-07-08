<article>
    <a class="card card-border card-side bg-base-200 h-full overflow-hidden shadow-sm transition-shadow hover:shadow-lg md:flex-col"
        href="{{ $post['url'] }}">
        <figure class="bg-base-300 aspect-square w-2/5 shrink-0 md:aspect-16/10 md:w-full">
            <x-img class="h-full w-full object-cover" :src="$post['image']" :alt="$post['image_alt'] ?? $post['title']"
                :responsive="$post['image_responsive']" sizes="(min-width: 48rem) 33vw, 40vw" />
        </figure>

        <div class="card-body w-3/5 gap-2 p-4 md:w-full md:gap-3 md:p-5 lg:p-6">
            <div class="badge badge-primary badge-outline badge-sm">
                {{ $post['category_label'] }}</div>

            @if ($headingLevel === 2)
                <h2
                    class="card-title wrap-anywhere text-md leading-tight md:text-lg lg:text-xl line-clamp-4 md:line-clamp-3 text-ellipsis">
                    {{ $post['title'] }}
                </h2>
            @else
                <h3
                    class="card-title wrap-anywhere text-md leading-tight md:text-lg lg:text-xl line-clamp-4 md:line-clamp-3 text-ellipsis">
                    {{ $post['title'] }}
                </h3>
            @endif

            @if ($post['excerpt'])
                <p class="text-base-content/70 max-md:hidden line-clamp-3 text-sm">
                    {{ $post['excerpt'] }}</p>
            @endif
        </div>
    </a>
</article>
