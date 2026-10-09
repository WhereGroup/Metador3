<?php

namespace App\Component\Map\XmlUtils;

use XMLReader;

class EXmlReader extends XMLReader
{
    protected $xpath;
    protected $currentName;
    protected $contextReader;
    protected $toOutputReader;
    protected $readers;

    public function __construct(array $readers = [])
    {
        $this->xpath = '';
        $this->readers = count($readers) > 0 ? $readers : [];
        $this->contextReader = null;
        $this->toOutputReader = null;
    }

    public static function create($uri, array $readers = [], $encoding = null, $readOptions = null): ?EXmlReader
    {
        if (!$uri) {
            return null;
        }
        $reader = new EXmlReader($readers);
        $opened = $reader->open($uri, $encoding, $readOptions);

        return $opened ? $reader : null;
    }

    public function addReader($path, IContextReader $reader): void
    {
        if (is_array($path)) {
            foreach ($path as $value) {
                $this->readers[$value] = $reader;
            }
        } else {
            $this->readers[$path] = $reader;
        }
    }

    public function getXpath(): string
    {
        return $this->xpath;
    }

    protected function activateContextReader(): void
    {
        if ($this->contextReader === null && isset($this->readers[$this->xpath])) {
            $this->toOutputReader = null;
            $this->contextReader = $this->readers[$this->xpath];
            $this->contextReader->reset();
        } elseif ($this->contextReader === null && isset($this->readers[$this->currentName])) {
            $this->toOutputReader = null;
            $this->contextReader = $this->readers[$this->currentName];
            $this->contextReader->reset();
        }
    }

    protected function deactivateContextReader(): ?IContextReader
    {
        if ($this->contextReader !== null && isset($this->readers[$this->xpath])) {
            $this->contextReader = null;
            $this->toOutputReader = $this->readers[$this->xpath];

            return $this->toOutputReader;
        } elseif ($this->contextReader !== null && isset($this->readers[$this->currentName])) {
            $this->contextReader = null;
            $this->toOutputReader = $this->readers[$this->currentName];

            return $this->toOutputReader;
        }

        return null;
    }

    protected function fromRoot(): void
    {
        $this->currentName = $this->prefixedName();
        $this->xpath .= '/'.$this->currentName;
    }

    protected function toRoot(): void
    {
        $this->currentName = $this->prefixedName();
        $this->xpath = substr($this->xpath, 0, strlen($this->xpath) - strlen('/'.$this->currentName));
    }

    protected function prefixedName(): string
    {
        return ($this->prefix ? $this->prefix.':' : '').$this->localName;
    }

    public function read()
    {
        if (parent::read()) {
            switch ($this->nodeType) {
                case EXmlReader::ELEMENT:
                    $this->fromRoot();
                    $this->activateContextReader();
                    if ($this->contextReader) {
                        $this->contextReader->startElement();
                    }
                    break;
                case EXmlReader::TEXT:
                case EXmlReader::CDATA:
                    if ($this->contextReader) {
                        $this->contextReader->addText();
                    }
                    break;
                case EXmlReader::END_ELEMENT:
                    $this->toRoot();
                    if ($this->contextReader) {
                        $this->contextReader->endElement();
                    }
                    if (($reader = $this->deactivateContextReader())) {
                        return $reader;
                    } else {
                        return true;
                    }
                    break;
                default:
                    ;
            }

            return true;
        } else {
            return false;
        }
    }

    public function readComponent(): bool
    {
        while (($res = $this->read())) {
            if ($res instanceof IContextReader) {
                return true;
            }
        }

        return false;
    }

    public function getContent()
    {
        return $this->toOutputReader->getContent();
    }
}
