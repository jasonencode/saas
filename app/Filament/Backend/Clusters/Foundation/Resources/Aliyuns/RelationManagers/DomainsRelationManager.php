<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\RelationManagers;

use AlibabaCloud\SDK\Domain\V20180129\Domain;
use AlibabaCloud\SDK\Domain\V20180129\Models\QueryDomainListRequest;
use App\Models\AliyunDomain;
use Darabonba\OpenApi\Models\Config;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    protected static ?string $title = '域名';

    protected static ?string $modelLabel = '域名';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function getTableRecords(): Paginator
    {
        $config = new Config([
            'accessKeyId' => $this->getOwnerRecord()->app_id,
            'accessKeySecret' => $this->getOwnerRecord()->app_secret,
            'endpoint' => 'domain.aliyuncs.com',
        ]);

        $page = $this->getPage();
        $perPage = $this->getTableRecordsPerPage();

        $request = new QueryDomainListRequest([
            'pageNum' => $page,
            'pageSize' => $perPage,
        ]);

        $response = new Domain($config)->queryDomainList($request);

        $result = [];
        foreach ($response->body->data->domain as $item) {
            $result[] = new AliyunDomain($item->toArray());
        }

        return new LengthAwarePaginator($result, $response->body->totalItemNum, $perPage, $page);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('InstanceId')
                    ->label('ID'),
                TextColumn::make('DomainName')
                    ->label('域名'),
                TextColumn::make('RegistrationDate')
                    ->label('注册时间'),
                TextColumn::make('ExpirationDate')
                    ->label('到期时间'),
                TextColumn::make('AliyunDomainStatus')
                    ->label('状态')
                    ->badge(),
                TextColumn::make('Remark')
                    ->label('备注'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('dns')
                    ->label('解析')
                    ->url(fn($record) => route('filament.backend.foundation.resources.aliyuns.dns', ['record' => $this->getOwnerRecord(), 'domain' => $record->DomainName]), true),
            ]);
    }
}