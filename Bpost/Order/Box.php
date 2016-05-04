<?php
namespace TijsVerkoyen\Bpost\Bpost\Order;

use TijsVerkoyen\Bpost\Exception;

/**
 * bPost Box class
 *
 * @author Tijs Verkoyen <php-bpost@verkoyen.eu>
 */
class Box
{
    /**
     * @var \TijsVerkoyen\Bpost\Bpost\Order\Sender
     */
    private $sender;

    /**
     * @var \TijsVerkoyen\Bpost\Bpost\Order\Box\AtHome
     */
    private $nationalBox;

    /**
     * @var \TijsVerkoyen\Bpost\Bpost\Order\Box\International
     */
    private $internationalBox;

    /**
     * @var string
     */
    private $remark;

    /**
     * @var string
     */
    private $status;

    /**
     * @param \TijsVerkoyen\Bpost\Bpost\Order\Box\International $internationalBox
     *
     * @return $this
     */
    public function setInternationalBox(Box\International $internationalBox)
    {
        $this->internationalBox = $internationalBox;

        return $this;
    }

    /**
     * @return \TijsVerkoyen\Bpost\Bpost\Order\Box\International
     */
    public function getInternationalBox()
    {
        return $this->internationalBox;
    }

    /**
     * @param \TijsVerkoyen\Bpost\Bpost\Order\Box\National $nationalBox
     *
     * @return $this
     */
    public function setNationalBox(Box\National $nationalBox)
    {
        $this->nationalBox = $nationalBox;

        return $this;
    }

    /**
     * @return \TijsVerkoyen\Bpost\Bpost\Order\Box\National
     */
    public function getNationalBox()
    {
        return $this->nationalBox;
    }

    /**
     * @param string $remark
     *
     * @return $this
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param \TijsVerkoyen\Bpost\Bpost\Order\Sender $sender
     *
     * @return $this
     */
    public function setSender(Sender $sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @return \TijsVerkoyen\Bpost\Bpost\Order\Sender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * @param string $status
     *
     * @return $this
     * @throws \TijsVerkoyen\Bpost\Exception
     */
    public function setStatus($status)
    {
        $status = strtoupper($status);
        if (!in_array($status, self::getPossibleStatusValues())) {
            throw new Exception(sprintf('Invalid value, possible values are: %1$s.', implode(', ', self::getPossibleStatusValues())));
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public static function getPossibleStatusValues()
    {
        return [
            'OPEN',
            'PENDING',
            'PRINTED',
            'CANCELLED',
            'ON-HOLD',
            'ANNOUNCED',
            'IN_TRANSIT',
            'AWAITING_PICKUP',
            'DELIVERED',
            'BACK_TO_SENDER',
        ];
    }

    /**
     * Return the object as an array for usage in the XML
     *
     * @param \DomDocument $document
     * @param string       $prefix
     *
     * @return \DomElement
     */
    public function toXML(\DOMDocument $document, $prefix = null)
    {
        $tagName = 'box';
        if ($prefix !== null) {
            $tagName = $prefix . ':' . $tagName;
        }

        $box = $document->createElement($tagName);

        if ($this->getSender() !== null) {
            $box->appendChild($this->getSender()->toXML($document, $prefix));
        }
        if ($this->getNationalBox() !== null) {
            $box->appendChild($this->getNationalBox()->toXML($document, $prefix));
        }
        if ($this->getInternationalBox() !== null) {
            $box->appendChild($this->getInternationalBox()->toXML($document, $prefix));
        }
        if ($this->getRemark() !== null) {
            $tagName = 'remark';
            if ($prefix !== null) {
                $tagName = $prefix . ':' . $tagName;
            }
            $box->appendChild($document->createElement($tagName, $this->getRemark()));
        }

        return $box;
    }

    /**
     * @param \SimpleXMLElement $xml
     *
     * @return \TijsVerkoyen\Bpost\Bpost\Order\Box
     * @throws \TijsVerkoyen\Bpost\Exception
     */
    public static function createFromXML(\SimpleXMLElement $xml)
    {
        $box = new self();
        if (isset($xml->sender)) {
            $box->setSender(
                Sender::createFromXML($xml->sender->children('http://schema.post.be/shm/deepintegration/v3/common'))
            );
        }
        if (isset($xml->nationalBox)) {
            $nationalBoxData = $xml->nationalBox->children('http://schema.post.be/shm/deepintegration/v3/national');

            // build classname based on the tag name
            $className = '\\TijsVerkoyen\\Bpost\\Bpost\\Order\\Box\\' . ucfirst($nationalBoxData->getName());
            if ($nationalBoxData->getName() == 'at24-7') {
                $className = '\\TijsVerkoyen\\Bpost\\Bpost\\Order\\Box\\At247';
            }

            if (!method_exists($className, 'createFromXML')) {
                throw new Exception('Not Implemented');
            }

            $nationalBox = call_user_func([ $className, 'createFromXML' ], $nationalBoxData);

            $box->setNationalBox($nationalBox);
        }
        if (isset($xml->internationalBox)) {
            $internationalBoxData = $xml->internationalBox->children('http://schema.post.be/shm/deepintegration/v3/international');

            // build classname based on the tag name
            $className = '\\TijsVerkoyen\\Bpost\\Bpost\\Order\\Box\\' . ucfirst($internationalBoxData->getName());

            if (!method_exists($className, 'createFromXML')) {
                var_dump($className);
                throw new Exception('Not Implemented');
            }

            $internationalBox = call_user_func([ $className, 'createFromXML' ], $internationalBoxData);

            $box->setInternationalBox($internationalBox);
        }
        if (isset($xml->remark) && $xml->remark != '') {
            $box->setRemark((string) $xml->remark);
        }
        if (isset($xml->status) && $xml->status != '') {
            $box->setStatus((string) $xml->status);
        }

        return $box;
    }
}
