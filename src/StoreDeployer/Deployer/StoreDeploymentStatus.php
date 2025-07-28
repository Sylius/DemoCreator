<?php

declare(strict_types=1);

namespace App\StoreDeployer\Deployer;

enum StoreDeploymentStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Create an enum from a string (case‑insensitive for names, or exact for values).
     *
     * @throws \InvalidArgumentException if the input is not a valid status.
     */
    public static function fromString(string $value): self
    {
        $upper = strtoupper($value);

        // match backed values first, then case‑insensitive names
        foreach (self::cases() as $case) {
            if ($case->value === $value || $case->name === $upper) {
                return $case;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown status "%s".', $value));
    }

    /**
     * A human‑readable label, suitable for UIs or logs.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Waiting',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    /**
     * Returns true if transitioning from $this to $next is allowed.
     * Enforces a simple linear state machine:
     *   PENDING → IN_PROGRESS → (COMPLETED | FAILED)
     *   PENDING → FAILED
     *   IN_PROGRESS → FAILED
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PENDING => in_array($next, [self::IN_PROGRESS, self::FAILED], true),
            self::IN_PROGRESS => in_array($next, [self::COMPLETED, self::FAILED], true),
            default => false,
        };
    }

    // Predicates for each state
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this === self::IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Returns true when the process has finished one way or another.
     */
    public function isDone(): bool
    {
        return $this->isCompleted() || $this->isFailed();
    }
}
