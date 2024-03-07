<?php

namespace Instride\Payum\Wallee\Request;

use Payum\Core\Request\Generic;
use Wallee\Sdk\Model\TransactionCreate;

class PrepareTransaction extends Generic
{
    /**
     * @var TransactionCreate
     */
    protected $transaction;

    /**
     * @param mixed $model
     */
    public function __construct($model, $transaction)
    {
        parent::__construct($model);

        $this->transaction = $transaction;
    }

    /**
     * @return TransactionCreate
     */
    public function getTransaction(): TransactionCreate
    {
        return $this->transaction;
    }
}
