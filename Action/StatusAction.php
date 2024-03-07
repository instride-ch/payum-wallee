<?php

namespace Instride\Payum\Wallee\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Wallee\Sdk\Model\TransactionState;
use Instride\Payum\Wallee\ApiWrapper;
use Instride\Payum\Wallee\Request\GetHumanStatus;

/**
 * @property ApiWrapper $api
 */
class StatusAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = ApiWrapper::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param \Payum\Core\Request\GetHumanStatus $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = new ArrayObject($request->getModel());

        if (null === $model['pfc_transaction_id']) {
            $request->markNew();
            return;
        }

        $transactionId = $model['pfc_transaction_id'];

        $transaction = $this->api->getApi()->read($this->api->getSpaceId(), $transactionId);

        $status = $transaction->getState();

        switch ($status) {
            case TransactionState::CONFIRMED:
                if ($request instanceof GetHumanStatus) {
                    $request->markConfirmed();
                }
                break;
            case TransactionState::FULFILL:
                $request->markCaptured();
                break;
            case TransactionState::PENDING:
                $request->markPending();
                break;
            case TransactionState::AUTHORIZED:
                $request->markAuthorized();
                break;
            case TransactionState::COMPLETED:
                $request->markCaptured();
                break;
            case TransactionState::FAILED:
                $request->markFailed();
                break;
            case TransactionState::DECLINE:
                $request->markFailed();
                break;
            default:
                $request->markUnknown();
                break;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
