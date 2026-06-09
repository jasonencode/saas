<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\Gender;
use PHPUnit\Framework\TestCase;

class GenderTest extends TestCase
{
    public function test_from_code_returns_male_for_1(): void
    {
        $this->assertSame(Gender::Male, Gender::fromCode(1));
    }

    public function test_from_code_returns_female_for_2(): void
    {
        $this->assertSame(Gender::Female, Gender::fromCode(2));
    }

    public function test_from_code_returns_secret_for_unknown(): void
    {
        $this->assertSame(Gender::Secret, Gender::fromCode(0));
        $this->assertSame(Gender::Secret, Gender::fromCode(99));
    }

    public function test_get_code(): void
    {
        $this->assertSame(1, Gender::Male->getCode());
        $this->assertSame(2, Gender::Female->getCode());
        $this->assertSame(0, Gender::Secret->getCode());
    }

    public function test_get_label(): void
    {
        $this->assertSame('男', Gender::Male->getLabel());
        $this->assertSame('女', Gender::Female->getLabel());
        $this->assertSame('保密', Gender::Secret->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('danger', Gender::Male->getColor());
        $this->assertSame('success', Gender::Female->getColor());
        $this->assertSame('info', Gender::Secret->getColor());
    }

    public function test_from_code_and_get_code_are_inverse(): void
    {
        foreach (Gender::cases() as $case) {
            $this->assertSame($case, Gender::fromCode($case->getCode()));
        }
    }
}
