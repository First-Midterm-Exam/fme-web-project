<?php

namespace App\Services\Ia;

use App\Exceptions\LimiteIaExcedido;
use App\Exceptions\ServicioIaNoDisponible;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ClienteGroq
{
    public function __construct(
        private readonly string $clave,
        private readonly string $url,
        private readonly string $modelo,
        private readonly int $timeout,
    ) {}

    public function configurado(): bool
    {
        return $this->clave !== '';
    }

    /**
     * @param  list<array{role: string, content: string}>  $mensajes
     * @return array<string, mixed>
     */
    public function json(array $mensajes): array
    {
        if (! $this->configurado()) {
            throw new ServicioIaNoDisponible;
        }

        try {
            $respuesta = Http::withToken($this->clave)
                ->acceptJson()
                ->timeout($this->timeout)
                ->post(rtrim($this->url, '/').'/chat/completions', [
                    'model' => $this->modelo,
                    'messages' => $mensajes,
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException) {
            throw new ServicioIaNoDisponible;
        }

        if ($respuesta->status() === 429) {
            throw new LimiteIaExcedido(max(1, (int) ceil((float) ($respuesta->header('Retry-After') ?: 30))));
        }

        if ($respuesta->failed()) {
            throw new ServicioIaNoDisponible;
        }

        $datos = json_decode((string) $respuesta->json('choices.0.message.content', ''), true);

        return is_array($datos) ? $datos : [];
    }
}
