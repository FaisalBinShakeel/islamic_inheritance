<?php

declare(strict_types=1);

namespace Faraid;

/** One heir group's entitlement, with the reason it holds that entitlement. */
final class Share
{
    public function __construct(
        public readonly HeirType $heir,
        public readonly int $count,
        public readonly Fraction $share,
        /** quranic | residuary | quranic_and_residuary | representation */
        public readonly string $basis,
        /** Translation key for the plain-language "why this share" sentence. */
        public readonly string $reasonKey,
        /** For shares taken by representation: which predeceased child they come through. */
        public readonly ?string $via = null,
    ) {
    }

    public function withShare(Fraction $share): self
    {
        return new self($this->heir, $this->count, $share, $this->basis, $this->reasonKey, $this->via);
    }

    public function withBasis(string $basis, ?string $reasonKey = null): self
    {
        return new self($this->heir, $this->count, $this->share, $basis, $reasonKey ?? $this->reasonKey, $this->via);
    }

    /** The share one member of this group receives. */
    public function perHead(): Fraction
    {
        return $this->count > 1
            ? $this->share->divide(Fraction::of($this->count))
            : $this->share;
    }
}
