<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Rule;

use App\Advisor\Domain\Assessment\HardFilteredOfferTrace;
use App\Advisor\Domain\Assessment\RankedOutOfferTrace;
use InvalidArgumentException;

final readonly class AlternativeSelectionResult
{
    /**
     * @var list<HardFilteredOfferTrace>
     */
    private array $hardFilteredOffers;

    /**
     * @var list<RankedOutOfferTrace>
     */
    private array $rankedOutOffers;

    /**
     * @param list<HardFilteredOfferTrace> $hardFilteredOffers
     * @param list<RankedOutOfferTrace> $rankedOutOffers
     */
    public function __construct(
        private ?AlternativeEvaluation $selectedAlternative,
        array $hardFilteredOffers,
        array $rankedOutOffers,
    ) {
        foreach ($hardFilteredOffers as $offer) {
            if (!$offer instanceof HardFilteredOfferTrace) {
                throw new InvalidArgumentException('hardFilteredOffers must be a list of HardFilteredOfferTrace.');
            }
        }

        foreach ($rankedOutOffers as $offer) {
            if (!$offer instanceof RankedOutOfferTrace) {
                throw new InvalidArgumentException('rankedOutOffers must be a list of RankedOutOfferTrace.');
            }
        }

        $this->hardFilteredOffers = array_values($hardFilteredOffers);
        $this->rankedOutOffers = array_values($rankedOutOffers);
    }

    public function selectedAlternative(): ?AlternativeEvaluation
    {
        return $this->selectedAlternative;
    }

    /**
     * @return list<HardFilteredOfferTrace>
     */
    public function hardFilteredOffers(): array
    {
        return $this->hardFilteredOffers;
    }

    /**
     * @return list<RankedOutOfferTrace>
     */
    public function rankedOutOffers(): array
    {
        return $this->rankedOutOffers;
    }

    public function hasSelection(): bool
    {
        return $this->selectedAlternative !== null;
    }
}
