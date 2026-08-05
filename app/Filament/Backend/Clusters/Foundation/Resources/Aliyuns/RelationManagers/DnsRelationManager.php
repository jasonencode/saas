<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\RelationManagers;

use AlibabaCloud\SDK\Alidns\V20150109\Alidns;
use AlibabaCloud\SDK\Alidns\V20150109\Models\AddDomainRecordRequest;
use AlibabaCloud\SDK\Alidns\V20150109\Models\DeleteDomainRecordRequest;
use AlibabaCloud\SDK\Alidns\V20150109\Models\DescribeDomainRecordsRequest;
use AlibabaCloud\SDK\Alidns\V20150109\Models\UpdateDomainRecordRequest;
use App\Enums\Foundation\AliyunDnsType;
use App\Models\Foundation\AliyunDns;
use Darabonba\OpenApi\Models\Config;
use Exception;
use Filament\Actions;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class DnsRelationManager extends RelationManager
{
    protected static string $relationship = 'dns';

    protected static ?string $title = '解析记录';

    protected static ?string $modelLabel = '解析记录';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('RR')
                    ->label('主机记录')
                    ->required(),
                Forms\Components\Select::make('Type')
                    ->label('记录类型')
                    ->required()
                    ->options(AliyunDnsType::class)
                    ->default(AliyunDnsType::A),
                Forms\Components\TextInput::make('Value')
                    ->label('记录值')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->getRecords())
            ->columns([
                Tables\Columns\TextColumn::make('RR')
                    ->label('主机记录'),
                Tables\Columns\TextColumn::make('Line')
                    ->label('解析线路'),
                Tables\Columns\TextColumn::make('Type')
                    ->label('记录类型'),
                Tables\Columns\TextColumn::make('Value')
                    ->label('记录值')
                    ->copyable(),
                Tables\Columns\TextColumn::make('Status')
                    ->label('启用状态')
                    ->sortable(),
                Tables\Columns\TextColumn::make('TTL')
                    ->label('TTL'),
                Tables\Columns\TextColumn::make('CreateTimestamp')
                    ->label(__('backend.created_at')),
                Tables\Columns\TextColumn::make('UpdateTimestamp')
                    ->label('最新更新时间'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->action(function (CreateAction $action, array $data): void {
                        $domain = request()->route('domain') ?? session('filament_aliyun_dns_domain') ?? $this->getDomainFromReferer();
                        if (!$domain) {
                            $action->failureNotificationTitle('缺少域名参数');
                            $action->failure();

                            return;
                        }

                        $request = new AddDomainRecordRequest([
                            'domainName' => $domain,
                            'RR' => $data['RR'],
                            'type' => $data['Type']->value,
                            'value' => $data['Value'],
                        ]);

                        $this->getAliyunClient()->addDomainRecord($request);

                        $action->successNotificationTitle('解析记录已创建');
                        $action->success();

                        $this->dispatch('$refresh');
                    }),
            ])
            ->recordActions([
                Actions\EditAction::make()
                    ->action(function (AliyunDns $record, Actions\EditAction $action, array $data): void {
                        $request = new UpdateDomainRecordRequest([
                            'recordId' => $record->RecordId,
                            'RR' => $data['RR'],
                            'type' => $data['Type']->value,
                            'value' => $data['Value'],
                        ]);

                        $this->getAliyunClient()->updateDomainRecord($request);

                        $action->successNotificationTitle('解析记录已更新');
                        $action->success();

                        $this->dispatch('$refresh');
                    }),
                Actions\DeleteAction::make()
                    ->action(function (AliyunDns $record, Actions\DeleteAction $action): void {
                        $request = new DeleteDomainRecordRequest([
                            'recordId' => $record->RecordId,
                        ]);

                        $this->getAliyunClient()->deleteDomainRecord($request);

                        $action->successNotificationTitle('解析记录已删除');
                        $action->success();

                        $this->dispatch('$refresh');
                    }),
            ]);
    }

    protected function getRecords(): Paginator
    {
        $page = $this->getPage();
        $perPage = $this->getTableRecordsPerPage();

        $domain = request()->route('domain') ?? $this->getDomainFromReferer();

        if (!$domain) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $request = new DescribeDomainRecordsRequest([
            'domainName' => $domain,
            'pageNumber' => $page,
            'pageSize' => $perPage,
        ]);

        try {
            $response = $this->getAliyunClient()->describeDomainRecords($request);

            $result = [];
            foreach ($response->body->domainRecords->record ?? [] as $item) {
                $dns = new AliyunDns([
                    ...$item->toArray(),
                    'CreateTimestamp' => $item->createTimestamp / 1000,
                    'UpdateTimestamp' => $item->updateTimestamp / 1000,
                    'exists' => true,
                ]);
                $dns->exists = true;
                $result[] = $dns;
            }

            return new LengthAwarePaginator($result, $response->body->totalCount, $perPage, $page);
        } catch (Exception) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
    }

    protected function getDomainFromReferer(): ?string
    {
        $referer = request()->headers->get('referer', '');
        if (!$referer) {
            return null;
        }

        $match = Str::of($referer)->match('#/dns/([^/?]+)#');

        return (string) $match;
    }

    protected function getAliyunClient(): Alidns
    {
        $config = new Config([
            'accessKeyId' => $this->getOwnerRecord()->app_id,
            'accessKeySecret' => $this->getOwnerRecord()->app_secret,
            'endpoint' => 'alidns.aliyuncs.com',
        ]);

        return new Alidns($config);
    }
}
