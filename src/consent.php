<?php

require_once dirname(__DIR__, 4) . '/consent/lib.php';

final class FunConsent
{
    public static function resolve(array $session): array
    {
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
}
