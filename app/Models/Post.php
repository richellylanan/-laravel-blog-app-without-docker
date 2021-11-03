<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Comment;

class Post extends Model
{
    use HasFactory;

    const S3_IMAGES_FOLDER  = 'images/';
    const S3_DISK           = 's3';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'title',
        'body',
        'image',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the comments that is owned by the post.
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Show image that is fetched from the S3 server
     *
     * @param String $image
     * @return String
     */
    public static function showImage($image)
    {
        return config('app.env') === 'local'
                ? asset('/storage/images/' . $image) 
                : Storage::disk(self::S3_DISK)->temporaryUrl(
                    self::S3_IMAGES_FOLDER . $image,
                    now()->addMinutes(10)
                );
    }
}
