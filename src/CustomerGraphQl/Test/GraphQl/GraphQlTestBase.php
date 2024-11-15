<?php
declare(strict_types=1);

namespace Ls\CustomerGraphQl\Test\GraphQl;

use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Abstract class for graphql test
 */
abstract class GraphQlTestBase extends GraphQlAbstract
{

    /**
     * Executes a GraphQL query with optional authorization headers.
     *
     * @param string $query The GraphQL query or mutation.
     * @param ?string $token Optional authentication token for Bearer authorization.
     *
     * @return array<string, mixed> The response array from the GraphQL resolver.
     * @throws \RuntimeException If the response is not valid or has errors.
     * @throws \Exception
     */
    protected function executeQuery(string $query, ?string $token = ''): array
    {
        // Initialize headers array
        $headers = [];

        // Add Authorization header if token is provided
        if ($token !== null) {
            $headers['Authorization'] = "Bearer $token";
        }

        return $this->graphQlQuery($query, [], '', $headers);
    }

    /**
     * Executes a GraphQL mutation with optional authorization headers.
     *
     * @param string $query The GraphQL query or mutation.
     * @param ?string $token Optional authentication token for Bearer authorization.
     *
     * @return array<string, mixed> The response array from the GraphQL resolver.
     * @throws \RuntimeException If the response is not valid or has errors.
     * @throws \Exception
     */
    protected function executeMutation(string $query, array $variables = [], ?string $token = ''): array
    {
        // Initialize headers array
        $headers = [];

        // Add Authorization header if token is provided
        if ($token !== null) {
            $headers['Authorization'] = "Bearer $token";
        }

        return $this->graphQlMutation($query, $variables, '', $headers);
    }

    /**
     * Authenticates the user and retrieves the token.
     *
     * @return string The authentication token.
     * @throws \Exception
     */
    protected function loginAndFetchToken(): string
    {
        $email    = getenv('EMAIL') ?: throw new \RuntimeException('TEST_USER_EMAIL is not set in .env');
        $password = getenv('PASSWORD') ?:
            throw new \RuntimeException('TEST_USER_PASSWORD is not set in .env');

        $loginMutation = <<<MUTATION
        mutation {
            generateCustomerToken(
                email: "{$email}"
                password: "{$password}"
            ) {
                token
            }
        }
        MUTATION;

        $response = $this->executeMutation($loginMutation);

        if (empty($response['generateCustomerToken']['token'])) {
            throw new \RuntimeException('Failed to retrieve authentication token.');
        }

        return $response['generateCustomerToken']['token'];
    }
}
