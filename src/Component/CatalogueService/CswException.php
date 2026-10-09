<?php

namespace App\Component\CatalogueService;

use Exception;

class CswException extends \Exception
{
    /* OGC exception code */
    const CANNOTLOCKALLFEATURES = 101;
    const DUPLICATESTOREDQUERYIDVALUE = 102;
    const DUPLICATESTOREDQUERYPARAMETERNAME = 103;
    const FEATURESNOTLOCKED = 104; // WFS-T
    const INVALIDLOCKID = 105; // WFS-T
    const INVALIDVALUE = 106;
    const LOCKHASEXPIRED = 107; // WFS-T
    const OPERATIONPARSINGFAILED = 108;
    const OPERATIONPROCESSINGFAILED = 109;
    /* OWS exception code */
    const OPERATIONNOTSUPPORTED = 110;
    const MISSINGPARAMETERVALUE = 111;
    const INVALIDPARAMETERVALUE = 112;
    const VERSIONNEGOTIATIONFAILED = 113;
    const INVALIDUPDATESEQUENCE = 114;
    const OPTIONNOTSUPPORTED = 115;
    const NOAPPLICABLECODE = 116;


    public function __construct($locator = "", $code = 105, Exception $previous = null)
    {
        // @TODO locator vs $previous locator
        parent::__construct($locator, $code, $previous);
    }

    /**
     * @return string|null
     */
    public function getLocator()
    {
        return $this->message && $this->hasLocator($this->code) ? $this->message : null;
    }

    /**
     * @return string
     */
    public function getCswCode()
    {
        return $this->getErrorCode($this->code);
    }


    public function getText(): array
    {
        if ($this->getPrevious()) {
            $messages = [];
            $this->getMessageText($this, $messages);

            return $messages;
        }

        return [$this->getErrorMessage()];
    }

    /**
     * @param Exception $e
     * @param array $messages
     */
    private function getMessageText(Exception $e, array &$messages)
    {
        $messages[] = $e->getMessage();

        if ($e instanceof CswException) {
            $messages[] = $e->getErrorMessage($e->getCode());
        }

        if ($e->getPrevious()) {
            $this->getMessageText($e->getPrevious(), $messages);
        }
    }

    /**
     * @param $code
     * @return bool|string
     */
    public function hasLocator($code)
    {
        switch ($code) {
            case self::OPERATIONNOTSUPPORTED:
            case self::CANNOTLOCKALLFEATURES:
            case self::DUPLICATESTOREDQUERYIDVALUE:
            case self::DUPLICATESTOREDQUERYPARAMETERNAME:
            case self::INVALIDLOCKID:
            case self::INVALIDVALUE:
            case self::MISSINGPARAMETERVALUE:
            case self::INVALIDPARAMETERVALUE:
            case self::OPTIONNOTSUPPORTED:
                return true;
            case self::FEATURESNOTLOCKED:
            case self::LOCKHASEXPIRED:
            case self::OPERATIONPROCESSINGFAILED:
            case self::OPERATIONPARSINGFAILED:
            case self::VERSIONNEGOTIATIONFAILED:
            case self::INVALIDUPDATESEQUENCE:
            case self::NOAPPLICABLECODE:
            default:
                return false;
        }
    }

