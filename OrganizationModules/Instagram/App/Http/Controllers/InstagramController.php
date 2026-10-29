<?php

namespace OrganizationModules\Instagram\App\Http\Controllers;

use App\Helpers\CustomHelper;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramController extends Controller
{
    private $instagramClientId;
    private $instagramClientSecret;

    public function __construct()
    {
        // $this->instagramClientId = '1088421895625352';
        // $this->instagramClientSecret = '0a9ade3796160c7bade8e4e47e9cff1f';

        $this->instagramClientId = CustomHelper::config('instagram_client_id');
        $this->instagramClientSecret = CustomHelper::config('instagram_client_secret');
    }

    /**
     * Redirect to instagram OAuth for Instagram integration.
     */
    public function redirectToInstagram()
    {
        try {
            $this->validateOAuthParameters();
    
            $scopes = implode(',', $this->getRequiredScopes());
            $queryString = http_build_query([
                'client_id'     => $this->instagramClientId,
                'redirect_uri'  => route('instagram.callback'),
                'scope'         => $scopes,
                'response_type' => 'code',
            ]);
    
            // Instagram Login without Facebook Page linking
            $instagramAuthUrl = "https://www.instagram.com/oauth/authorize?$queryString";
            Log::info('Generated Instagram Auth URL: ' . $instagramAuthUrl);
    
            return response()->json(['redirectUrl' => $instagramAuthUrl]);
        } catch (\Exception $e) {
            Log::error('Error in Instagram OAuth redirect: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate Instagram login URL.'], 500);
        }
    }
    

    private function validateOAuthParameters()
    {
        $clientId = $this->instagramClientId;
        $redirectUri = route('instagram.callback');
        $organizationId = session()->get('current_organization');

        if (!$clientId) {
            throw new \Exception('instagram Client ID is not configured.');
        }
        if (!$redirectUri) {
            throw new \Exception('Redirect URI is not properly configured.');
        }
        if (!$organizationId) {
            throw new \Exception('Organization ID is not found in session.');
        }
    }

    /**
     * Get required OAuth scopes.
     */
    private function getRequiredScopes()
    {
        return [
            'instagram_business_basic',
            'instagram_business_content_publish',
            'instagram_business_manage_messages',
            'instagram_business_manage_comments',

        ];
    }


    public function handleInstagramCallback(Request $request)
    {
        try {
            $organization = Organization::find(session('current_organization'));
            Log::info('Instagram callback received', ['organization' => $organization]);
    
            if (!$organization) {
                throw new \Exception('Organization not found.');
            }
    
            $code = $request->input('code');
            if (!$code) {
                throw new \Exception('Authorization code not received.');
            }
    
            // Step 2: Exchange code for a short-lived token then long-lived token
            $tokenData = $this->exchangeCodeForTokens($code, $organization->id);
    
            return redirect()->route('integrations')->with('status', [
                'type' => 'success',
                'message' => __('Instagram connected successfully!'),
                'view_toast' => true
            ]);
    
        } catch (\Exception $e) {
            Log::error('Instagram integration error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return redirect()->route('dashboard')->withErrors([
                'error' => config('app.debug') 
                    ? 'Instagram integration error: ' . $e->getMessage() 
                    : 'An error occurred while connecting Instagram.'
            ]);
        }
    }
    
    private function exchangeCodeForTokens($code, $organizationId)
    {
        Log::info('Exchanging code for short-lived token', ['code' => $code]);
    
        $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id'     => $this->instagramClientId,
            'client_secret' => $this->instagramClientSecret,
            'redirect_uri'  => route('instagram.callback'),
            'code'          => $code,
            'grant_type'    => 'authorization_code',
        ]);
    
        Log::info('Short-lived token response', ['status' => $response->status(), 'body' => $response->json()]);
    
        if ($response->failed()) {
            Log::error('Failed to obtain short-lived token', ['response' => $response->body()]);
            throw new \Exception('Failed to obtain short-lived access token.');
        }
    
        return $this->exchangeForLongLivedToken($response->json(), $organizationId);
    }
    
    public function exchangeForLongLivedToken($data, $organizationId)
    {
        Log::info('Exchanging short-lived token for long-lived token', ['short_lived_token' => $data['access_token']]);
    
        $response = Http::get('https://graph.instagram.com/access_token', [
            'grant_type'    => 'ig_exchange_token',
            'client_secret' => $this->instagramClientSecret,
            'access_token'  => $data['access_token'],
        ]);
    
        Log::info('Long-lived token response', ['status' => $response->status(), 'body' => $response->json()]);
    
        if ($response->failed()) {
            Log::error('Failed to obtain long-lived token', ['response' => $response->body()]);
            throw new \Exception('Failed to obtain long-lived access token.');
        }
    
        return $this->getInstagramAccountDetails($response->json(), $organizationId);
    }
    
    public function getInstagramAccountDetails($longLivedData, $organizationId)
    {
        Log::info('Fetching Instagram account details', ['access_token' => $longLivedData['access_token']]);
    
        $response = Http::get("https://graph.instagram.com/me", [
            'fields'       => 'user_id,username,account_type',
            'access_token' => $longLivedData['access_token'],
        ]);
    
        Log::info('Instagram account details response', ['status' => $response->status(), 'body' => $response->json()]);
    
        if ($response->failed()) {
            Log::error('Failed to fetch Instagram account details', ['response' => $response->body()]);
            throw new \Exception('Failed to fetch Instagram account details.');
        }
    
        return $this->subscribeToWebhooks($response->json(), $longLivedData, $organizationId);
    }
    
    public function subscribeToWebhooks($userData, $longLivedData, $organizationId)
    {
        Log::info('Subscribing to Instagram webhooks', ['userData' => $userData]);
    
        $response = Http::post("https://graph.instagram.com/{$userData['user_id']}/subscribed_apps", [
            'subscribed_fields' => 'comments,messages',
            'access_token'      => $longLivedData['access_token'],
        ]);
    
        Log::info('Webhook subscription response', ['status' => $response->status(), 'body' => $response->json()]);
    
        if ($response->failed()) {
            Log::error('Failed to subscribe to Instagram webhooks', ['response' => $response->body()]);
            throw new \Exception('Failed to subscribe to Instagram webhooks.');
        }
    
        return $this->saveIntegrationData($longLivedData, $userData, $organizationId);
    }
    
    private function saveIntegrationData($longLivedData, $userData, $organizationId)
    {
        Log::info('Saving Instagram integration data', [
            'longLivedData' => $longLivedData,
            'userData' => $userData,
            'organizationId' => $organizationId
        ]);
    
        $integration = OrganizationIntegration::updateOrCreate(
            ['organization_id' => $organizationId, 'name' => 'instagram'],
            [
                'metadata' => [
                    'instagram_user_id'       => $userData['user_id'],
                    'username'                => $userData['username'],
                    'account_type'            => $userData['account_type'],
                    'long_lived_access_token' => $longLivedData['access_token'],
                    'expires_in'              => now()->addSeconds($longLivedData['expires_in'] ?? 5183944)->toIso8601String(),
                    'last_updated'            => now()->toIso8601String(),
                ],
                'active' => true,
            ]
        );
    
        Log::info('Integration saved successfully', ['integration' => $integration]);
        return $integration;
    }

    public function retriveMessages()
    {

    }
    
    public function refreshLongLivedToken($accessToken)
    {
        Log::info('Refreshing long-lived token', ['accessToken' => $accessToken]);
    
        $response = Http::get('https://graph.instagram.com/refresh_access_token', [
            'grant_type'   => 'ig_refresh_token',
            'access_token' => $accessToken,
        ]);
    
        Log::info('Refresh token response', ['status' => $response->status(), 'body' => $response->json()]);
    
        if ($response->failed()) {
            Log::error('Failed to refresh long-lived token', ['response' => $response->body()]);
            throw new \Exception('Failed to refresh long-lived access token.');
        }
    
        return $response->json();
    }
    


    public function deleteIntegration(Request $request)
    {
        $request->validate(['name' => 'required']);
        $integartion = OrganizationIntegration::where('organization_id', session()->get('current_organization'))->where('name', $request->name)->first();

        if (!$integartion) {
            return response()->json([
                'type' => 'failed',
                'message' => __('An error occurred while deleting Instagram.')
            ], 401);
        }
        $integartion->delete();

        return response()->json([
            'type' => 'success',
            'message' => __('Instagram deleted successfully!')
        ]);
    }
}
