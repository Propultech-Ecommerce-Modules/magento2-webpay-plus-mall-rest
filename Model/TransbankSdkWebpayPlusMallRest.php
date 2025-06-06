<?php

namespace Propultech\WebpayPlusMallRest\Model;

use Magento\Framework\Exception\LocalizedException;
use Transbank\Webpay\Exceptions\MissingArgumentException;
use Transbank\Webpay\Exceptions\TransbankCreateException;
use Transbank\Webpay\Helper\PluginLogger;
use Transbank\Webpay\WebpayPlus\MallTransaction;
use Transbank\Webpay\WebpayPlus\Exceptions\MallTransactionCreateException;
use Transbank\Webpay\WebpayPlus\Exceptions\MallTransactionCommitException;
use Transbank\Webpay\WebpayPlus\Exceptions\MallTransactionRefundException;
use Transbank\Webpay\WebpayPlus\Responses\MallTransactionCommitResponse;
use Transbank\Webpay\WebpayPlus\Responses\MallTransactionRefundResponse;

/**
 * Class TransbankSdkWebpayPlusMallRest.
 */
class TransbankSdkWebpayPlusMallRest
{
    /**
     * @var PluginLogger
     */
    private PluginLogger $log;

    /**
     * @var MallTransaction
     */
    private MallTransaction $mallTransaction;

    /**
     * TransbankSdkWebpayPlusMallRest constructor.
     *
     * @param PluginLogger $logger
     * @param array $config
     */
    public function __construct(
        PluginLogger $logger,
        array $config
    ) {
        $this->log = $logger;
        $this->mallTransaction = new MallTransaction();

        $environment = $config['ENVIRONMENT'] ?? 'TEST';
        $this->log->logInfo('Environment: ' . json_encode($environment));

        if ($environment !== 'TEST') {
            if (!isset($config['COMMERCE_CODE']) || !isset($config['API_KEY'])) {
                throw new LocalizedException(__('Missing Transbank configuration parameters'));
            }
            $this->mallTransaction->configureForProduction($config['COMMERCE_CODE'], $config['API_KEY']);
        }
    }

    /**
     * Create a Webpay Plus Mall transaction
     *
     * @param string $buyOrder
     * @param string $sessionId
     * @param string $returnUrl
     * @param array $details
     *
     * @throws TransbankCreateException
     *
     * @return array
     */
    public function createTransaction(string $buyOrder, string $sessionId, string $returnUrl, array $details): array
    {
        $result = [];

        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('createTransaction - buyOrder: ' . $buyOrder . ', sessionId: ' . $sessionId .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime . ', details: ' . json_encode($details));

            if (empty($details)) {
                throw new TransbankCreateException('Transaction details cannot be empty');
            }

            $createResult = $this->mallTransaction->create($buyOrder, $sessionId, $returnUrl, $details);

            $this->log->logInfo('createTransaction - createResult: ' . json_encode($createResult));
            if (isset($createResult) && isset($createResult->url) && isset($createResult->token)) {
                $result = [
                    'url'      => $createResult->url,
                    'token_ws' => $createResult->token,
                ];
            } else {
                throw new TransbankCreateException('No se ha creado la transacción para, buyOrder: ' . $buyOrder . ', sessionId: ' . $sessionId);
            }
        } catch (MallTransactionCreateException $e) {
            $this->log->logError('MallTransactionCreateException: ' . $e->getMessage());
            $result = [
                'error'  => 'Error al crear la transacción',
                'detail' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->log->logError('Exception creating transaction: ' . $e->getMessage());
            $result = [
                'error'  => 'Error inesperado al crear la transacción',
                'detail' => $e->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Commit a Webpay Plus Mall transaction
     *
     * @param string $tokenWs
     *
     * @throws MissingArgumentException
     *
     * @return array|MallTransactionCommitResponse
     */
    public function commitTransaction(string $tokenWs)
    {
        try {
            if (empty($tokenWs)) {
                throw new MissingArgumentException('El token webpay es requerido');
            }

            $transaction = $this->mallTransaction->commit($tokenWs);

            $this->log->logInfo('commitTransaction: ' . json_encode($transaction));
            return $transaction;
        } catch (MallTransactionCommitException $e) {
            $this->log->logError('MallTransactionCommitException: ' . $e->getMessage());
            return [
                'error'  => 'Error al confirmar la transacción',
                'detail' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->log->logError('Exception committing transaction: ' . $e->getMessage());
            return [
                'error'  => 'Error inesperado al confirmar la transacción',
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a Webpay Plus Mall transaction
     *
     * @param string $token
     * @param string $buyOrder
     * @param string $childCommerceCode
     * @param string $childBuyOrder
     * @param int $amount
     *
     * @return MallTransactionRefundResponse|array
     */
    public function refundTransaction(
        string $token,
        string $buyOrder,
        string $childCommerceCode,
        string $childBuyOrder,
        int $amount
    ) {
        try {
            if (empty($token) || empty($buyOrder) || empty($childCommerceCode) || empty($childBuyOrder) || $amount <= 0) {
                throw new MissingArgumentException('Missing required parameters for refund');
            }

            $refundResponse = $this->mallTransaction->refund($token, $buyOrder, $childCommerceCode, $childBuyOrder, $amount);
            $this->log->logInfo('refundTransaction: ' . json_encode($refundResponse));

            return $refundResponse;
        } catch (MallTransactionRefundException $e) {
            $this->log->logError('MallTransactionRefundException: ' . $e->getMessage());
            return [
                'error'  => 'Error al reembolsar la transacción',
                'detail' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $this->log->logError('Exception refunding transaction: ' . $e->getMessage());
            return [
                'error'  => 'Error inesperado al reembolsar la transacción',
                'detail' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the mall transaction instance
     *
     * @return MallTransaction
     */
    public function getMallTransaction(): MallTransaction
    {
        return $this->mallTransaction;
    }
}
