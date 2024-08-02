<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;
use App\Models\CustomConnector;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Http\Requests\StreamRequest;

class CustomConnectorController extends Controller
{
    public function index()
    {
        $connectors = CustomConnector::all();
        return response()->json($connectors);
    }

    public function createConnector(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'base_url' => 'required|url',
            'auth_type' => 'required|string|in:none,api_key,bearer,basic,oauth,session_token',
            'auth_details' => 'nullable|array',
        ]);

        $connector = CustomConnector::create($validated);

        return response()->json(['message' => 'Connector created successfully', 'data' => $connector]);
    }

    public function addStream(Request $request, $id)
    {
        $connector = CustomConnector::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'url' => 'required|string',
        ]);


        $streams = json_decode($connector->streams, true) ?? [];
        $streams[] = $validated;

        $connector->update(['streams' => json_encode($streams)]);

        return response()->json(['message' => 'Stream added successfully', 'data' => $connector]);
    }
    public function testStreamByUrl($url)
    {
        $decodedUrl = urldecode($url);

        // Extract the path part from the URL
        $urlPath = parse_url($decodedUrl, PHP_URL_PATH);

        // Find the connector that matches the stream URL or base URL
        $connectors = CustomConnector::all();
        $matchedConnector = null;
        $matchedStream = null;

        foreach ($connectors as $connector) {
            $streams = json_decode($connector->streams, true);

            // Check if any stream URL matches the decoded URL or URL path
            foreach ($streams as $stream) {
                $streamUrl = filter_var($stream['url'], FILTER_VALIDATE_URL) ? $stream['url'] : rtrim($connector->base_url, '/') . '/' . ltrim($stream['url'], '/');
                if ($streamUrl == $decodedUrl || $stream['url'] == '/' . $urlPath) {
                    $matchedConnector = $connector;
                    $matchedStream = $stream;
                    break 2; // Break both loops
                }
            }
        }

        if (!$matchedConnector || !$matchedStream) {
            return response()->json(['message' => 'Connector not found for the provided URL'], 404);
        }

        // Determine if the stream URL is a full URL or a relative path
        $streamUrl = filter_var($matchedStream['url'], FILTER_VALIDATE_URL) ? $matchedStream['url'] : rtrim($matchedConnector->base_url, '/') . '/' . ltrim($matchedStream['url'], '/');

        // Make the authenticated request
        $response = $this->makeAuthenticatedRequest($matchedConnector, $streamUrl);

        if ($response->successful()) {
            $matchedConnector->update(['published' => true]);
            return response()->json(['message' => 'Stream tested successfully and connector published', 'data' => $response->json()]);
        }

        return response()->json(['message' => 'Failed to test stream', 'error' => $response->body()], 400);
    }

    private function makeAuthenticatedRequest($connector, $url)
    {
        switch ($connector->auth_type) {
            case 'api_key':
                return Http::withHeaders([
                    'Authorization' => 'API-Key ' . $connector->auth_details['api_key']
                ])->get($url);
            case 'bearer':
                return Http::withToken($connector->auth_details['token'])->get($url);
            case 'basic':
                return Http::withBasicAuth($connector->auth_details['username'], $connector->auth_details['password'])->get($url);
            case 'oauth':
                // Implement OAuth logic here
                break;
            case 'session_token':
                return Http::withHeaders([
                    'Authorization' => 'Session ' . $connector->auth_details['token']
                ])->get($url);
            default:
                return Http::get($url);
        }
    }


   

    public function testUrl(Request $request)
    {
       
        $validated = $request->validate([
            'url' => 'required|url',
            'auth_type'=>'required|string',
            'stream_url'=>'required|string'
        ]);


        if($validated['stream_url'] != NULL && !Str::contains($validated['stream_url'] , $validated['url']))
        {
            $validated['stream_url'] = $validated['url'].$validated['stream_url']; 
        }

        $url = $validated['stream_url'];
        

        switch ($validated['auth_type']) {
            case 'api_key':
                return   $this->validateApiKey($request)->fetchDataFromUrl($url);
            case 'bearer':
                // $response =  Http::withToken($connector->auth_details['token']);
            case 'basic':
                return $this->validateBasicHttp($request)->fetchDataFromUrl($url);
            case 'oauth':
                // Implement OAuth logic here
                break;
            case 'session_token':

        }

        return  $this->fetchDataFromUrl($url);
    }

    public function fetchDataFromUrl($url)
    {
        try{
            $response = Http::get($url);
            // Check if the response is successful
            if ($response->successful()) {
                return response()->json([
                    'message' => 'Stream tested successfully',
                    'data' => $response->json(),
                ]);
            }

            // If the response is not successful, return the error
            return response()->json([
                'message' => 'Failed to test stream',
                'status' => $response->status(),
                'error' => $response->body(),
            ], $response->status());
        }
        catch(\Exception $e){
            return response()->json([
                'message' => 'An error occurred while testing the stream',
                'error' => $e->getMessage(),
            ], 500);
        }
        
    }

    protected function validateApiKey(Request $request)
    {
        $validate = $request->validate([
            'api_key' => 'required|string',
        ]);

        $authorizationHeader = $request->header('Authorization');
        return Http::withHeaders([
            'Authorization' => 'API-Key ' . $request['api_key'],
        ]);
    }

    protected function validateBearer(Request $request)
    {
         $response = $request->validate([
            'api_key' => 'required|string',
        ]);
        // return Http::withToken($connector->auth_details['token']);
    }


    protected function validateBasicHttp(Request $request)
    {
        $validate = $request->validate([
            'username' => 'required|string',
            'password' => 'required|password',
        ]);

        return Http::withBasicAuth($validate['username'], $validate['password']);
    }
   
    protected function validateOAuth(Request $request)
    {

    }

    protected function validateSessionTokken(Request $request)
    {
        return Http::withHeaders([
            // 'Authorization' => 'Session ' . $connector->auth_details['token']
        ]);
    }

    public function publishConnector(Request $request,StreamRequest $streamRequest)
    {
        $validatedMain = $request->validate([
            'base_url' => 'required|url',
            'auth_type' => 'required|string|in:no_auth,api_key,bearer,basic,oauth,session_token',
            'auth_details' => 'nullable|array',
        ]);

        // Get the validated stream data from StreamRequest
        $validatedStream = $streamRequest->validated();


        // Combine the main validation data and stream validation data
        $validated = array_merge($validatedMain, $validatedStream);

        // dd($validated);

        // Handle the validated data
        // For example, saving the data to the database

        $hello = CustomConnector::create($validatedStream);
        
        return response()->json([
            'message' => 'Stream created successfully',
            'data' => $hello
        ]);
    }
}