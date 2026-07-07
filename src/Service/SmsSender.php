<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Envoi de SMS via l'API Twilio, consommée avec le composant HttpClient.
 * Utilisé pour notifier le client (devis de personnalisation proposé, nouveau
 * message de l'administration).
 *
 * Les identifiants proviennent de variables d'environnement (.env.local en
 * local, variables Render en production). Si Twilio n'est pas configuré, l'envoi
 * est ignoré (log) : le parcours n'est jamais bloqué.
 *
 * NB (compte d'essai Twilio) : les SMS ne peuvent être envoyés qu'à des numéros
 * préalablement vérifiés dans la console Twilio.
 */
class SmsSender
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $accountSid = '',
        private readonly string $authToken = '',
        private readonly string $fromNumber = '',
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->accountSid !== '' && $this->authToken !== '' && $this->fromNumber !== '';
    }

    public function send(string $phoneNumber, string $message): bool
    {
        if (!$this->isConfigured()) {
            $this->logger->info('SMS non envoyé (Twilio non configuré)', [
                'to' => $phoneNumber,
                'message' => $message,
            ]);

            return false;
        }

        $to = $this->normalize($phoneNumber);
        if ($to === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', $this->accountSid),
                [
                    // Authentification Basic : identifiant = Account SID, mot de passe = Auth Token.
                    'auth_basic' => [$this->accountSid, $this->authToken],
                    // Corps envoyé en x-www-form-urlencoded (format attendu par Twilio).
                    'body' => [
                        'To' => $to,
                        'From' => $this->fromNumber,
                        'Body' => $message,
                    ],
                    'timeout' => 10,
                ]
            );

            $status = $response->getStatusCode();
            if ($status >= 200 && $status < 300) {
                return true;
            }

            $this->logger->error('Échec envoi SMS Twilio', [
                'status' => $status,
                'response' => $response->getContent(false),
            ]);

            return false;
        } catch (\Throwable $e) {
            $this->logger->error('Erreur envoi SMS Twilio', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /** Normalise un numéro français (0X XX...) au format international E.164 (+33...). */
    private function normalize(string $phone): string
    {
        $p = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if (str_starts_with($p, '0') && strlen($p) === 10) {
            return '+33'.substr($p, 1);
        }

        return $p;
    }
}
