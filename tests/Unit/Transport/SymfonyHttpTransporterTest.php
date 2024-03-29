<?php

declare(strict_types=1);

/*
 * This file is part of the Modelflow AI package.
 *
 * (c) Johannes Wachter <johannes@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ModelflowAi\ApiClient\Tests\Unit\Transport;

use ModelflowAi\ApiClient\Exceptions\TransportException;
use ModelflowAi\ApiClient\Tests\DataFixtures;
use ModelflowAi\ApiClient\Transport\Payload;
use ModelflowAi\ApiClient\Transport\Response\ObjectResponse;
use ModelflowAi\ApiClient\Transport\Response\TextResponse;
use ModelflowAi\ApiClient\Transport\SymfonyHttpTransporter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SymfonyHttpTransporterTest extends TestCase
{
    public function testRequestText(): void
    {
        $transporter = $this->createInstance(new MockResponse('Test text', ['http_code' => 200]));

        $payload = Payload::create('chat/completions', DataFixtures::CHAT_CREATE_REQUEST);

        $response = $transporter->requestText($payload);

        $this->assertInstanceOf(TextResponse::class, $response);
        $this->assertSame('Test text', $response->text);
    }

    public function testRequestText400(): void
    {
        $this->expectException(TransportException::class);

        $transporter = $this->createInstance(new MockResponse('Failure reason ...', ['http_code' => 400]));

        $payload = Payload::create('chat/completions', DataFixtures::CHAT_CREATE_REQUEST);

        $transporter->requestText($payload);
    }

    public function testRequestObject(): void
    {
        $transporter = $this->createInstance(new JsonMockResponse(DataFixtures::CHAT_CREATE_RESPONSE, ['http_code' => 200]));

        $payload = Payload::create('chat/completions', DataFixtures::CHAT_CREATE_REQUEST);

        $response = $transporter->requestObject($payload);

        $this->assertInstanceOf(ObjectResponse::class, $response);
        $this->assertSame(DataFixtures::CHAT_CREATE_RESPONSE, $response->data);
    }

    public function testRequestObject400(): void
    {
        $this->expectException(TransportException::class);

        $transporter = $this->createInstance(new JsonMockResponse([
            'reason' => 'Failure reason ...',
        ], ['http_code' => 400]));

        $payload = Payload::create('chat/completions', DataFixtures::CHAT_CREATE_REQUEST);

        $transporter->requestObject($payload);
    }

    public function testRequestStream(): void
    {
        $transporter = $this->createInstance(new MockResponse(
            (string) \json_encode(DataFixtures::CHAT_CREATE_RESPONSE),
            ['http_code' => 200],
        ));

        $payload = Payload::create('chat/completions', DataFixtures::CHAT_CREATE_REQUEST);

        $responses = \iterator_to_array($transporter->requestStream($payload));

        $this->assertCount(1, $responses);
        $this->assertInstanceOf(ObjectResponse::class, $responses[0]);
        $this->assertSame(DataFixtures::CHAT_CREATE_RESPONSE, $responses[0]->data);
    }

    private function createInstance(ResponseInterface $response): SymfonyHttpTransporter
    {
        return new SymfonyHttpTransporter(new MockHttpClient($response), 'https://api.example.com');
    }
}
