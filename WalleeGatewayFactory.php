<?php

namespace WVision\Payum\Wallee;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Wallee\Sdk\ApiClient;
use Wallee\Sdk\Service\TransactionCompletionService;
use Wallee\Sdk\Service\TransactionService;
use WVision\Payum\Wallee\Action\CaptureOffSiteAction;
use WVision\Payum\Wallee\Action\ConvertPaymentAction;
use WVision\Payum\Wallee\Action\NotifyAction;
use WVision\Payum\Wallee\Action\NotifyNullAction;
use WVision\Payum\Wallee\Action\StatusAction;

class WalleeGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name'           => 'wallee',
            'payum.factory_title'          => 'Wallee E-Commerce',
            'payum.action.capture'         => new CaptureOffSiteAction(),
            'payum.action.status'          => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.notify_null'     => new NotifyNullAction(),
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = [
                'space_id'         => '',
                'user_id'          => '',
                'secret'           => ''
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = ['space_id', 'user_id', 'secret'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                $client = new ApiClient($config['user_id'], $config['secret']);

                return new ApiWrapper(
                    new TransactionService($client),
                    new TransactionCompletionService($client),
                    $config['space_id']
                );
            };
        }
    }
}
