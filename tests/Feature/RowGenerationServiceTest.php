<?php

use App\Ai\Agents\RowGeneratorAgent;
use App\Services\RowGenerationService;

function generatePairsFromAgentReply(string $agentReply): array
{
    RowGeneratorAgent::fake([$agentReply]);

    return app(RowGenerationService::class)->generateRowPairs('Greetings', 2, '', false, false, [], 0);
}

test('parses pairs wrapped in a markdown code fence', function (): void {
    $result = generatePairsFromAgentReply("```json\n[{\"source\": \"Bonjour\", \"russian\": \"Здр<b>а</b>вствуйте\"}]\n```");

    expect($result['pairs'])->toBe([['source' => 'Bonjour', 'russian' => 'Здр<b>а</b>вствуйте']]);
});

test('extracts the pairs array when the reply contains bracketed prose before it', function (): void {
    $result = generatePairsFromAgentReply('Here are [2] pairs: [{"source": "Oui", "russian": "Да"}, {"source": "Non", "russian": "Нет"}]');

    expect($result['pairs'])->toHaveCount(2)
        ->and($result['pairs'][1])->toBe(['source' => 'Non', 'russian' => 'Нет']);
});

test('trims values and drops items without usable source and russian text', function (): void {
    $result = generatePairsFromAgentReply(json_encode([
        ['source' => '  Merci  ', 'russian' => ' Спас<b>и</b>бо '],
        ['source' => 'Sans russe'],
        ['source' => '', 'russian' => 'Пусто'],
        'not an object',
    ]));

    expect($result['pairs'])->toBe([['source' => 'Merci', 'russian' => 'Спас<b>и</b>бо']]);
});

test('throws when the reply is not valid JSON', function (): void {
    expect(fn () => generatePairsFromAgentReply('Sorry, I cannot help with that.'))
        ->toThrow(RuntimeException::class, 'The AI returned an invalid response');
});

test('throws when the reply contains no usable pair', function (string $agentReply): void {
    expect(fn () => generatePairsFromAgentReply($agentReply))
        ->toThrow(RuntimeException::class, 'The AI returned no usable flashcard pairs');
})->with([
    'empty array' => '[]',
    'objects missing fields' => '[{"french": "Bonjour", "translation": "Привет"}]',
    'array of strings' => '["Bonjour", "Привет"]',
]);
