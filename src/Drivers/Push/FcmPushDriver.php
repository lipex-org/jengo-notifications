<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\PushMessage;

class FcmPushDriver implements PushDriverInterface
{
    public function __construct(
        protected string $projectId = '',
        protected string $serviceAccountJson = '',
        protected ?string $accessToken = null
    ) {
        if (empty($this->projectId) && function_exists('config')) {
            $config = config('Notifications');
            $fcmConfig = $config->push['fcm'] ?? [];
            $this->projectId          = $fcmConfig['projectId'] ?? '';
            $this->serviceAccountJson = $fcmConfig['serviceAccountJson'] ?? '';
            $this->accessToken        = $fcmConfig['accessToken'] ?? null;
        }
    }

    public function send(string|array $target, PushMessage $message): bool|array
    {
        $tokens = is_array($target) ? $target : [$target];

        if (empty($tokens)) {
            return false;
        }

        if (empty($this->projectId)) {
            throw CouldNotSendNotificationException::serviceRespondedWithError(
                'fcm',
                'FCM Project ID is not configured.'
            );
        }

        $results = [];
        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $tokenAuth = $this->getAccessToken();

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token'        => $token,
                    'notification' => [
                        'title' => $message->title,
                        'body'  => $message->body,
                        'image' => $message->image,
                    ],
                    'data'         => array_map('strval', $message->data),
                ],
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$tokenAuth}",
                    'Content-Type: application/json',
                ],
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => 15,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw CouldNotSendNotificationException::serviceRespondedWithError('fcm', $error);
            }

            $results[$token] = ($httpCode >= 200 && $httpCode < 300);
        }

        return count($tokens) === 1 ? reset($results) : $results;
    }

    protected function getAccessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        if (file_exists($this->serviceAccountJson)) {
            $sa = json_decode((string) file_get_contents($this->serviceAccountJson), true);
            if (isset($sa['private_key'], $sa['client_email'])) {
                // Generate JWT and exchange for OAuth2 access token
                return $this->generateOAuthToken($sa);
            }
        }

        return '';
    }

    protected function generateOAuthToken(array $sa): string
    {
        // Simple JWT grant exchange for googleapis.com/auth/firebase.messaging
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claim = [
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ];

        $encodedHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $encodedClaim  = rtrim(strtr(base64_encode(json_encode($claim)), '+/', '-_'), '=');
        $signingInput  = $encodedHeader . '.' . $encodedClaim;

        $signature = '';
        if (openssl_sign($signingInput, $signature, $sa['private_key'], 'sha256')) {
            $jwt = $signingInput . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $ch = curl_init('https://oauth2.googleapis.com/token');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]),
                CURLOPT_TIMEOUT        => 10,
            ]);
            $res = curl_exec($ch);
            curl_close($ch);

            $data = json_decode((string) $res, true);
            if (!empty($data['access_token'])) {
                return $this->accessToken = $data['access_token'];
            }
        }

        return '';
    }
}
