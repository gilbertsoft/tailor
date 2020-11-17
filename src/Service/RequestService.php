<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project  - inspiring people to share!
 * (c) 2020 Oliver Bartsch
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace TYPO3\Tailor\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use TYPO3\Tailor\Dto\RequestConfiguration;
use TYPO3\Tailor\HttpClientFactory;

/**
 * Service for performing HTTP request to the TER REST API
 */
class RequestService
{
    /** @var HttpClient */
    protected $client;

    /** @var RequestConfiguration */
    protected $requestConfiguration;

    /** @var FormatService */
    protected $formatService;

    public function __construct(RequestConfiguration $requestConfiguration, FormatService $formatService)
    {
        $this->client = HttpClientFactory::create($requestConfiguration);
        $this->requestConfiguration = $requestConfiguration;
        $this->formatService = $formatService;
    }

    /**
     * Run the request by the given request configuration and format
     * the result using the FormatService.
     *
     * @return bool
     */
    public function run(): bool
    {
        $this->formatService->writeTitle();

        try {
            $response = $this->client->request($this->requestConfiguration->getMethod(), $this->requestConfiguration->getEndpoint());
            $content = $response->getContent(false);
            $status = $response->getStatusCode();

            if ($this->requestConfiguration->isRaw()) {
                $this->formatService->write($content);
                return true;
            }

            $content = (array)(json_decode($content, true) ?? []);

            if ($this->requestConfiguration->isSuccessful($status)) {
                $this->formatService->writeSuccess();
                $this->formatService->formatResult($content);
            } else {
                $this->formatService->writeFailure(
                    (string)($content['error_description'] ?? $content['message'] ?? 'Unknown (Status ' . $status . ')')
                );
            }

        } catch (HttpExceptionInterface|TransportExceptionInterface $e) {
            $this->formatService->error('An error occurred: ' . $e->getMessage());
            return false;
        }

        return true;
    }
}
