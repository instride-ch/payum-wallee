<?php

namespace WVision\Payum\Wallee\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\Base;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use WVision\Payum\Wallee\ApiWrapper;

/**
 * @property ApiWrapper $api
 */
class NotifyNullAction implements ActionInterface, GatewayAwareInterface, ApiAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = ApiWrapper::class;
    }

    /**
     * {@inheritDoc}
     *
     * @param $request Notify
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $this->gateway->execute($httpRequest = new GetHttpRequest());
        $token = null;

        try {
            $parsedBody = \json_decode($httpRequest->content, true);

            if ($parsedBody === false) {
                return;
            }

            //1472041829003 === Transaction
            if ($parsedBody['listenerEntityId'] !== 1472041829003) {
                return;
            }

            $transaction = $this->api->getApi()->read($this->api->getSpaceId(), $parsedBody['entityId']);

            if (!$transaction) {
                return;
            }

            $token = $transaction->getMetaData()['token'];

        } catch (\Exception $e) {
            throw new HttpResponse($e->getMessage(), 500, ['Content-Type' => 'text/plain']);
        }

        if (null === $token) {
            throw new HttpResponse('Invalid Request', 400, ['Content-Type' => 'text/plain']);
        }

        try {
            $this->gateway->execute($getToken = new GetToken($token));
            $this->gateway->execute(new Notify($getToken->getToken()));
        } catch (Base $e) {
            throw $e;
        } catch (LogicException $e) {
            throw new HttpResponse($e->getMessage(), 400, ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            null === $request->getModel();
    }
}
