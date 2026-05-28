<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('view', $task);
        $this->authorize('create', Comment::class);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => $request->user()->id,
            'action'     => 'commented',
            'changes'    => ['body' => Str::limit($comment->body, 120)],
            'created_at' => now(),
        ]);

        return back()->with('status', 'Comment added.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);
        $task = $comment->task;
        $comment->delete();

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => auth()->id(),
            'action'     => 'comment_deleted',
            'created_at' => now(),
        ]);

        return back()->with('status', 'Comment deleted.');
    }
}
