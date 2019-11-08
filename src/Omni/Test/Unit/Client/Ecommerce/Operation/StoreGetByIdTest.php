<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\ClassMap;
use \Ls\Omni\Client\Ecommerce\Entity\Store;
use \Ls\Omni\Service\ServiceType;
use \Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class StoreGetByIdTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $this->storeGetByIdMock = $this->getMockBuilder(\Ls\Omni\Client\Ecommerce\Operation\StoreGetById::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseUrl = $_ENV['BASE_URL'];
        $url = implode('/', [$baseUrl, 'UCService.svc?singlewsdl']);
        $service_type = new ServiceType(ServiceType::ECOMMERCE);
        $uri = UriFactory::factory($url);
        $this->client = new OmniClient($uri, $service_type);
        $this->client->setClassmap(ClassMap::getClassMap());
    }

    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param = array(
            'storeId' => 'S0010'
        );
        $response = $this->client->StoreGetById($param);
        $result = $response->getResult();
        $this->assertInstanceOf(Store::class, $result);
        $this->assertNotNull($result->getLatitude());
        $this->assertNotNull($result->getLongitude());
        $this->assertNotNull($result->getPhone());
        $this->assertNotNull($result->getStoreHours());
        $this->assertNotNull($result->getAddress());
        $this->assertEquals('S0010', $result->getId());
        $this->assertEquals('Cronus Café', $result->getDescription());
    }


    private function getXml()
    {
        return <<<XML
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
   <s:Body>
      <StoreGetByIdResponse xmlns="http://lsretail.com/LSOmniService/EComm/2017/Service">
         <StoreGetByIdResult xmlns:a="http://lsretail.com/LSOmniService/Base/2017" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
            <a:Id>S0010</a:Id>
            <a:Address>
               <a:Address1>878 Longhorn Street</a:Address1>
               <a:Address2/>
               <a:CellPhoneNumber i:nil="true"/>
               <a:City>Glasgow</a:City>
               <a:Country>GB</a:Country>
               <a:HouseNo i:nil="true"/>
               <a:Id/>
               <a:PhoneNumber i:nil="true"/>
               <a:PostCode>G1 1PP</a:PostCode>
               <a:StateProvinceRegion/>
               <a:Type>Store</a:Type>
            </a:Address>
            <a:CultureName i:nil="true"/>
            <a:Currency>
               <a:Id>GBP</a:Id>
               <a:AmountRoundingMethod>RoundNearest</a:AmountRoundingMethod>
               <a:Culture/>
               <a:DecimalPlaces>2</a:DecimalPlaces>
               <a:DecimalSeparator/>
               <a:Description/>
               <a:Postfix/>
               <a:Prefix/>
               <a:RoundOffAmount>0.01</a:RoundOffAmount>
               <a:RoundOffSales>0.01</a:RoundOffSales>
               <a:SaleRoundingMethod>RoundNearest</a:SaleRoundingMethod>
               <a:Symbol/>
               <a:ThousandSeparator/>
            </a:Currency>
            <a:DefaultCustomer i:nil="true"/>
            <a:Description>Cronus Café</a:Description>
            <a:Distance>0</a:Distance>
            <a:FunctionalityProfileId i:nil="true"/>
            <a:Images>
               <a:ImageView>
                  <a:Id>CR-COFFE HOUSE</a:Id>
                  <a:AvgColor/>
                  <a:DisplayOrder>0</a:DisplayOrder>
                  <a:Format/>
                  <a:Image/>
                  <a:ImgSize>
                     <a:Height>0</a:Height>
                     <a:UseMinHorVerSize>false</a:UseMinHorVerSize>
                     <a:Width>0</a:Width>
                  </a:ImgSize>
                  <a:LoadFromFile>false</a:LoadFromFile>
                  <a:Location>http://omnidevklpc.lsretail.local/lsomniservice411/ucservice.svc/ImageStreamGetById?id=CR-COFFE HOUSE&amp;width={0}&amp;height={1}</a:Location>
                  <a:LocationType>Image</a:LocationType>
               </a:ImageView>
            </a:Images>
            <a:IsClickAndCollect>false</a:IsClickAndCollect>
            <a:Latitude>55.864599999999996</a:Latitude>
            <a:Longitude>-4.2596099999999995</a:Longitude>
            <a:Phone>1144 5555</a:Phone>
            <a:StoreHours>
               <a:StoreHours>
                  <a:DayOfWeek>3</a:DayOfWeek>
                  <a:Description>Wednesday</a:Description>
                  <a:NameOfDay>Wednesday</a:NameOfDay>
                  <a:OpenFrom>0001-01-01T09:00:00Z</a:OpenFrom>
                  <a:OpenTo>0001-01-01T12:00:00Z</a:OpenTo>
                  <a:StoreHourtype>MainStore</a:StoreHourtype>
                  <a:StoreId>S0010</a:StoreId>
                  <a:Type>Normal</a:Type>
               </a:StoreHours>
               <a:StoreHours>
                  <a:DayOfWeek>4</a:DayOfWeek>
                  <a:Description>Thursday</a:Description>
                  <a:NameOfDay>Thursday</a:NameOfDay>
                  <a:OpenFrom>0001-01-01T04:00:00Z</a:OpenFrom>
                  <a:OpenTo>0001-01-01T08:00:00Z</a:OpenTo>
                  <a:StoreHourtype>MainStore</a:StoreHourtype>
                  <a:StoreId>S0010</a:StoreId>
                  <a:Type>Normal</a:Type>
               </a:StoreHours>
            </a:StoreHours>
            <a:StoreServices/>
            <a:TaxGroupId/>
            <a:UseDefaultCustomer i:nil="true"/>
         </StoreGetByIdResult>
      </StoreGetByIdResponse>
   </s:Body>
</s:Envelope>
XML;
    }
}