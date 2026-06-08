<?php

namespace Tests\Feature\BlockChain;

use App\Enums\BlockChain\ChainType;
use App\Enums\BlockChain\ContractDeployStatus;
use App\Enums\BlockChain\ContractType;
use App\Extensions\BlockChain\Adapters\FiscoAdapter;
use App\Jobs\BlockChain\DeployContractJob;
use App\Models\BlockChain\ChainAddress;
use App\Models\BlockChain\Contract;
use App\Models\BlockChain\Network;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DeployContractJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deploys_contract_and_updates_status(): void
    {
        config([
            'custom.block_chain.public_key' => null,
            'custom.block_chain.private_key' => 'invalid-private-key',
        ]);

        $network = Network::create([
            'name' => 'Fisco Testnet',
            'type' => ChainType::Fisco,
            'rpc_url' => 'https://fisco.test',
            'status' => true,
        ]);

        $address = ChainAddress::create([
            'network_id' => $network->id,
            'name' => 'deployer',
            'address' => '0xdeployer',
            'private_key' => str_repeat('a', 64),
        ]);

        $contract = Contract::create([
            'network_id' => $network->id,
            'deployer_id' => $address->id,
            'name' => 'DemoContract',
            'type' => ContractType::CUSTOM,
            'parameter' => json_encode(['token', 'TKN'], JSON_THROW_ON_ERROR),
            'bytecode' => '0x60806040',
            'abi' => '[{"inputs":[{"name":"name","type":"string"},{"name":"symbol","type":"string"}],"type":"constructor"}]',
            'deploy_status' => ContractDeployStatus::Pending,
        ]);

        $adapter = new class extends FiscoAdapter
        {
            public array $captured = [];

            public function deployContract(
                string $privateKey,
                string $bytecode,
                ?string $abi = null,
                array $constructorArgs = [],
                string $rpcUrl = '',
                array $sslOptions = [],
            ): array {
                $this->captured = [
                    'privateKey' => $privateKey,
                    'bytecode' => $bytecode,
                    'abi' => $abi,
                    'constructorArgs' => $constructorArgs,
                    'rpcUrl' => $rpcUrl,
                    'sslOptions' => $sslOptions,
                ];

                return [
                    'contract_address' => '0xcontract',
                    'tx_hash' => '0xtxhash',
                ];
            }
        };

        $this->app->instance(FiscoAdapter::class, $adapter);

        new DeployContractJob($contract)->handle();

        $contract->refresh();

        $this->assertSame(ContractDeployStatus::Deployed, $contract->deploy_status);
        $this->assertSame('0xcontract', $contract->address);
        $this->assertSame('0xtxhash', $contract->hash);
        $this->assertStringContainsString('部署成功', (string) $contract->remark);
        $this->assertSame(str_repeat('a', 64), $adapter->captured['privateKey']);
        $this->assertSame(['token', 'TKN'], $adapter->captured['constructorArgs']);
        $this->assertSame('https://fisco.test', $adapter->captured['rpcUrl']);
    }

    public function test_it_fails_when_deployer_does_not_belong_to_selected_network(): void
    {
        $selectedNetwork = Network::create([
            'name' => 'Selected Network',
            'type' => ChainType::Fisco,
            'rpc_url' => 'https://selected.test',
            'status' => true,
        ]);

        $deployerNetwork = Network::create([
            'name' => 'Other Network',
            'type' => ChainType::Fisco,
            'rpc_url' => 'https://other.test',
            'status' => true,
        ]);

        $address = ChainAddress::create([
            'network_id' => $deployerNetwork->id,
            'name' => 'deployer',
            'address' => '0xdeployer',
            'private_key' => str_repeat('b', 64),
        ]);

        $contract = Contract::create([
            'network_id' => $selectedNetwork->id,
            'deployer_id' => $address->id,
            'name' => 'DemoContract',
            'type' => ContractType::CUSTOM,
            'bytecode' => '0x60806040',
            'abi' => '[]',
            'deploy_status' => ContractDeployStatus::Pending,
        ]);

        try {
            new DeployContractJob($contract)->handle();
            $this->fail('Expected network mismatch exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('部署账户不属于当前合约选择的主网。', $exception->getMessage());
        }

        $contract->refresh();

        $this->assertSame(ContractDeployStatus::Failed, $contract->deploy_status);
        $this->assertStringContainsString('部署账户不属于当前合约选择的主网。', (string) $contract->remark);
    }

    public function test_it_marks_contract_failed_when_deploy_throws(): void
    {
        $network = Network::create([
            'name' => 'Fisco Testnet',
            'type' => ChainType::Fisco,
            'rpc_url' => 'https://fisco.test',
            'status' => true,
        ]);

        $address = ChainAddress::create([
            'network_id' => $network->id,
            'name' => 'deployer',
            'address' => '0xdeployer',
            'private_key' => 'plain-private-key',
        ]);

        $contract = Contract::create([
            'network_id' => $network->id,
            'deployer_id' => $address->id,
            'name' => 'DemoContract',
            'type' => ContractType::CUSTOM,
            'bytecode' => '0x60806040',
            'abi' => '[]',
            'deploy_status' => ContractDeployStatus::Pending,
        ]);

        $adapter = new class extends FiscoAdapter
        {
            public function deployContract(
                string $privateKey,
                string $bytecode,
                ?string $abi = null,
                array $constructorArgs = [],
                string $rpcUrl = '',
                array $sslOptions = [],
            ): array {
                throw new RuntimeException('deploy failed');
            }
        };

        $this->app->instance(FiscoAdapter::class, $adapter);

        try {
            new DeployContractJob($contract)->handle();
            $this->fail('Expected deploy failure exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('deploy failed', $exception->getMessage());
        }

        $contract->refresh();

        $this->assertSame(ContractDeployStatus::Failed, $contract->deploy_status);
        $this->assertStringContainsString('deploy failed', (string) $contract->remark);
    }
}