    /**
     * @param $code
     * @return string
     */
    public function getErrorCode($code)
    {
        switch ($code) {
            case self::CANNOTLOCKALLFEATURES:
                return "CannotLockAllFeatures";
            case self::DUPLICATESTOREDQUERYIDVALUE:
                return "DuplicateStoredQueryIdValue";
            case self::DUPLICATESTOREDQUERYPARAMETERNAME:
                return "DuplicateStoredQueryParameterName";
            case self::FEATURESNOTLOCKED:
                return "FeaturesNotLocked";
            case self::INVALIDLOCKID:
                return "InvalidLockId";
            case self::INVALIDVALUE:
                return "InvalidValue";
            case self::LOCKHASEXPIRED:
                return "LockHasExpired";
            case self::OPERATIONPARSINGFAILED:
                return "OperationParsingFailed";
            case self::OPERATIONPROCESSINGFAILED:
                return "OperationProcessingFailed";
            case self::OPERATIONNOTSUPPORTED:
                return "OperationNotSupported";
            case self::MISSINGPARAMETERVALUE:
                return "MissingParameterValue";
            case self::INVALIDPARAMETERVALUE:
                return "InvalidParameterValue";
            case self::VERSIONNEGOTIATIONFAILED:
                return "VersionNegotiationFailed";
            case self::INVALIDUPDATESEQUENCE:
                return "InvalidUpdateSequence";
            case self::OPTIONNOTSUPPORTED:
                return "OptionNotSupported";
            case self::NOAPPLICABLECODE:
                return "NoApplicableCode";
            default:
                return $this->getErrorCode(self::NOAPPLICABLECODE);
        }
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        switch ($this->code) {
            case self::CANNOTLOCKALLFEATURES:
                return "Cannot lock all features";
            case self::DUPLICATESTOREDQUERYIDVALUE:
                return "Duplicate stored query identifier";
            case self::DUPLICATESTOREDQUERYPARAMETERNAME:
                return "Duplicate stored query parameter name";
            case self::FEATURESNOTLOCKED:
                return "Automatic locking not implemented";
            case self::INVALIDLOCKID:
                return "Invalid lock identifier";
            case self::INVALIDVALUE:
                return "Invalid feature or property value";
            case self::LOCKHASEXPIRED:
                return "Lock identifier has expired";
            case self::OPERATIONPARSINGFAILED:return "Parsing failed: ".$this->getMessage();
            case self::OPERATIONPROCESSINGFAILED:
                return "Operation processing failed";
            case self::OPERATIONNOTSUPPORTED:
                return "Request is for an operation that is not supported by this server/version";
            case self::MISSINGPARAMETERVALUE:
                return "Operation request does not include a parameter value";
            case self::INVALIDPARAMETERVALUE:
                return "Operation request contains an invalid parameter value";
            case self::VERSIONNEGOTIATIONFAILED:
                return "List of versions in AcceptVersions parameter value in GetCapabilities operation request"
                    ." did not include any version supported by this server";
            case self::INVALIDUPDATESEQUENCE:
                return "Value of (optional) updateSequence parameter in GetCapabilities operation request is"
                    ." greater than current value of service metadata updateSequence number";
            case self::OPTIONNOTSUPPORTED:
                return "Option is not supported";
            case self::NOAPPLICABLECODE:
                return "No other exceptionCode specified by this service and server applies to this exception";
            default:
                return $this->getErrorMessage(self::NOAPPLICABLECODE);
        }
    }

    /**
     * @return int
     */
    public function getHttpStatusCode()
    {
        switch ($this->code) {
            case self::CANNOTLOCKALLFEATURES:
                return 409;
            case self::DUPLICATESTOREDQUERYIDVALUE:
                return 403;
            case self::DUPLICATESTOREDQUERYPARAMETERNAME:
                return 403;
            case self::FEATURESNOTLOCKED:
                return 501;
            case self::INVALIDLOCKID:
                return 403;
            case self::INVALIDVALUE:
                return 403;
            case self::LOCKHASEXPIRED:
                return 403;
            case self::OPERATIONPARSINGFAILED:
                return 400;
            case self::OPERATIONPROCESSINGFAILED:
                return 500;
            case self::OPERATIONNOTSUPPORTED:
                return 501;
            case self::MISSINGPARAMETERVALUE:
                return 400;
            case self::INVALIDPARAMETERVALUE:
                return 400;
            case self::VERSIONNEGOTIATIONFAILED:
                return 400;
            case self::INVALIDUPDATESEQUENCE:
                return 400;
            case self::OPTIONNOTSUPPORTED:
                return 501;
            case self::NOAPPLICABLECODE:
                return 500;
            default:
                return $this->getHttpStatusCode(self::NOAPPLICABLECODE);
        }
    }
}
