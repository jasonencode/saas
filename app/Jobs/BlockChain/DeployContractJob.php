<?php

namespace App\Jobs\BlockChain;

use App\Enums\BlockChain\ContractDeployStatus;
use App\Jobs\BaseJob;
use App\Models\BlockChain\Contract;
use JsonException;
use RuntimeException;
use Throwable;

class DeployContractJob extends BaseJob
{
    public string $queue = 'block-chain';

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(protected Contract $contract) {}

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function handle(): void
    {
        $contract = $this->contract->fresh(['network', 'deployer.network']);

        if ($contract === null) {
            return;
        }

        if (filled($contract->address) || $contract->deploy_status === ContractDeployStatus::Deployed) {
            return;
        }

        $contract->update([
            'deploy_status' => ContractDeployStatus::Deploying,
        ]);

        try {
            $network = $contract->network ?? $contract->deployer?->network;
            $deployer = $contract->deployer;

            if ($network === null || $deployer === null) {
                throw new RuntimeException('合约缺少主网或部署账户。');
            }

            if ((int) $deployer->network_id !== (int) $network->id) {
                throw new RuntimeException('部署账户不属于当前合约选择的主网。');
            }

            $adapter = app($network->type->getAdapter());
            $result = $adapter->deployContract(
                privateKey: $deployer->getDecryptedPrivateKey(),
                bytecode: (string) $contract->bytecode,
                abi: $contract->abi,
                constructorArgs: $this->parseConstructorArguments($contract->parameter),
                rpcUrl: (string) $network->rpc_url,
                sslOptions: $network->getSslOptions(),
            );

            $contract->update([
                'network_id' => $network->id,
                'address' => $result['contract_address'] ?: null,
                'hash' => $result['tx_hash'] ?: null,
                'deploy_status' => ContractDeployStatus::Deployed,
                'remark' => $this->mergeRemark($contract->remark, '部署成功'),
            ]);
        } catch (Throwable $throwable) {
            $contract->update([
                'deploy_status' => ContractDeployStatus::Failed,
                'remark' => $this->mergeRemark($contract->remark, $throwable->getMessage()),
            ]);

            report($throwable);

            throw $throwable;
        }
    }

    /**
     * @throws JsonException
     *
     * @return array<int, mixed>
     */
    protected function parseConstructorArguments(?string $parameter): array
    {
        if (blank($parameter)) {
            return [];
        }

        $decoded = json_decode($parameter, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException('合约部署参数必须是 JSON 数组。');
        }

        if (!array_is_list($decoded)) {
            throw new RuntimeException('合约部署参数必须是按顺序排列的 JSON 数组。');
        }

        return $decoded;
    }

    protected function mergeRemark(?string $currentRemark, string $message): string
    {
        $currentRemark = trim((string) $currentRemark);
        $message = trim($message);

        if ($currentRemark === '') {
            return $message;
        }

        return $currentRemark.PHP_EOL.$message;
    }
}
