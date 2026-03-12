<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\RelationManagers;

use AlibabaCloud\SDK\Ecs\V20140526\Ecs;
use AlibabaCloud\SDK\Ecs\V20140526\Models\DescribeInstancesRequest;
use App\Models\AliyunEcs;
use Darabonba\OpenApi\Models\Config;
use Exception;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EcsRelationManager extends RelationManager
{
    protected static string $relationship = 'ecs';

    protected static ?string $title = 'ECS';

    protected static ?string $modelLabel = 'ECS';

    public function getTableRecords(): Paginator
    {
        $config = new Config([
            'accessKeyId' => $this->getOwnerRecord()->app_id,
            'accessKeySecret' => $this->getOwnerRecord()->app_secret,
            'endpoint' => 'ecs.cn-beijing.aliyuncs.com',
        ]);

        $page = $this->getPage();
        $perPage = $this->getTableRecordsPerPage();

        $request = new DescribeInstancesRequest([
            'regionId' => 'cn-beijing',
            'pageNumber' => $page,
            'pageSize' => $perPage,
        ]);

        try {
            $response = new Ecs($config)->describeInstances($request);
            $result = [];
            foreach ($response->body->instances->instance as $item) {
                $result[] = new AliyunEcs($item->toArray());
            }

            return new LengthAwarePaginator($result, $response->body->totalCount, $perPage, $page);
        } catch (Exception) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('InstanceName')
                    ->label('实例名称')
                    ->description(fn(AliyunEcs $record) => $record->InstanceId),
                TextColumn::make('OSType')
                    ->label('操作系统')
                    ->description(fn(AliyunEcs $record) => $record->OSName),
                TextColumn::make('ZoneId')
                    ->label('地域'),
                TextColumn::make('PublicIpAddress.IpAddress')
                    ->label('IP地址')
                    ->copyable()
                    ->description(fn(AliyunEcs $record) => $record->VpcAttributes['PrivateIpAddress']['IpAddress'][0] ?? ''),
                TextColumn::make('Cpu')
                    ->label('配置信息')
                    ->formatStateUsing(function (AliyunEcs $record) {
                        return $record->Cpu.' (vCPU) '.($record->Memory / 1024).' GiB';
                    })
                    ->description(fn(AliyunEcs $record) => $record->InternetMaxBandwidthOut.'Mbps'),
                TextColumn::make('InstanceChargeType')
                    ->label('付费类型')
                    ->description(fn(AliyunEcs $record) => $record->ExpiredTime)
                    ->badge()
                    ->color(function (AliyunEcs $record) {
                        if (empty($record->ExpiredTime)) {
                            return null;
                        }
                        $expiredAt = Carbon::parse($record->ExpiredTime);
                        if ($expiredAt->isPast()) {
                            return 'danger';
                        }

                        return now()->diffInDays($expiredAt) < 14 ? 'warning' : null;
                    }),
                TextColumn::make('CreationTime')
                    ->label('创建时间'),
            ]);
    }
}
