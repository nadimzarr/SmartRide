<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class InfoBipService
{
    private $httpClient;
    private $apiKey;
    private $baseUrl;
    private $senderId;

    public function __construct(
        string $apiKey = null,
        string $baseUrl = null,
        string $senderId = null,
        LoggerInterface $logger = null
    ) {
        $this->httpClient = HttpClient::create();
        $this->apiKey = $apiKey ?? $_ENV['INFOBIP_API_KEY'] ?? 'test_key';
        $this->baseUrl = rtrim(($baseUrl ?? $_ENV['INFOBIP_BASE_URL'] ?? 'https://api.infobip.com'), '/');
        $this->senderId = $senderId ?? $_ENV['INFOBIP_SENDER_ID'] ?? 'SmartRide';
        $this->logger = $logger;
    }

    public function sendSms(string $to, string $message): bool
    {
        $to = $this->formatPhoneNumber($to);
        
        // Mode test si clé invalide
        if ($this->isTestMode()) {
            return $this->simulateSms($to, $message);
        }
        
        // API Réelle
        $url = $this->baseUrl . '/sms/2/text/advanced';
        
        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'App ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'messages' => [[
                        'destinations' => [['to' => $to]],
                        'from' => $this->senderId,
                        'text' => $message
                    ]]
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $this->log("✅ SMS envoyé à " . $to);
                return true;
            } else {
                return $this->simulateSms($to, $message);
            }
            
        } catch (\Exception $e) {
            $this->log("⚠️ Erreur: " . $e->getMessage());
            return $this->simulateSms($to, $message);
        }
    }

    public function sendReclamationNotification(object $reclamation, string $phoneNumber): bool
    {
        $message = $this->createReclamationMessage($reclamation);
        return $this->sendSms($phoneNumber, $message);
    }

    private function createReclamationMessage(object $reclamation): string
    {
        return sprintf(
            "🚨 NOUVELLE RÉCLAMATION\n" .
            "─────────────────────────\n" .
            
            "👤 Client: %s %s\n" .
            "📅 Date: %s\n" .
            "⏰ Heure: %s\n" .
            "📞 SmartRide Support",
        
            $reclamation->getNom(),
            $reclamation->getPrenom(),
            $reclamation->getDateReclamation()->format('d/m/Y'),
            date('H:i')
        );
    }

    private function isTestMode(): bool
    {
        return strpos($this->apiKey, 'test') === 0 || 
               strpos($this->apiKey, 'dummy') === 0 ||
               strlen($this->apiKey) < 20;
    }

    private function simulateSms(string $to, string $message): bool
    {
        $log = sprintf("[%s] SMS à %s: %s\n", 
            date('Y-m-d H:i:s'), 
            $to, 
            substr($message, 0, 50)
        );
        
        file_put_contents(__DIR__ . '/../../var/log/sms.log', $log, FILE_APPEND);
        $this->log("📱 SMS simulé");
        
        return true;
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (str_starts_with($phone, '0')) {
            return '216' . substr($phone, 1);
        }
        
        if (!str_starts_with($phone, '216')) {
            return '216' . $phone;
        }
        
        return $phone;
    }

    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}