<?php

declare(strict_types=1);

namespace Openeuropa\GptAtEcPhpClient\Transporters;

use GuzzleHttp\Exception\ClientException;
use OpenAI\Contracts\TransporterContract;
use OpenAI\Enums\Transporter\ContentType;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;
use OpenAI\ValueObjects\Transporter\AdaptableResponse;
use OpenAI\ValueObjects\Transporter\BaseUri;
use OpenAI\ValueObjects\Transporter\Headers;
use OpenAI\ValueObjects\Transporter\Payload;
use OpenAI\ValueObjects\Transporter\QueryParams;
use OpenAI\ValueObjects\Transporter\Response;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

final class HttpTransporter implements TransporterContract
{

    public function __construct(
        private readonly ClientInterface $client,
        private readonly BaseUri $baseUri,
        private Headers $headers,
        private readonly QueryParams $queryParams,
        private readonly \Closure $streamHandler,
    ) {
        // ..
    }

    /**
     * {@inheritDoc}
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers = $this->headers->withCustomHeader($name, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function requestObject(Payload $payload): Response
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);

        $response = $this->sendRequest(fn(): ResponseInterface => $this->client->sendRequest($request));

        $contents = (string)$response->getBody();

        $this->throwIfJsonError($response, $contents);

        return Response::from($this->decode($contents, $response), $response->getHeaders());
    }

    /**
     * {@inheritDoc}
     */
    public function requestStringOrObject(Payload $payload): AdaptableResponse
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);

        $response = $this->sendRequest(fn(): ResponseInterface => $this->client->sendRequest($request));

        $contents = (string)$response->getBody();

        if (str_contains($response->getHeaderLine('Content-Type'), ContentType::TEXT_PLAIN->value)) {
            return AdaptableResponse::from($contents, $response->getHeaders());
        }

        $this->throwIfJsonError($response, $contents);

        return AdaptableResponse::from($this->decode($contents, $response), $response->getHeaders());
    }

    /**
     * {@inheritDoc}
     */
    public function requestContent(Payload $payload): string
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);

        $response = $this->sendRequest(fn(): ResponseInterface => $this->client->sendRequest($request));

        $contents = (string)$response->getBody();

        $this->throwIfJsonError($response, $contents);

        return $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function requestStream(Payload $payload): ResponseInterface
    {
        $request = $payload->toRequest($this->baseUri, $this->headers, $this->queryParams);

        $response = $this->sendRequest(fn() => ($this->streamHandler)($request));

        $this->throwIfJsonError($response, $response);

        return $response;
    }

    private function sendRequest(\Closure $callable): ResponseInterface
    {
        try {
            return $callable();
        }
        catch (ClientExceptionInterface $clientException) {
            if ($clientException instanceof ClientException) {
                $this->throwIfJsonError(
                    $clientException->getResponse(),
                    (string)$clientException->getResponse()->getBody(),
                );
            }

            throw new TransporterException($clientException);
        }
    }

    private function throwIfJsonError(ResponseInterface $response, string|ResponseInterface $contents): void
    {
        if ($response->getStatusCode() < 400) {
            return;
        }

        if (!str_contains($response->getHeaderLine('Content-Type'), ContentType::JSON->value)) {
            return;
        }

        if ($contents instanceof ResponseInterface) {
            $contents = (string) $contents->getBody();
        }

        // GPT@EC response doesn't wrap error information in an "error" array.
        /** @var array{message: string|array<int, string>, code: string, description: string} $body */
        $body = $this->decode($contents, $response);

        $data = [
            'code' => $body['code'] ?? NULL,
        ];

        if (!empty($body['description'])) {
            $data['message'] = $body['description'];
        }
        elseif (!empty($body['message'])) {
            $data['message'] = $body['message'];

            // If we have a valid code, append it to the message.
            if (is_string($data['message']) && isset($data['code'])) {
                $data['message'] = $data['code'] . ': ' . $data['message'];
            }
        }
        elseif (!empty($body['detail'])) {
            // The detail key is a nested array. For simplicity, json_encode its content.
            $data['message'] = json_encode($body['detail']);
        }

        throw new ErrorException($data, $response);
    }

    /**
     * Decodes a JSON response body.
     *
     * @return array<array-key, mixed>
     */
    private function decode(string $contents, ResponseInterface $response): array
    {
        try {
            /** @var array<array-key, mixed> $data */
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $jsonException) {
            throw new UnserializableResponse($jsonException, $response);
        }

        return $data;
    }

}
