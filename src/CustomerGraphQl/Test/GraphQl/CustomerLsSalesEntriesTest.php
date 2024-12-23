<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\GraphQl;

class CustomerLsSalesEntriesTest extends GraphQlTestBase
{
    /**
     * @var $authToken
     */
    private $authToken;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->authToken = $this->loginAndFetchToken();
    }

    public function testLsSalesEntriesWithoutFilter(): void
    {
        $query = <<<'QUERY'
        query {
            customer {
                lsSalesEntries(pageSize: 1) {
                      click_and_collect_order
                      contact_address {
                        address1
                        address2
                        cell_phone_number
                        city
                        country
                        house_no
                        post_code
                        state_province_region
                        type
                      }
                      document_id
                      document_reg_time
                      external_id
                      id
                      id_type
                      items {
                        amount
                        click_and_collect_line
                        discount_amount
                        discount_percent
                        item_description
                        item_id
                        item_image_id
                        line_number
                        line_type
                        net_amount
                        net_price
                        parent_line
                        price
                        quantity
                        store_id
                        tax_amount
                        uom_id
                        variant_description
                        variant_id
                      }
                      line_item_count
                      payments {
                        amount
                        card_no
                        currency_code
                        currency_factor
                        line_number
                        tender_type
                      }
                      points_rewarded
                      points_used
                      posted
                      ship_to_address {
                        address1
                        address2
                        cell_phone_number
                        city
                        country
                        house_no
                        post_code
                        state_province_region
                        type
                      }
                      ship_to_email
                      ship_to_name
                      status
                      store_currency
                      store_id
                      store_name
                      total_amount
                      total_discount
                      total_net_amount
                      total_tax_amount
                    }
            }
        }
        QUERY;

        $response = $this->executeQuery($query, $this->authToken);

        $this->assertNotEmpty($response['customer']['lsSalesEntries']);
        $this->assertIsArray($response['customer']['lsSalesEntries']);
    }

    public function testLsSalesEntriesWithFilter(): void
    {
        // Fetch the ID from environment variables
        $id = getenv('LS_SALES_ENTRY_ID') ?: 'CO001813';

        $query = <<<QUERY
        query {
         customer {
            lsSalesEntries(filter: { id: "{$id}", type: "order" }) {
                  click_and_collect_order
                  contact_address {
                    address1
                    address2
                    cell_phone_number
                    city
                    country
                    house_no
                    post_code
                    state_province_region
                    type
                  }
                  document_id
                  document_reg_time
                  external_id
                  id
                  id_type
                  items {
                    amount
                    click_and_collect_line
                    discount_amount
                    discount_percent
                    item_description
                    item_id
                    item_image_id
                    line_number
                    line_type
                    net_amount
                    net_price
                    parent_line
                    price
                    quantity
                    store_id
                    tax_amount
                    uom_id
                    variant_description
                    variant_id
                  }
                  line_item_count
                  payments {
                    amount
                    card_no
                    currency_code
                    currency_factor
                    line_number
                    tender_type
                  }
                  points_rewarded
                  points_used
                  posted
                  ship_to_address {
                    address1
                    address2
                    cell_phone_number
                    city
                    country
                    house_no
                    post_code
                    state_province_region
                    type
                  }
                  ship_to_email
                  ship_to_name
                  status
                  store_currency
                  store_id
                  store_name
                  total_amount
                  total_discount
                  total_net_amount
                  total_tax_amount
                }
            }
        }
        QUERY;

        $response = $this->executeQuery($query, $this->authToken);

        $this->assertNotEmpty($response['customer']['lsSalesEntries']);
    }
}