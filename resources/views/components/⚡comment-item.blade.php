<?php

use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Comment $comment;

    public bool $isEditing = false;

    public string $content = '';

    public array $images = [];

    public function removeImage($index)
    {
        $image = $this->images[$index];
        $image->delete();
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    public function save()
    {
        $this->authorize('update', $this->comment);

        $this->validate([
            'content' => 'required|string|max:5000',
            'images.*' => 'image|max:10240',
            'images' => 'max:' . $this->getMaxAllowedUploads(),
        ]);

        DB::transaction(function () {
            $this->comment->lockForUpdate();

            $this->comment->update([
                'content' => $this->pull('content'),
                'edited_by' => Auth::id(),
                'edited_at' => now(),
            ]);

            foreach ($this->images as $image) {
                $this->comment->attachImage($image);
            }
        });

        $this->reset('images');
        $this->comment->task->touch();
        $this->isEditing = false;
    }

    public function startEditing()
    {
        $this->content = $this->comment->content;
        $this->isEditing = true;
    }

    private function getMaxAllowedUploads(): int
    {
        return max(0, 4 - $this->comment->images()->count());
    }
};
?>

<div>
    <div class="flex gap-3">
        <flux:avatar
            circle
            size="sm"
            :src="$comment->user->profilePhotoUrl()"
            name="{{ $comment->user->name }}"
            color="auto"
            color:seed="{{ $comment->user->id }}"
            tooltip="{{ $comment->user->name }}"
        />
        <div class="flex-1 space-y-1">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1">
                    <div class="flex items-center gap-2">
                        <flux:heading>{{ $comment->user->name }}</flux:heading>
                        <flux:text class="text-xs">
                            {{ $comment->created_at->diffForHumans() }}
                        </flux:text>
                    </div>
                    @if ($comment->edited_at)
                        <div class="flex items-center gap-1">
                            <flux:text>·</flux:text>
                            <flux:text class="text-xs">edited by {{ $comment->editor->name }}</flux:text>
                        </div>
                    @endif
                </div>
                <div class="flex gap-1">
                    @if (Auth::user()->can('update', $comment) || Auth::user()->can('delete', $comment))
                        <flux:dropdown position="bottom" align="end">
                            <flux:button size="xs" icon="ellipsis-horizontal" variant="subtle" />
                            <flux:menu>
                                @if (Auth::user()->can('update', $comment))
                                    <flux:menu.item icon="pencil" wire:click="startEditing">Edit</flux:menu.item>
                                @endif

                                @if (Auth::user()->can('delete', $comment))
                                    <flux:modal.trigger :name="'delete-comment-' . $comment->id">
                                        <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
                                    </flux:modal.trigger>
                                @endif
                            </flux:menu>
                        </flux:dropdown>
                    @endif
                </div>
            </div>

            @if ($isEditing)
                <form wire:submit="save" wire:show="isEditing" wire:cloak class="space-y-2">
                    <flux:composer wire:model="content" rows="3" max-rows="8" placeholder="Edit your comment...">
                        <x-slot name="header">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($this->images as $index => $image)
                                    @if (is_object($image) && $image->isPreviewable())
                                        <div
                                            class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700"
                                        >
                                            <img
                                                src="{{ $image->temporaryUrl() }}"
                                                alt="Uploaded image"
                                                class="size-14"
                                            />
                                            <div class="absolute top-0 right-0 p-1">
                                                <button
                                                    type="button"
                                                    wire:click="removeImage({{ $index }})"
                                                    class="flex items-center justify-center rounded-full bg-zinc-900/50 p-0.5 hover:bg-zinc-900/70"
                                                >
                                                    <flux:icon icon="x-mark" variant="micro" class="text-white" />
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </x-slot>
                        <x-slot name="input">
                            <flux:editor
                                variant="borderless"
                                toolbar="bold italic | link"
                                placeholder="Edit your comment..."
                            />
                        </x-slot>
                        <x-slot name="actionsLeading">
                            <div>
                                <flux:file-upload wire:model="images" multiple>
                                    <flux:button size="sm" variant="subtle" icon="paper-clip" />
                                </flux:file-upload>
                                <flux:error name="images" />
                            </div>
                        </x-slot>
                        <x-slot name="actionsTrailing">
                            <flux:button type="button" size="sm" variant="subtle" wire:click="$js.cancelEditing">
                                Cancel
                            </flux:button>
                            <flux:button type="submit" size="sm" variant="primary">Update comment</flux:button>
                        </x-slot>
                    </flux:composer>
                </form>
            @endif

            <div wire:show="!isEditing" class="space-y-2">
                @unless ($comment->images->isEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($comment->images as $image)
                            <img
                                src="{{ $image->url() }}"
                                alt="Comment image"
                                class="size-24 rounded-lg object-cover"
                            />
                        @endforeach
                    </div>
                @endunless

                <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none">
                    {!! $comment->content !!}
                </div>
            </div>
        </div>
    </div>

    <flux:modal :name="'delete-comment-' . $comment->id" class="min-w-[22rem]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete comment?</flux:heading>
                <flux:text class="mt-2">You're about to delete this comment. This action cannot be reversed.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="button" variant="danger" wire:click="$parent.deleteComment({{ $comment->id }})">
                    Delete comment
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

<script>
    this.$js.cancelEditing = () => {
        this.isEditing = false;
    };
</script>
