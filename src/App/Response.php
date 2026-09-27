<?php

declare(strict_types=1);

namespace App;

final class Response
{
    public function __construct(
        public readonly string $body,
        public readonly int $status = 200,
        /** @var array<string,string> */
        public readonly array $headers = [],
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(array $payload, int $status = 200): self
    {
        return new self(
            (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8']
        );
    }

    public static function text(string $body, string $contentType = 'text/plain; charset=UTF-8', int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => $contentType]);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $this->body;
    }
}
