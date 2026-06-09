<?php

namespace Tests\Unit\Enums\BlockChain;

use App\Enums\BlockChain\ChainType;
use App\Extensions\BlockChain\Adapters\Chain33Adapter;
use App\Extensions\BlockChain\Adapters\FiscoAdapter;
use PHPUnit\Framework\TestCase;

class ChainTypeTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('飞梭 (FISCO)', ChainType::Fisco->getLabel());
        $this->assertSame('复杂美 (Chain33)', ChainType::Chain33->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('success', ChainType::Fisco->getColor());
        $this->assertSame('info', ChainType::Chain33->getColor());
    }

    public function test_get_adapter(): void
    {
        $this->assertSame(FiscoAdapter::class, ChainType::Fisco->getAdapter());
        $this->assertSame(Chain33Adapter::class, ChainType::Chain33->getAdapter());
    }

    public function test_config_fields_returns_array(): void
    {
        $fiscoFields = ChainType::Fisco->configFields();
        $this->assertIsArray($fiscoFields);
        $this->assertNotEmpty($fiscoFields);

        // Fisco fields should be prefixed with 'fisco.'
        foreach ($fiscoFields as $key => $field) {
            $this->assertStringStartsWith('fisco.', $key);
            $this->assertArrayHasKey('label', $field);
            $this->assertArrayHasKey('type', $field);
        }
    }

    public function test_chain33_config_fields(): void
    {
        $chain33Fields = ChainType::Chain33->configFields();
        $this->assertIsArray($chain33Fields);
        $this->assertNotEmpty($chain33Fields);

        // Chain33 fields should be prefixed with 'chain33.'
        foreach ($chain33Fields as $key => $field) {
            $this->assertStringStartsWith('chain33.', $key);
        }
    }

    public function test_all_cases_have_labels_and_colors(): void
    {
        foreach (ChainType::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
            $this->assertNotEmpty($case->getColor());
            $this->assertNotEmpty($case->getAdapter());
            $this->assertIsArray($case->configFields());
        }
    }
}
