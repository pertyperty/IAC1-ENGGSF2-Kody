@props(['source'])
@inject('learningProse', 'App\Support\LearningProse')
<div {{ $attributes }}>
    <div class="[contain:inline-size] whitespace-normal space-y-3 [&_ol]:list-decimal [&_ul]:list-disc [&_ol]:pl-6 [&_ul]:pl-6 [&_pre]:max-w-full [&_pre]:overflow-x-auto [&_pre]:whitespace-pre [&_pre]:rounded-lg [&_pre]:bg-[var(--surface-soft)] [&_pre]:p-3 [&_figcaption]:text-sm [&_figcaption]:text-[var(--muted)]">{{ $learningProse->render($source) }}</div>
</div>
