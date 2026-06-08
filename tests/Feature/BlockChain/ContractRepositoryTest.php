<?php

namespace Tests\Feature\BlockChain;

use App\Models\BlockChain\ContractRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_store_sol_source_code_in_repository(): void
    {
        $repository = ContractRepository::create([
            'name' => 'ERC20 Demo',
            'slug' => 'erc20-demo',
            'version' => '1.0.0',
            'compiler_version' => '0.8.28',
            'license' => 'MIT',
            'contract_name' => 'DemoToken',
            'source_path' => 'contracts/source/demo.sol',
            'source_name' => 'demo.sol',
            'source_size' => 128,
            'source_code' => 'pragma solidity ^0.8.28; contract DemoToken {}',
            'tags' => ['erc20', 'token'],
            'status' => true,
        ]);

        $repository->refresh();

        $this->assertSame('demo.sol', $repository->source_name);
        $this->assertStringContainsString('pragma solidity', (string) $repository->source_code);
        $this->assertSame('erc20', $repository->tags[0]);
        $this->assertTrue($repository->status);
    }
}
