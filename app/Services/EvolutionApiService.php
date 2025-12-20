<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private $baseUrl;
    private $apiKey;
    private $client;

    public function __construct()
    {
        $this->baseUrl = env('EVOLUTION_API_URL');
        $this->apiKey  = env('EVOLUTION_API_KEY');
        $this->client = new HttpClient([
            'timeout' => 10,
        ]);
        
        Log::info('EvolutionApiService - Construtor', [
            'EVOLUTION_API_URL (env)' => env('EVOLUTION_API_URL') ?? 'NULL',
            'EVOLUTION_API_KEY (env)' => env('EVOLUTION_API_KEY') ? substr(env('EVOLUTION_API_KEY'), 0, 10) . '...' : 'NULL',
            'this->baseUrl' => $this->baseUrl ?? 'NULL',
            'this->apiKey' => empty($this->apiKey) ? 'VAZIA' : substr($this->apiKey, 0, 10) . '...',
        ]);
    }

    private function getHeaders()
    {
        return [
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function createInstance(string $instanceName, ?string $number = null): array
    {
        try {
            $url = "{$this->baseUrl}/instance/create";
            
            $payload = [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS',
                'qrcode' => true,
            ];

            if ($number) {
                $payload['number'] = $number;
            }

            $response = $this->client->request('POST', $url, [
                'headers' => $this->getHeaders(),
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                Log::warning('Evolution API - erro ao criar instância', [
                    'instance_name' => $instanceName,
                    'status' => $statusCode,
                    'response' => $body,
                    'base_url' => $this->baseUrl,
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha ao criar instância',
                    'status' => $statusCode,
                    'error' => $body,
                ];
            }

            return [
                'success' => true,
                'data' => $body,
            ];
        } catch (GuzzleException $e) {
            Log::error('Evolution API - exceção ao criar instância', [
                'instance_name' => $instanceName,
                'base_url' => $this->baseUrl,
                'has_api_key' => !empty($this->apiKey),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API - exceção ao criar instância', [
                'instance_name' => $instanceName,
                'base_url' => $this->baseUrl,
                'has_api_key' => !empty($this->apiKey),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function fetchInstances(): array
    {
        try {
            $url = "{$this->baseUrl}/instance/fetchInstances";
            
            $response = $this->client->request('GET', $url, [
                'headers' => $this->getHeaders(),
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('EvolutionApiService - fetchInstances - Resposta HTTP', [
                'status' => $statusCode,
                'body' => $body,
            ]);

            if ($statusCode >= 400) {
                Log::warning('Evolution API - erro ao buscar instâncias', [
                    'status' => $statusCode,
                    'response' => $body,
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha ao buscar instâncias',
                    'status' => $statusCode,
                    'error' => $body,
                ];
            }

            return [
                'success' => true,
                'data' => $body,
            ];
        } catch (GuzzleException $e) {
            Log::error('Evolution API - exceção ao buscar instâncias (GuzzleException)', [
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API - exceção ao buscar instâncias', [
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendText(string $instanceId, string $number, string $text): array
    {
        try {
            $url = "{$this->baseUrl}/message/sendText/{$instanceId}";
            
            $response = $this->client->request('POST', $url, [
                'headers' => $this->getHeaders(),
                'json' => [
                    'number' => $number,
                    'text'   => $text,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 400) {
                Log::warning('Evolution API - erro ao enviar texto', [
                    'instance_id' => $instanceId,
                    'status'      => $statusCode,
                    'response'    => $body,
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha ao enviar mensagem',
                ];
            }

            return [
                'success' => true,
                'data'    => $body,
            ];
        } catch (GuzzleException $e) {
            Log::error('Evolution API - exceção ao enviar texto', [
                'instance_id' => $instanceId,
                'exception'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API - exceção ao enviar texto', [
                'instance_id' => $instanceId,
                'exception'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
            ];
        }
    }

    public function deleteInstance(string $instanceName): array
    {
        try {
            $url = "{$this->baseUrl}/instance/delete/{$instanceName}";
            
            $response = $this->client->request('DELETE', $url, [
                'headers' => $this->getHeaders(),
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            Log::info('EvolutionApiService - deleteInstance - Resposta HTTP', [
                'instance_name' => $instanceName,
                'status' => $statusCode,
                'body' => $body,
            ]);

            if ($statusCode >= 400) {
                Log::warning('Evolution API - erro ao deletar instância', [
                    'instance_name' => $instanceName,
                    'status' => $statusCode,
                    'response' => $body,
                ]);

                return [
                    'success' => false,
                    'message' => 'Falha ao deletar instância',
                    'status' => $statusCode,
                    'error' => $body,
                ];
            }

            return [
                'success' => true,
                'data' => $body,
            ];
        } catch (GuzzleException $e) {
            Log::error('Evolution API - exceção ao deletar instância (GuzzleException)', [
                'instance_name' => $instanceName,
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            Log::error('Evolution API - exceção ao deletar instância', [
                'instance_name' => $instanceName,
                'mensagem' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao comunicar com serviço externo',
                'error' => $e->getMessage(),
            ];
        }
    }
}