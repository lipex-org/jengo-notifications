<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Config\Services;
use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\PushMessage;
use Throwable;

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
        $client = Services::curlrequest([
            'timeout'     => 15.0,
            'http_errors' => false,
        ]);

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

            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Authorization' => "Bearer {$tokenAuth}",
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                $httpCode = $response->getStatusCode();
            } catch (Throwable $e) {
                throw CouldNotSendNotificationException::serviceRespondedWithError('fcm', $e->getMessage());
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

            try {
                $tokenClient = Services::curlrequest([
                    'timeout'     => 10.0,
                    'http_errors' => false,
                ]);

                $res = $tokenClient->post('https://oauth2.googleapis.com/token', [
                    'form_params' => [
                        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                        'assertion'  => $jwt,
                    ],
                ]);

                $data = json_decode((string) $res->getBody(), true);
                if (!empty($data['access_token'])) {
                    return $this->accessToken = $data['access_token'];
                }
            } catch (Throwable) {
                return '';
            }
        }

        return '';
    }
}
