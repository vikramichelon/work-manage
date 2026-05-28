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
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);
        $this->authorize('create', Attachment::class);

        $request->validate([
            'file' => [
                'required', 'file',
                'max:10240', // 10 MB
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt,zip',
            ],
        ]);

        $file = $request->file('file');
        // Prefix timestamp to avoid name collisions in the same task folder.
        $storedName = time().'_'.preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs("attachments/{$task->id}", $storedName, 'local');

        $att = Attachment::create([
            'task_id'       => $task->id,
            'user_id'       => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path'   => $path,
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
        ]);

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => $request->user()->id,
            'action'     => 'attached',
            'changes'    => ['filename' => $att->original_name],
            'created_at' => now(),
        ]);

        return back()->with('status', "File \"{$att->original_name}\" attached.");
    }

    /** Serve the file inline (images/PDFs) or trigger download. */
    public function download(Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);
        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        $headers = ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream'];

        return Storage::disk('local')->response(
            $attachment->stored_path,
            $attachment->original_name,
            $headers
        );
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $this->authorize('delete', $attachment);
        $task = $attachment->task;
        $name = $attachment->original_name;

        Storage::disk('local')->delete($attachment->stored_path);
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
