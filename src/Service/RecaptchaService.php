<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class RecaptchaService
{
    private $httpClient;
    private $secretKey;
    private $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $recaptchaSecretKey
    ) {
        $this->httpClient = $httpClient;
        $this->secretKey = $recaptchaSecretKey;
        $this->logger = $logger;
    }

    /**
     * Vérifie le token reCAPTCHA v2
     */
    public function verify(?string $token, ?string $remoteIp = null): array
    {
        if (empty($token)) {
            $this->logger->warning('reCAPTCHA v2: Token manquant');
            return [
                'success' => false,
                'message' => 'Veuillez cocher la case "Je ne suis pas un robot"'
            ];
        }

        try {
            $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp
                ]
            ]);

            $result = $response->toArray();

            $this->logger->info('reCAPTCHA v2 Response', [
                'success' => $result['success'] ?? false,
                'challenge_ts' => $result['challenge_ts'] ?? '',
                'hostname' => $result['hostname'] ?? ''
            ]);

            if (!isset($result['success']) || $result['success'] !== true) {
                $errorCodes = $result['error-codes'] ?? ['unknown-error'];
                
                $this->logger->warning('reCAPTCHA v2: Échec de vérification', [
                    'error_codes' => $errorCodes
                ]);

                return [
                    'success' => false,
                    'message' => 'Vérification reCAPTCHA échouée. Veuillez réessayer.'
                ];
            }

            // Succès !
            return [
                'success' => true,
                'message' => 'Vérification reCAPTCHA réussie'
            ];

        } catch (\Exception $e) {
            $this->logger->error('reCAPTCHA v2: Erreur lors de la vérification', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification reCAPTCHA'
            ];
        }
    }
}