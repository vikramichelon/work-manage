<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /** Disk where attachments live — 'public' so images are servable
     *  via the storage symlink (asset('storage/...')). Tradeoff: anyone
     *  with the URL can fetch the file, no auth check. Acceptable for
     *  the small team use case. */
    private const DISK = 'public';

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);
        $this->authorize('create', Attachment::class);

        $request->validate([
            'file' => [
                'required', 'file',
                'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,pdf,doc,docx,xls,xlsx,csv,txt,zip',
            ],
        ]);

        self::storeUploaded($request->file('file'), $task, $request->user()->id);

        return back()->with('status', 'File attached.');
    }

    /**
     * Reusable helper: write an UploadedFile to disk, create the Attachment
     * row, and log activity. Called from here AND from TaskController on
     * task create/update to attach files in the same form submit.
     */
    public static function storeUploaded($file, Task $task, int $userId): Attachment
    {
        // Prefix timestamp to avoid name collisions in the same task folder.
        $storedName = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs("attachments/{$task->id}", $storedName, self::DISK);

        $att = Attachment::create([
            'task_id'       => $task->id,
            'user_id'       => $userId,
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
        ]);

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => $userId,
            'action'     => 'attached',
            'changes'    => ['filename' => $att->original_name],
            'created_at' => now(),
        ]);

        return $att;
    }

    /**
     * Serve the file. Images load inline (so <img src="..."> works); other
     * types are sent as attachment to trigger a save dialog.
     * Falls back to the legacy 'local' disk for files uploaded before the
     * switch to public storage.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        $disk = Storage::disk(self::DISK)->exists($attachment->stored_path)
            ? self::DISK
            : 'local';

        abort_unless(Storage::disk($disk)->exists($attachment->stored_path), 404);

        $isImage = str_starts_with((string) $attachment->mime_type, 'image/');
        $headers = ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream'];

        return $isImage
            ? Storage::disk($disk)->response($attachment->stored_path, $attachment->original_name, $headers)
            : Storage::disk($disk)->download($attachment->stored_path, $attachment->original_name, $headers);
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);
        $task = $attachment->task;
        $name = $attachment->original_name;

        // Delete from whichever disk holds the file (public for new, local for legacy).
        foreach ([self::DISK, 'local'] as $disk) {
            if (Storage::disk($disk)->exists($attachment->stored_path)) {
                Storage::disk($disk)->delete($attachment->stored_path);
                break;
            }
        }
        $attachment->delete();

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => auth()->id(),
            'action'     => 'detached',
            'changes'    => ['filename' => $name],
            'created_at' => now(),
        ]);

        return back()->with('status', "File \"{$name}\" removed.");
    }
}
