<?php

declare(strict_types=1);

namespace App\Advisor\Domain\Assessment;

use App\Advisor\Domain\Enum\DiscardReasonCode;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class HardFilteredOfferTrace
{
    /**
     * @var list<DiscardReasonCode>
     */
    private array $reasonCodes;

    /**
     * @param list<DiscardReasonCode> $reasonCodes
     */
    public function __construct(
        private Uuid $offerVersionId,
        array $reasonCodes,
    ) {
        if (count($reasonCodes) === 0) {
            throw new InvalidArgumentException('HardFilteredOfferTrace reasonCodes cannot be empty.');
        }

        $unique = [];

        foreach ($reasonCodes as $code) {
            if (!$code instanceof DiscardReasonCode) {
                throw new InvalidArgumentException('Each reason code must be a DiscardReasonCode.');
            }

            $key = $code->value;

            if (isset($unique[$key])) {
                throw new InvalidArgumentException('Duplicate discard reason code: ' . $code->value);
            }

            $unique[$key] = $code;
        }

        $this->reasonCodes = array_values($unique);
    }

    public function offerVersionId(): Uuid
    {
        return $this->offerVersionId;
    }

    /**
     * @return list<DiscardReasonCode>
     */
    public function reasonCodes(): array
    {
        return $this->reasonCodes;
    }
}
