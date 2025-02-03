namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\MyClient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class MyClientController extends Controller
{
    public function index()
    {
        return response()->json(MyClient::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:my_client,slug',
            'is_project' => 'in:0,1',
            'self_capture' => 'in:0,1',
            'client_prefix' => 'required|max:4',
            'client_logo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'nullable',
            'phone_number' => 'nullable',
            'city' => 'nullable'
        ]);

        if ($request->hasFile('client_logo')) {
            $data['client_logo'] = Storage::disk('s3')->put('clients', $request->file('client_logo'));
        }

        $client = MyClient::create($data);
        Redis::set("client:{$client->slug}", json_encode($client));

        return response()->json($client, 201);
    }

    public function show($slug)
    {
        $client = Redis::get("client:$slug");

        if (!$client) {
            $client = MyClient::where('slug', $slug)->firstOrFail();
            Redis::set("client:$slug", json_encode($client));
        } else {
            $client = json_decode($client);
        }

        return response()->json($client);
    }

    public function update(Request $request, $slug)
    {
        $client = MyClient::where('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name' => 'required',
            'is_project' => 'in:0,1',
            'self_capture' => 'in:0,1',
            'client_prefix' => 'required|max:4',
            'client_logo' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'nullable',
            'phone_number' => 'nullable',
            'city' => 'nullable'
        ]);

        if ($request->hasFile('client_logo')) {
            if ($client->client_logo !== 'no-image.jpg') {
                Storage::disk('s3')->delete($client->client_logo);
            }
            $data['client_logo'] = Storage::disk('s3')->put('clients', $request->file('client_logo'));
        }

        $client->update($data);
        Redis::del("client:$slug");
        Redis::set("client:$slug", json_encode($client));

        return response()->json($client);
    }

    public function destroy($slug)
    {
        $client = MyClient::where('slug', $slug)->firstOrFail();
        $client->delete();
        Redis::del("client:$slug");

        return response()->json(['message' => 'Client deleted']);
    }
}
