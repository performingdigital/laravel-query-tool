<?php

namespace PerformingDigital\QueryTool\Agent;

final class OpenAiToolFactory
{
    /**
     * Responses API / Chat Completions compatible function tool definition.
     *
     * @return array<string, mixed>
     */
    public static function from(QueryToolAgentTool $tool): array
    {
        $schema = $tool->schema();

        return [
            'type' => 'function',
            'function' => [
                'name' => $schema['name'],
                'description' => $schema['description'],
                'parameters' => $schema['parameters'],
            ],
        ];
    }
}
