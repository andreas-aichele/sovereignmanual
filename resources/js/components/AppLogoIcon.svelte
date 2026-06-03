<script lang="ts">
    let {
        class: className = '',
        title = 'Sovereign Manual',
        ...rest
    }: {
        class?: string;
        title?: string;
        [key: string]: unknown;
    } = $props();
</script>

<img src="/logo.svg" alt={title} class={className} {...rest} />
