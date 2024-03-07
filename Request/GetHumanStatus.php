<?php

namespace Instride\Payum\Wallee\Request;

class GetHumanStatus extends \Payum\Core\Request\GetHumanStatus
{
    public const STATUS_CONFIRMED = 'confirmed';

    /**
     * {@inheritDoc}
     */
    public function markConfirmed()
    {
        $this->status = static::STATUS_CONFIRMED;
    }

    /**
     * {@inheritDoc}
     */
    public function isConfirmed()
    {
        return $this->isCurrentStatusEqualTo(static::STATUS_CONFIRMED);
    }
}
