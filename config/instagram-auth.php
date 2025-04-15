<?php
require_once __DIR__ . '/config.php';

class InstagramAuth {
    private $appId;
    private $appSecret;
    private $redirectUri;
    private $apiVersion = 'v18.0';
    private $graphUrl = 'https://graph.facebook.com';
    
    public function __construct() {
        $this->appId = FACEBOOK_APP_ID;
        $this->appSecret = FACEBOOK_APP_SECRET;
        $this->redirectUri = INSTAGRAM_REDIRECT_URI;
    }
    
    public function getLoginUrl() {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'instagram_basic,instagram_manage_comments,pages_show_list',
            'response_type' => 'code',
            'state' => $this->generateState()
        ];
        
        // Correct OAuth URL for Instagram Business Login
        return 'https://www.facebook.com/' . $this->apiVersion . '/dialog/oauth?' . http_build_query($params);
    }
    
    private function generateState() {
        $state = bin2hex(random_bytes(16));
        $_SESSION['instagram_auth_state'] = $state;
        return $state;
    }
    
    public function getAccessToken($code, $state) {
        // Verify state first
        if (!isset($_SESSION['instagram_auth_state']) || $_SESSION['instagram_auth_state'] !== $state) {
            throw new Exception('Invalid state parameter');
        }
    
        $url = $this->graphUrl . '/' . $this->apiVersion . '/oauth/access_token';
        $data = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ];
    
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json']
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);  // Get cURL error if any
        curl_close($ch);
    
        if ($httpCode !== 200) {
            // Log or display the cURL error and response for debugging
            throw new Exception('Failed to get access token: ' . $response . ' - cURL Error: ' . $curlError);
        }
    
        return json_decode($response, true);
    }
    
    
    public function getLongLivedToken($shortLivedToken) {
        $url = $this->graphUrl . '/' . $this->apiVersion . '/oauth/access_token';
        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getInstagramAccount($accessToken) {
        // First get the Facebook Page connected to Instagram
        $url = $this->graphUrl . '/' . $this->apiVersion . '/me/accounts?fields=instagram_business_account,access_token&access_token=' . $accessToken;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'][0]['instagram_business_account'])) {
            throw new Exception('No Instagram Business Account connected to this Facebook Page');
        }
        
        return [
            'instagram_id' => $data['data'][0]['instagram_business_account']['id'],
            'page_access_token' => $data['data'][0]['access_token']
        ];
    }
    
    public function getUserProfile($instagramId, $accessToken) {
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$instagramId}?fields=id,username,website,name,profile_picture_url,followers_count,media_count&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getUserMedia($instagramId, $accessToken, $limit = 10) {
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$instagramId}/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,comments_count,like_count&limit={$limit}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getMediaComments($mediaId, $accessToken) {
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$mediaId}/comments?fields=id,text,username,timestamp,replies{id,text,username,timestamp}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function postCommentReply($commentId, $message, $accessToken) {
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$commentId}/replies";
        $data = [
            'message' => $message,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>