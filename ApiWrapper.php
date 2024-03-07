<?php

namespace Instride\Payum\Wallee;

use Wallee\Sdk\Service\TransactionCompletionService;
use Wallee\Sdk\Service\TransactionService;

class ApiWrapper
{
    /**
     * @var TransactionService
     */
    private $api;

    /**
     * @var TransactionCompletionService
     */
    private $completionApi;

    /**
     * @var string
     */
    private $spaceId;

    /**
     * @param TransactionService $api
     * @param TransactionCompletionService $completionApi
     * @param string             $spaceId
     */
    public function __construct(TransactionService $api, TransactionCompletionService $completionApi, string $spaceId)
    {
        $this->api = $api;
        $this->completionApi = $completionApi;
        $this->spaceId = $spaceId;
    }

    /**
     * @return TransactionService
     */
    public function getApi(): TransactionService
    {
        return $this->api;
    }

    /**
     * @return TransactionService
     */
    public function getCompletionApi(): TransactionCompletionService
    {
        return $this->completionApi;
    }

    /**
     * @return string
     */
    public function getSpaceId(): string
    {
        return $this->spaceId;
    }
}
