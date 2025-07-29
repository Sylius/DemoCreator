<?php

declare(strict_types=1);

namespace App\StoreDeployer\ValueObject;

enum StoreDeploymentStatus: string
{
    case PENDING     = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED   = 'completed';
    case FAILED      = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING     => 'Waiting',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED   => 'Completed',
            self::FAILED      => 'Failed',
        };
    }

    public static function fromString(string $value): self
    {
        $upper = strtoupper($value);
        foreach (self::cases() as $case) {
            if ($case->value === $value || $case->name === $upper) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown status "%s".', $value));
    }
}
