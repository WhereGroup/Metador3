<?php

namespace App\Component\Map\XmlUtils;

class XmlAssocArrayReader implements IContextReader
{

    const KEY_ATTRS = '_attrs_';
    const KEY_NAME = '_name_';
    const KEY_VALUE = '_value_';
    const KEY_CHILDREN = '_children_';
    protected $xmlReader;
    protected $writer;
    protected $xmlAssocArray;
    protected $path;

    public function __construct(EXmlReader $xmlReader, IContextWriter $writer = null)
    {
        $this->xmlReader = $xmlReader;
        $this->writer = $writer;
        $this->xmlAssocArray = [];
    }

    public function setXmlReader(EXmlReader $xmlReader): void
    {
        $this->xmlReader = $xmlReader;
    }

    public function getWriter(): ?IContextWriter
    {
        return $this->writer;
    }

    public function startElement(): void
    {
        $node = [
            self::KEY_NAME => $this->xmlReader->name,
        ];
        if (($attrs = $this->getAttrs())) {
            $node[self::KEY_ATTRS] = $attrs;
        }
        if (!$this->path) {
            $this->path = [];
            $this->xmlAssocArray = [$node];
            $this->path[] =& $this->xmlAssocArray[0];
        } else {
            $parentPos = count($this->path) - 1;
            $this->path[$parentPos][self::KEY_CHILDREN][] = $node;
            $last = count($this->path[$parentPos][self::KEY_CHILDREN]) - 1;
            $this->path[] =& $this->path[$parentPos][self::KEY_CHILDREN][$last];
        }
    }

    public function endElement(): void
    {
        $last = array_pop($this->path);
    }

    public function getAttrs(): ?array
    {
        if ($this->xmlReader->hasAttributes) {
            $attrs = [];
            $attributeCount = $this->xmlReader->attributeCount;
            for ($i = 0; $i < $attributeCount; $i++) {
                $this->xmlReader->moveToAttributeNo($i);
                $attrs[$this->xmlReader->name] = $this->xmlReader->value;
            }
            $this->xmlReader->moveToElement();

            return $attrs;
        } else {
            return null;
        }
    }

    public function addText(): void
    {
        $this->path[count($this->path) - 1][self::KEY_VALUE] = $this->xmlReader->value;
    }

    public function reset(): void
    {
        $this->xmlAssocArray = null;
        $this->path = null;
        $this->writer?->reset();
    }

    public function getContent()
    {
        return $this->writer?->write($this->xmlAssocArray);
    }
}
