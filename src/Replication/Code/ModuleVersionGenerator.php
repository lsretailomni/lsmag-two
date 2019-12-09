<?php
// @codingStandardsIgnoreFile

namespace Ls\Replication\Code;

use Sabre\Xml\LibXMLException;
use Sabre\Xml\ParseException;
use Sabre\Xml\Reader;
use Sabre\Xml\Service as XmlService;
use Zend\Code\Generator\GeneratorInterface;

/**
 * Class ModuleVersionGenerator
 * @package Ls\Replication\Code
 */
class ModuleVersionGenerator implements GeneratorInterface
{
    /** @var  string */
    private $xml_path;

    /** @var  string */
    private $xsd_path;

    /** @var  string */
    private $version;

    /** @var XmlService */
    private $xml_service;

    /** @var array|object|string */
    private $xml;

    /**
     * ModuleVersionGenerator constructor.
     * @param $xml_path
     * @param $xsd_path
     * @throws LibXMLException
     * @throws ParseException
     */
    public function __construct($xml_path, $xsd_path)
    {

        $this->xml_path    = $xml_path;
        $this->xsd_path    = $xsd_path;
        $this->xml_service = new XmlService();
        /** @var Reader $reader */
        $reader = $this->xml_service->getReader();

        $xml           = file_get_contents($this->xml_path);
        $parsed        = $this->xml_service->parse($xml);
        $parts         = explode('.', $parsed[0]['attributes']['setup_version']);
        $parts [2]     = intval($parts[2]) + 1;
        $this->version = join('.', $parts);

        $reader->XML($xml, 'UTF-8', LIBXML_PEDANTIC);
        $reader->setSchema($this->xsd_path);

        $this->xml = $reader->parse();
    }

    /**
     * @return string
     */
    public function generate()
    {

        $this->xml['value'][0]['attributes']['setup_version'] = $this->getVersion();
        $content                                              = $this->xml_service->write(null, $this->xml);
        $content                                              = str_replace('xmlns="" ', '', $content);
        return $content;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }
}
