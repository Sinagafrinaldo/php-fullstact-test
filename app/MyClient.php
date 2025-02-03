namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class MyClient extends Model
{
    use SoftDeletes;

    protected $table = 'my_client';
    protected $fillable = [
        'name', 'slug', 'is_project', 'self_capture', 
        'client_prefix', 'client_logo', 'address', 
        'phone_number', 'city'
    ];

    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($client) {
            Redis::set("client:{$client->slug}", json_encode($client));
        });

        static::updated(function ($client) {
            Redis::del("client:{$client->slug}");
            Redis::set("client:{$client->slug}", json_encode($client));
        });

        static::deleted(function ($client) {
            Redis::del("client:{$client->slug}");
        });
    }
}
