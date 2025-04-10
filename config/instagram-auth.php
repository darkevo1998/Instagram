<?php
require_once __DIR__ . '/config.php';

class InstagramAuth {
    private $appId;
    private $appSecret;
    private $redirectUri;
    private $apiVersion = 'v12.0'; // Use latest stable API version
    
    public function __construct() {
        $this->appId = INSTAGRAM_APP_ID;
        $this->appSecret = INSTAGRAM_APP_SECRET;
        $this->redirectUri = INSTAGRAM_REDIRECT_URI;
    }
    
    public function getLoginUrl() {
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
            'state' => $this->generateState()
        ];
        
        return 'https://api.instagram.com/oauth/authorize?' . http_build_query($params);
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
        
        $url = 'https://api.instagram.com/oauth/access_token';
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get access token: ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    public function getUserProfile($accessToken) {
        $url = "https://graph.instagram.com/me?fields=id,username,account_type,media_count&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getUserMedia($accessToken, $limit = 10) {
        $url = "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,comments_count,like_count&limit={$limit}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function getMediaComments($mediaId, $accessToken) {
        $url = "https://graph.instagram.com/{$mediaId}/comments?fields=id,text,username,timestamp,replies{id,text,username,timestamp}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    public function postCommentReply($commentId, $message, $accessToken) {
        $url = "https://graph.instagram.com/{$commentId}/replies?message={$message}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
?>