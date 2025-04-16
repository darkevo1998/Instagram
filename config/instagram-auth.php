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
        $this->logMessage("InstagramAuth initialized with App ID: " . substr($this->appId, 0, 4) . "...");
    }
    
    public function getLoginUrl() {
        $state = $this->generateState();
        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'instagram_basic,instagram_manage_comments,pages_show_list,instagram_manage_messages,business_management',
            'response_type' => 'code',
            'state' => $state,
            'auth_type' => 'reauthenticate'
        ];
        
        $loginUrl = 'https://www.facebook.com/' . $this->apiVersion . '/dialog/oauth?' . http_build_query($params);
        $this->logMessage("Generated login URL with state: $state");
        
        return $loginUrl;
    }
    
    private function generateState() {
        $state = bin2hex(random_bytes(16));
        $_SESSION['instagram_auth_state'] = $state;
        $this->logMessage("Generated new state parameter: $state");
        return $state;
    }
    
    public function getAccessToken($code, $state) {
        $this->logMessage("Attempting to get access token with code: " . substr($code, 0, 6) . "... and state: $state");
        
        if (!isset($_SESSION['instagram_auth_state']) || $_SESSION['instagram_auth_state'] !== $state) {
            $this->logMessage("State validation failed. Session state: " . ($_SESSION['instagram_auth_state'] ?? 'not set') . ", provided state: $state");
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
    
        $this->logMessage("Requesting access token from: $url with data: " . json_encode(array_merge($data, ['client_secret' => '***'])));
    
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
        $curlError = curl_error($ch);
        curl_close($ch);
    
        $this->logMessage("Access token response - HTTP Code: $httpCode, Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
    
        if ($httpCode !== 200) {
            $this->logMessage("Failed to get access token. Error: $curlError, Full response: $response");
            throw new Exception('Failed to get access token: ' . $response . ' - cURL Error: ' . $curlError);
        }
    
        $tokenData = json_decode($response, true);
        $this->logMessage("Successfully obtained access token. Token expires in: " . ($tokenData['expires_in'] ?? 'unknown') . " seconds");
        
        return $tokenData;
    }
    
    public function getLongLivedToken($shortLivedToken) {
        $this->logMessage("Attempting to exchange short-lived token for long-lived token");
        
        $url = $this->graphUrl . '/' . $this->apiVersion . '/oauth/access_token';
        $params = [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken
        ];
        
        $this->logMessage("Requesting long-lived token from: $url");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->logMessage("Long-lived token response - HTTP Code: $httpCode, Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
        
        $tokenData = json_decode($response, true);
        if (isset($tokenData['error'])) {
            $this->logMessage("Error getting long-lived token: " . json_encode($tokenData['error']));
            throw new Exception('Failed to get long-lived token: ' . $response);
        }
        
        $this->logMessage("Successfully obtained long-lived token. Expires in: " . ($tokenData['expires_in'] ?? 'unknown') . " seconds");
        return $tokenData;
    }
    
    public function getInstagramAccount($accessToken) {
        $this->logMessage("Attempting to get Instagram account connected to Facebook Page");
        
        $url = $this->graphUrl . '/' . $this->apiVersion . '/me/accounts?fields=instagram_business_account,access_token&access_token=' . $accessToken;
        $this->logMessage("Requesting Instagram account info from: " . substr($url, 0, 100) . "...");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->logMessage("Instagram account response - HTTP Code: $httpCode, Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
        
        $data = json_decode($response, true);
        
        if (!isset($data['data'][0]['instagram_business_account'])) {
            $this->logMessage("No Instagram Business Account found in response");
            throw new Exception('No Instagram Business Account connected to this Facebook Page');
        }
        
        $this->logMessage("Found Instagram Business Account: " . $data['data'][0]['instagram_business_account']['id']);
        return [
            'instagram_id' => $data['data'][0]['instagram_business_account']['id'],
            'page_access_token' => $data['data'][0]['access_token']
        ];
    }
    
    public function getUserProfile($instagramId, $accessToken) {
        $this->logMessage("Requesting user profile for Instagram ID: $instagramId");
        
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$instagramId}?fields=id,username,website,name,profile_picture_url,followers_count,media_count&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $this->logMessage("User profile response - HTTP Code: $httpCode, Response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
        
        return json_decode($response, true);
    }
    
    public function getUserMedia($instagramId, $accessToken, $limit = 10) {
        $this->logMessage("Requesting media for Instagram ID: $instagramId, Limit: $limit, Access Token: 
        $accessToken");
        
        $url = $this->graphUrl . '/' . $this->apiVersion . "/{$instagramId}/media?fields=id,caption,media_type,media_url,permalink,thumbnail_url,timestamp,username,comments_count,like_count&limit={$limit}&access_token={$accessToken}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        $itemCount = isset($decodedResponse['data']) ? count($decodedResponse['data']) : 0;
        $this->logMessage("User media response - HTTP Code: $httpCode, Items returned: $itemCount, $response: " . substr($response, 0, 200) . (strlen($response) > 200 ? '...' : ''));
        
        return $decodedResponse;
    }
    
    public function getMediaComments($mediaId, $accessToken) {
        $url = "https://graph.facebook.com/v19.0/{$mediaId}/comments?fields=id,username,message,created_time,comments{username,message,created_time}&access_token={$accessToken}";
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    
        $this->logMessage("Fetching comments for media {$mediaId} from URL: {$url}");
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        $result = json_decode($response, true);
    
        if ($httpCode !== 200) {
            $errorMessage = $result['error']['message'] ?? 'Failed to get comments';
            $this->logMessage("Error fetching comments for media {$mediaId}: HTTP {$httpCode} - {$errorMessage}", 'error');
    
            return [
                'data' => [],
                'error' => $errorMessage
            ];
        }
    
        $commentCount = count($result['data'] ?? []);
        $this->logMessage("Successfully fetched {$commentCount} comments for media {$mediaId}");
    
        return $result;
    }
    


    public function getMedia($mediaId, $accessToken) {
        $apiVersion = 'v18.0'; // Use the latest stable API version
        $url = "https://graph.instagram.com/{$apiVersion}/{$mediaId}";
        
        // Basic fields that don't require special permissions
        $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username';
        
        try {
            // Initialize cURL
            $ch = curl_init();
            
            // Set the URL with parameters
            $requestUrl = "{$url}?fields={$fields}&access_token=" . urlencode($accessToken);
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json'
            ]);
            
            // Execute and get response
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('cURL error: ' . curl_error($ch));
            }
            
            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
                throw new Exception("Instagram API Error {$httpCode}: {$errorMsg} : {$response}");
            }
            
            $mediaData = json_decode($response, true);
            
            // Ensure required fields exist
            return array_merge([
                'id' => $mediaId,
                'media_type' => 'IMAGE',
                'media_url' => '',
                'thumbnail_url' => '',
                'caption' => '',
                'timestamp' => date('c'),
                'username' => '',
                'permalink' => ''
            ], $mediaData);
            
        } catch (Exception $e) {
            error_log("Instagram API request failed: " . $e->getMessage());
            return [
                'error' => true,
                'message' => $e->getMessage(),
                'media_type' => 'ERROR',
                'id' => $mediaId
            ];
        } finally {
            if (isset($ch)) curl_close($ch);
        }
    }

       /**
     * Post a comment on a media item
     * 
     * @param string $mediaId The Instagram media ID
     * @param string $commentText The comment text
     * @param string $accessToken User access token
     * @return array API response
     */
    public function postComment($mediaId, $commentText, $accessToken) {
        // Use Facebook Graph API (v19.0 or latest)
        $url = "https://graph.facebook.com/v19.0/{$mediaId}/comments";
    
        // Optional: validate token
        if (empty($accessToken)) {
            return [
                'error' => 'Missing access token',
                'http_code' => 401
            ];
        }
    
        $params = [
            'message' => $commentText,
            'access_token' => $accessToken
        ];
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
    
        $this->logMessage("Comment API Response - Code: $httpCode, Response: " . substr($response, 0, 500), 'info');
    
        if ($error) {
            return [
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
    
        $result = json_decode($response, true);
    
        if ($httpCode !== 200) {
            return [
                'error' => $result['error']['message'] ?? 'Failed to post comment',
                'http_code' => $httpCode,
                'full_response' => $result
            ];
        }
    
        return $result;
    }
    

    /**
     * Post a reply to a comment
     * 
     * @param string $mediaId The Instagram media ID
     * @param string $commentId The parent comment ID
     * @param string $replyText The reply text
     * @param string $accessToken User access token
     * @return array API response
     */
    public function postCommentReply($mediaId, $commentId, $replyText, $accessToken) {
        // Note: Instagram's API uses the same endpoint for comments and replies
        // The "reply" functionality is handled by including the parent comment ID
        $url = "https://graph.facebook.com/v19.0/{$mediaId}/comments";
        
        $params = [
            'message' => $replyText,
            'reply_to_comment_id' => $commentId,
            'access_token' => $accessToken
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode !== 200) {
            return [
                'error' => $result['error']['message'] ?? 'Failed to post reply'
            ];
        }
        
        return $result;
    }

    /**
     * Validate the access token
     */
    public  function validateAccessToken($accessToken) {
        if (empty($accessToken)) {
            return false;
        }
        
        // Simple format validation
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $accessToken)) {
            return false;
        }
        
        return true;
    }
    
    public function logMessage($message) {
        $logFile = __DIR__ . '/../logs/auth_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}
?>