<?php

namespace App\Component\CatalogueService\Parameter;

use App\Component\CatalogueService\CswException;
use App\Component\CatalogueService\Operation;
use App\Component\CatalogueService\Transaction;
use App\Component\CatalogueService\TransactionOperation;
use App\Service\Search\ExprHandler;
use DOMElement;
use Exception;

class TransactionParameter extends PostDomParameter
{
    private int$typeIdx = -1;

    public function initOperation(Operation $operation): Operation
    {
        $parameterMap = $operation->getPOSTParameterMap();
        $parameters = [];
        foreach ($parameterMap as $key => $value) {
            if (is_string($value)) {
                $parameters[$value] = $this->getParameter($key);
            }
        }
        $operation->setParameters($parameters);
        $this->reset();

        return $operation;
    }

    public function valueFor($xpath, $contextElm = null, $allowSingle = true): mixed
    {
        return $this->getValue($xpath, $contextElm, $allowSingle);
    }


    public function reset()
    {
        $this->typeIdx = -1;
    }

    private function getTypeConfiguration(Transaction $operation): ?array
    {
        $parameterMap = $operation->getPOSTParameterMap();
        foreach ($parameterMap as $key => $value) {
            if (!is_string($value)) {
                return ['key' => $key, 'value' => $value];
            }
        }
        return null;
    }


    public function nextAction(Transaction $operation, ExprHandler $expression): ?TransactionOperation
    {
        $conf = $this->getTypeConfiguration($operation);
        $xpathStr = $conf['key'];
        $typesConf = $conf['value'];
        $list = $this->getValue($xpathStr, $this->dom->documentElement, false);
        $this->typeIdx++;
        if (!$list || count($list) === 0) {
            throw new CswException($xpathStr, CswException::NOAPPLICABLECODE, );
        }
        if ($list && $this->typeIdx < count($list)) {
            /* @var DOMElement $actionNode */
            $actionNode = $list[$this->typeIdx];
            $config = $typesConf[$actionNode->localName];

            return $this->initAction($operation, $expression, $actionNode, $config);
        } else {
            return null;
        }
    }


    private function initAction(
        Transaction $transaction,
        ExprHandler $exprHandler,
        DOMElement $actionElm,
        array $config
    ) {
        if (!$transaction->isTypeSupported($config[Transaction::ACTION])) {
            throw new CswException($config[Transaction::ACTION], CswException::OPERATIONNOTSUPPORTED);
        }
        $action = new TransactionOperation($config[Transaction::ACTION], $exprHandler);
        foreach ($config[Transaction::PARAMS] as $key => $value) {
            $action->setParameter($value, $this->getParameter($key, $actionElm));
        }
        if (isset($config[Transaction::FILTER])) {
            if (($filter = $this->getValue($config[Transaction::FILTER], $actionElm, true)) === null) {
                throw new CswException(Transaction::FILTER, CswException::MISSINGPARAMETERVALUE);
            }
            $action->setConstraint($filter);
        }
        try {
            $action->setItems($this->getValue($config[Transaction::ITEMS], $actionElm, false));
        } catch (Exception $ex) {
            ;
        }

        return $action;
    }
}
