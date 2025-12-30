<div
    @class([
        'text-base leading-[1.7142857] text-zinc-700 dark:text-zinc-300',
        'sm:text-sm',
        '[&_a]:font-medium [&_a]:text-blue-600 dark:[&_a]:text-[#6791fd]',
        '[&_code:not(pre_code)]:inline-block [&_code:not(pre_code)]:rounded-lg [&_code:not(pre_code)]:bg-zinc-800/10 [&_code:not(pre_code)]:px-1.5 [&_code:not(pre_code)]:font-mono [&_code:not(pre_code)]:text-[.8275rem] [&_code:not(pre_code)]:whitespace-nowrap',
        '[&_li]:my-[0.2857143em]',
        '[&_pre]:rounded-lg [&_pre]:bg-zinc-800/10 [&_pre]:p-4',
        '[&_pre_code]:font-mono [&_pre_code]:font-medium',
        '[&_strong]:font-semibold',
        '[&>*:first-child]:mt-0 [&>*:last-child]:mb-0',
        '[&>blockquote]:relative [&>blockquote]:my-[1.3333333em] [&>blockquote]:ps-[1.1111111em] [&>blockquote]:italic [&>blockquote:before]:absolute [&>blockquote:before]:inset-y-0 [&>blockquote:before]:left-0 [&>blockquote:before]:w-[0.25rem] [&>blockquote:before]:rounded-[0.25rem] [&>blockquote:before]:bg-black/20 [&>blockquote:before]:content-[\'\']',
        '[&>h1]:mt-0 [&>h1]:mb-[0.8em] [&>h1]:text-[2.1428571em] [&>h1]:leading-[1.2] [&>h1]:font-extrabold',
        '[&>h2]:mt-[1.6em] [&>h2]:mb-[0.8em] [&>h2]:text-[1.4285714em] [&>h2]:leading-[1.4] [&>h2]:font-bold',
        '[&>h3]:mt-[1.5555556em] [&>h3]:mb-[0.4444444em] [&>h3]:text-[1.2857143em] [&>h3]:leading-[1.5555556] [&>h3]:font-semibold',
        '[&>img]:my-[1.7142857em]',
        '[&>ol]:my-[1.1428571em] [&>ol]:ps-[1.5714286em] [&>ol>li]:list-decimal [&>ol>li]:ps-[0.4285714em]',
        '[&>p]:my-[1.1428571em]',
        '[&>picture]:my-[1.7142857em]',
        '[&>ul]:my-[1.1428571em] [&>ul]:ps-[1.5714286em] [&>ul>li]:list-disc [&>ul>li]:ps-[0.4285714em]',
        '[&>video]:my-[1.7142857em]',
    ])
>
    {{ $slot }}
</div>
