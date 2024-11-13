<?php

declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\GraphQl;

class CustomerLsAccountTest extends GraphQlTestBase
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

    public function testLsAccountQueryWithAuthentication(): void
    {
        $query = <<<'QUERY'
        query {
            customer {
                lsAccount {
                    card_id
                    contact_id
                    username
                    account_id
                    scheme {
                        club_name
                        loyalty_level
                        point_balance
                        points_expiry
                        points_expiry_interval
                        next_level {
                            club_name
                            loyalty_level
                            benefits
                            points_needed
                        }
                    }
                }
            }
        }
      QUERY;

        $response = $this->executeQuery(
            $query,
            $this->authToken
        );

        $this->assertNotEmpty($response['customer']['lsAccount']);
        $this->assertIsString($response['customer']['lsAccount']['card_id']);
        $this->assertIsString($response['customer']['lsAccount']['contact_id']);
        $this->assertIsString($response['customer']['lsAccount']['scheme']['club_name']);
    }
}
