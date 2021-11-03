<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use App\Models\Post;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const S3_IMAGES_FOLDER  = 'avatars/';
    const S3_DISK           = 's3';
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Show image that is fetched from the S3 server
     *
     * @param String $image
     * @return String
     */
    public static function showAvatar($image)
    {
        return config('app.env') === 'local'
                ? asset('/storage/avatars/' . $image) 
                : Storage::disk(self::S3_DISK)->temporaryUrl(
                    self::S3_IMAGES_FOLDER . $image,
                    now()->addMinutes(10)
                );
    }
}
