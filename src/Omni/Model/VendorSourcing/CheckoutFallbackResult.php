<?php
declare(strict_types=1);

namespace Ls\Omni\Model\VendorSourcing;

/**
 * Accumulates the per-item {@see ItemSourcingVerdict}s produced by a single
 * {@see CheckoutFallbackService::evaluate()}/{@see CheckoutFallbackService::evaluateDelivery()} run.
 */
class CheckoutFallbackResult
{
    /** @var ItemSourcingVerdict[] */
    private array $verdicts = [];

    public function addVerdict(ItemSourcingVerdict $verdict): void
    {
        $this->verdicts[] = $verdict;
    }

    /**
     * @return ItemSourcingVerdict[]
     */
    public function getVerdicts(): array
    {
        return $this->verdicts;
    }

    public function hasBlockedItems(): bool
    {
        foreach ($this->verdicts as $verdict) {
            if ($verdict->isBlocked()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ItemSourcingVerdict[]
     */
    public function getBlockedVerdicts(): array
    {
        return array_values(array_filter(
            $this->verdicts,
            static fn (ItemSourcingVerdict $verdict): bool => $verdict->isBlocked()
        ));
    }
}
