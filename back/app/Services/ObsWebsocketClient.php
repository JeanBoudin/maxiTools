<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;

class ObsWebsocketClient
{
    private string $host;
    private int $port;
    private ?string $password;
    private bool $useTls;
    private int $timeout;
    private $socket;

    public function __construct(string $host, int $port, ?string $password = null, bool $useTls = false, int $timeout = 3)
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->useTls = $useTls;
        $this->timeout = $timeout;
    }

    public function connect(): void
    {
        $scheme = $this->useTls ? 'tls' : 'tcp';
        $this->socket = @stream_socket_client(
            sprintf('%s://%s:%d', $scheme, $this->host, $this->port),
            $errno,
            $errstr,
            $this->timeout
        );

        if (!$this->socket) {
            throw new RuntimeException('Impossible de se connecter à OBS WebSocket.');
        }

        stream_set_timeout($this->socket, $this->timeout);

        $key = base64_encode(random_bytes(16));
        $headers = [
            "GET / HTTP/1.1",
            "Host: {$this->host}:{$this->port}",
            "Upgrade: websocket",
            "Connection: Upgrade",
            "Sec-WebSocket-Key: {$key}",
            "Sec-WebSocket-Version: 13",
            "\r\n",
        ];
        fwrite($this->socket, implode("\r\n", $headers));

        $response = $this->readHttpResponse();
        if (stripos($response, ' 101 ') === false) {
            throw new RuntimeException('Handshake WebSocket refusé par OBS.');
        }

        $accept = $this->extractHeader($response, 'Sec-WebSocket-Accept');
        $expected = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        if ($accept !== $expected) {
            throw new RuntimeException('Handshake WebSocket invalide.');
        }

        $hello = $this->readJsonFrame();
        if (($hello['op'] ?? null) !== 0) {
            throw new RuntimeException('Réponse OBS inattendue.');
        }

        $auth = null;
        $authData = $hello['d']['authentication'] ?? null;
        if ($authData && $this->password) {
            $secret = base64_encode(hash('sha256', $this->password . $authData['salt'], true));
            $auth = base64_encode(hash('sha256', $secret . $authData['challenge'], true));
        }

        $identify = [
            'op' => 1,
            'd' => [
                'rpcVersion' => 1,
            ],
        ];
        if ($auth) {
            $identify['d']['authentication'] = $auth;
        }

        $this->sendJsonFrame($identify);

        $identified = $this->readJsonFrame();
        if (($identified['op'] ?? null) !== 2) {
            throw new RuntimeException('Connexion OBS non autorisée.');
        }
    }

    public function getSceneList(): array
    {
        $response = $this->request('GetSceneList');

        return $response['responseData']['scenes'] ?? [];
    }

    public function getCurrentProgramScene(): ?string
    {
        $response = $this->request('GetCurrentProgramScene');

        return $response['responseData']['currentProgramSceneName'] ?? null;
    }

    public function setCurrentScene(string $sceneName): void
    {
        $this->request('SetCurrentProgramScene', [
            'sceneName' => $sceneName,
        ]);
    }

    public function close(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    private function request(string $type, array $data = []): array
    {
        $requestId = Str::uuid()->toString();
        $payload = [
            'op' => 6,
            'd' => [
                'requestType' => $type,
                'requestId' => $requestId,
            ],
        ];
        if (!empty($data)) {
            $payload['d']['requestData'] = $data;
        }

        $this->sendJsonFrame($payload);

        while (true) {
            $message = $this->readJsonFrame();
            if (($message['op'] ?? null) !== 7) {
                continue;
            }

            if (($message['d']['requestId'] ?? '') !== $requestId) {
                continue;
            }

            $status = $message['d']['requestStatus'] ?? [];
            if (!($status['result'] ?? false)) {
                throw new RuntimeException($status['comment'] ?? 'Erreur OBS.');
            }

            return $message['d'];
        }
    }

    private function readJsonFrame(): array
    {
        $payload = $this->readFrame();
        if ($payload === '') {
            throw new RuntimeException('Connexion OBS interrompue.');
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            throw new RuntimeException('Réponse OBS invalide.');
        }

        return $data;
    }

    private function sendJsonFrame(array $data): void
    {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('Erreur encodage JSON.');
        }

        $this->sendFrame($payload);
    }

    private function readHttpResponse(): string
    {
        $response = '';
        while (!str_contains($response, "\r\n\r\n")) {
            $chunk = fread($this->socket, 1024);
            if ($chunk === '' || $chunk === false) {
                break;
            }
            $response .= $chunk;
        }

        return $response;
    }

    private function extractHeader(string $response, string $header): ?string
    {
        foreach (preg_split('/\r\n/', $response) as $line) {
            if (stripos($line, $header . ':') === 0) {
                return trim(substr($line, strlen($header) + 1));
            }
        }

        return null;
    }

    private function readFrame(): string
    {
        $header = $this->readBytes(2);
        if ($header === '') {
            return '';
        }

        $byte1 = ord($header[0]);
        $byte2 = ord($header[1]);

        $opcode = $byte1 & 0x0f;
        $masked = ($byte2 & 0x80) === 0x80;
        $length = $byte2 & 0x7f;

        if ($length === 126) {
            $lengthData = $this->readBytes(2);
            $length = unpack('n', $lengthData)[1];
        } elseif ($length === 127) {
            $lengthData = $this->readBytes(8);
            $lengthParts = unpack('J', $lengthData);
            $length = $lengthParts[1];
        }

        $mask = $masked ? $this->readBytes(4) : '';
        $payload = $length > 0 ? $this->readBytes($length) : '';

        if ($masked) {
            $payload = $this->applyMask($payload, $mask);
        }

        if ($opcode === 0x9) {
            $this->sendFrame($payload, 0xA);
            return $this->readFrame();
        }

        if ($opcode === 0x8) {
            return '';
        }

        return $payload;
    }

    private function sendFrame(string $payload, int $opcode = 0x1): void
    {
        $length = strlen($payload);
        $frame = chr(0x80 | $opcode);

        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0x80 | 126) . pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127) . pack('J', $length);
        }

        $mask = random_bytes(4);
        $frame .= $mask;
        $frame .= $this->applyMask($payload, $mask);

        fwrite($this->socket, $frame);
    }

    private function applyMask(string $payload, string $mask): string
    {
        $masked = '';
        $length = strlen($payload);
        for ($i = 0; $i < $length; $i += 1) {
            $masked .= $payload[$i] ^ $mask[$i % 4];
        }

        return $masked;
    }

    private function readBytes(int $length): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));
            if ($chunk === '' || $chunk === false) {
                break;
            }
            $data .= $chunk;
        }

        return $data;
    }
}
