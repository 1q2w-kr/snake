<?php

// Resolve the shared consent manager from the repo root.
$consentLib = dirname(__DIR__, 3) . '/consent/lib.php';
$hasConsentLib = file_exists($consentLib);
if ($hasConsentLib) {
    require_once $consentLib;
} else {
    error_log('Fun consent lib not found at ' . $consentLib);
}

final class FunConsent
{
    public static function resolve(array $session): array
    {
        if (class_exists('\\OneQ2w\\Consent\\Manager')) {
            $state = \OneQ2w\Consent\Manager::resolve($session);

            return [
                'hasConsent' => $state['hasFunctionalConsent'],
                'needsPrompt' => false,
                'choices' => $state['choices'],
                'acknowledged' => $state['acknowledged'],
                'timestamp' => $state['timestamp'],
                'source' => $state['source'],
            ];
        }

        // Fallback: no consent lib available
        return [
            'hasConsent' => false,
            'needsPrompt' => false,
            'choices' => [],
            'acknowledged' => false,
            'timestamp' => null,
            'source' => 'none',
        ];
    }
}
