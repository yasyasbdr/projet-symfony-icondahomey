<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Consommation d'une API externe via le composant HttpClient de Symfony.
 * Envoie une notification SMS (ex: nouveau message, prix de personnalisation propose).
 * Les identifiants proviennent de variables d'environnement (.env.local).
 * Si l'API n'est pas configuree, l'envoi est simplement ignore (log), ce qui
 * evite de casser le parcours en environnement de dev.
 */
class SmsSender
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $smsApiUrl = '',
        private readonly string $smsApiToken = '',
    ) {
    }

    public function send(string $phoneNumber, string $message): bool
    {
        if ($this->smsApiUrl === '' || $this->smsApiToken === '') {
            $this->logger->info('SMS non envoye (API non configuree)', [
                'to' => $phoneNumber,
                'message' => $message,
            ]);

            return false;
        }

        try {
            $response = $this->httpClient->request('POST', $this->smsApiUrl, [
                'auth_bearer' => $this->smsApiToken,
                'json' => [
                    'to' => $phoneNumber,
                    'text' => $message,
                ],
                'timeout' => 8,
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Throwable $e) {
            $this->logger->error('Echec envoi SMS', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
