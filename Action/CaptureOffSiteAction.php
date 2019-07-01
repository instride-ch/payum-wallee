<?php

namespace WVision\Payum\Wallee\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Wallee\Sdk\Model\LineItemCreate;
use Wallee\Sdk\Model\LineItemType;
use Wallee\Sdk\Model\TransactionCreate;
use WVision\Payum\Wallee\ApiWrapper;
use WVision\Payum\Wallee\Request\GetHumanStatus;
use WVision\Payum\Wallee\Request\PrepareTransaction;

/**
 * @property ApiWrapper $api
 */
class CaptureOffSiteAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    public function __construct()
    {
        $this->apiClass = ApiWrapper::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        //we are back from wallee site so we have to just update model.
        if (isset($model['pfc_transaction_id'])) {
            //$transaction = $this->api->getApi()->read($this->api->getSpaceId(), $model['pfc_transaction_id']);


            return;
        }

        $targetUrl = $request->getToken()->getTargetUrl();

        $lineItem = new LineItemCreate();
        $lineItem->setName($model['description']);
        $lineItem->setUniqueId($model['order_id']);
        $lineItem->setQuantity(1);
        $lineItem->setAmountIncludingTax(round($model['amount'] / 100, 2));
        $lineItem->setType(LineItemType::PRODUCT);

        $transaction = new TransactionCreate();
        $transaction->setCurrency($model['currency_code']);
        $transaction->setSuccessUrl($targetUrl . '?action=success');
        $transaction->setFailedUrl($targetUrl . '?action=cancelled');
        $transaction->setAutoConfirmationEnabled(true);
        $transaction->setMetaData([
            'token' => $request->getToken()->getHash()
        ]);

        $this->gateway->execute(new PrepareTransaction($request->getFirstModel(), $transaction));

        $createdTransaction = $this->api->getApi()->create($this->api->getSpaceId(), $transaction);
        $this->api->getApi()->confirm($this->api->getSpaceId(), $createdTransaction);

        $model['pfc_transaction_id'] = $createdTransaction->getId();

        $status = $this->waitForTransactionState($model, [GetHumanStatus::STATUS_FAILED, GetHumanStatus::STATUS_CONFIRMED]);

        if (false === $status) {
            return;
        }

        if ($status === GetHumanStatus::STATUS_FAILED) {
            return;
        }

        throw new HttpRedirect(
            $this->api->getApi()->buildPaymentPageUrl($this->api->getSpaceId(), $createdTransaction->getId())
        );
    }

    protected function waitForTransactionState($model, array $states, int $maxWaitTime = 10)
    {
        $startTime = microtime(true);
        while (true) {
            $this->gateway->execute($status = new GetHumanStatus($model));

            if (in_array($status->getValue(), $states, true)) {
                return $status->getValue();
            }

            if (microtime(true) - $startTime >= $maxWaitTime) {
                return false;
            }
            sleep(2);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
