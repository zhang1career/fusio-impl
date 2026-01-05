<?php

namespace Fusio\Impl\Service\Event;

use Symfony\Component\Messenger\Stamp\StampInterface;


final class HttpHeaderStamp implements StampInterface
{
    private array $headers;

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }
}
