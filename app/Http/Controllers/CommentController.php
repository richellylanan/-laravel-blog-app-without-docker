<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;

class CommentController extends Controller
{
    private $comment;

    /**
     * Create a new controller instance.
     *
     * @param Comment $comment
     * @return void
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function store($id, Request $request)
    {
        $validated = $request->validate([
            'comment'  => 'required|min:1|max:150'
        ]);

        $comment           = new Comment;
        $comment->user_id  = Auth::user()->id;
        $comment->post_id  = $id;
        $comment->body     = $request->comment;
        $comment->save();

        return redirect()->back();
    }

    /**
     * Remove the specified resource from db.
     *
     * @param  Integer $id
     * @return Redirect
     */
    public function destroy($id)
    {
        $this->comment->destroy($id);

        return redirect()->back();
    }
}
