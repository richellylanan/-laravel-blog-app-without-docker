<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;

class PostController extends Controller
{
    const LOCAL_STORAGE_FOLDER = 'public/images/';
    const S3_IMAGES_FOLDER  = 'images/';

    const LOCAL_DISK        = 'local';
    const S3_DISK           = 's3';

    private $post;

    /**
     * Create a new controller instance.
     *
     * @param Post $post
     * @return void
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        $posts = $this->post->latest()->get();

        return view('posts.index')->with('posts', $posts);
    }

    /**
     * Create a newly created resource in db.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Redirect
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in db.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Redirect
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:1|max:50',
            'body'  => 'required|min:1|max:1000',
            'image' => 'required|mimes:jpg,png,jpeg,gif|max:1048'
        ]);

        $post           = new Post;
        $post->user_id  = Auth::user()->id;
        $post->title    = $request->title;
        $post->body     = $request->body;
        $post->image    = $this->saveImage($request);
        $post->save();

        return redirect()->route('index');
    }

    /**
     * Show the specified resource.
     *
     * @param  Integer $id
     * @return View
     */
    public function show($id)
    {
        $post = $this->post->findOrFail($id);
        $comments = $post->comments->sortByDesc('id');

        return view('posts.show')
                ->with('post', $post)
                ->with('comments', $comments);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Integer $id
     * @return View
     */
    public function edit($id)
    {
        $post = $this->post->findOrFail($id);

        return view('posts.edit')->with('post', $post);
    }

    /**
     * Update the specified resource in db.
     *
     * @param  Integer $id
     * @param  \Illuminate\Http\Request  $request
     * @return Redirect
     */
    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|min:1|max:50',
            'body'  => 'required|min:1|max:1000',
            'image' => 'mimes:jpg,png,jpeg,gif|max:1048'
        ]);

        $post           = $this->post->find($id);
        $post->title    = $request->title;
        $post->body     = $request->body;
        
        if ($request->image) {
            $post->image = $this->saveImage($request, $post->id);
        }

        $post->save();

        return redirect()->route('index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer $id
     * @return Redirect
     */
    public function destroy($id)
    {
        $this->deletePostImage($id);
        $this->post->destroy($id);

        return redirect()->back();
    }

    /**
     * Update and rename image file for saving in local / S3
     *
     * @param Request $request
     * @param Int $postId
     * @return String
     */
    private function saveImage($request, $postId = null)
    {
        # rename the image to remove the risk of overwriting 
        $name       = time().'.'.$request->image->extension();
        $storage    = config('app.env') !== 'local' ? 's3' : '';
        $folder     = config('app.env') !== 'local' ? self::S3_IMAGES_FOLDER : self::LOCAL_STORAGE_FOLDER;

        $request->image->storeAs($folder, $name, $storage);

        # delete if there's any existing image to be overwritten
        $this->deletePostImage($postId);

        return $name;
    }

    /**
     * Delete post image when deleting the post
     *
     * @param Integer $postId
     * @return Void
     */
    public function deletePostImage($postId)
    {
        $postImage  = $this->post->where('id', $postId)->pluck('image')->first();
        $folder     = config('app.env') === 'local' 
                    ? self::LOCAL_STORAGE_FOLDER 
                    : self::S3_IMAGES_FOLDER;

        $disk   = config('app.env') === 'local' 
                ? self::LOCAL_DISK 
                : self::S3_DISK;

        if ($postImage) {
            if (Storage::disk($disk)->exists($folder . $postImage)) {
                Storage::disk($disk)->delete($folder . $postImage);
            }
        }
    }
}
