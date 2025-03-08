<?php

namespace App\Admin\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpZip\ZipFile;

class ExportUsersAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'exportUsers';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->before(function () {
            $this->ensureExportDirectoryExists();
            $this->cleanupOldFiles();
        });

        $this->label('导出')
            ->icon('heroicon-o-arrow-down-tray')
            ->form([
                Select::make('format')
                    ->label('导出格式')
                    ->options([
                        'csv' => 'CSV',
                        'excel' => 'Excel',
                    ])
                    ->default('csv')
                    ->required(),
                CheckboxList::make('fields')
                    ->label('导出字段')
                    ->options([
                        'id' => 'ID',
                        'username' => '用户名',
                        'email' => '邮箱',
                        'phone' => '手机号',
                        'created_at' => '注册时间',
                        'points' => '积分',
                        'relation.parent_id' => '推荐人ID',
                        'relation.layer' => '层级',
                    ])
                    ->columns()
                    ->required(),
                Toggle::make('compress')
                    ->label('压缩文件')
                    ->helperText('导出文件将被压缩为 ZIP 格式，并添加密码保护')
                    ->default(true),
            ])
            ->action(function (array $data): void {
                try {
                    $query = $this->getFilteredTableQuery();
                    $fields = $data['fields'];

                    // 生成基础文件名
                    $baseFilename = 'users_'.date('YmdHis');

                    // 导出文件
                    $method = "exportTo".ucfirst($data['format']);
                    $exportedFile = $this->$method($query, $fields, $baseFilename);

                    // 如果需要压缩
                    if ($data['compress']) {
                        $zipFile = $this->compressFile($exportedFile, $baseFilename);
                        $downloadUrl = url('storage/exports/'.basename($zipFile));
                        // 删除原始文件
                        unlink($exportedFile);
                    } else {
                        $downloadUrl = url('storage/exports/'.basename($exportedFile));
                    }

                    $this->success('导出完成，<a href="'.$downloadUrl.'" target="_blank">点击下载</a>', true);
                } catch (Exception $e) {
                    $this->error('导出失败：'.$e->getMessage());
                }
            })
            ->modalHeading('导出用户数据')
            ->modalDescription('请选择导出格式和字段')
            ->modalSubmitActionLabel('开始导出');

        // 在挂载时清理旧文件
        $this->before(function () {
            $this->cleanupOldFiles();
        });
    }

    protected function ensureExportDirectoryExists(): void
    {
        $directory = storage_path('app/public/exports');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    protected function cleanupOldFiles(): void
    {
        try {
            $directory = storage_path('app/public/exports');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);

                return;
            }

            $files = glob($directory.'/*');
            $now = time();

            foreach ($files as $file) {
                if (is_file($file)) {
                    // 删除超过24小时的文件
                    if ($now - filemtime($file) >= 86400) {
                        unlink($file);
                    }
                }
            }
        } catch (Exception) {
        }
    }

    protected function compressFile(string $filePath, string $baseFilename): string
    {
        $zipPath = storage_path('app/public/exports/'.$baseFilename.'.zip');

        try {
            $zipFile = new ZipFile();

            // 添加文件到压缩包
            $zipFile
                ->addFile($filePath, basename($filePath))
                // 设置压缩级别 (0-9)
                ->setCompressionLevel(9)
                // 添加注释
                ->setArchiveComment('用户数据导出 - '.now()->format('Y-m-d H:i:s'))
                // 可选：添加密码保护
                ->setPassword(config('app.key'))
                // 保存压缩包
                ->saveAsFile($zipPath)
                // 关闭
                ->close();

            return $zipPath;
        } catch (Exception $e) {
            throw new Exception('压缩文件失败：'.$e->getMessage());
        }
    }

    protected function exportToCsv(Builder $query, array $fields, string $baseFilename): string
    {
        $filename = $baseFilename.'.csv';
        $path = storage_path('app/public/exports/'.$filename);

        $csv = Writer::createFromPath($path, 'w+');
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');
        $csv->setEscape('\\');

        // 写入表头
        $headers = array_map(fn($field) => __($field), $fields);
        $csv->insertOne($headers);

        // 分批写入数据
        $query->chunk(1000, function ($users) use ($csv, $fields) {
            foreach ($users as $user) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = data_get($user, $field);
                }
                $csv->insertOne($row);
            }
        });

        return $path;
    }

    protected function exportToExcel(Builder $query, array $fields, string $baseFilename): string
    {
        $filename = $baseFilename.'.xlsx';
        $path = storage_path('app/public/exports/'.$filename);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 写入表头
        foreach ($fields as $columnIndex => $field) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->setCellValue($columnLetter.'1', __($field));
        }

        // 写入数据
        $row = 2;
        $query->chunk(1000, function ($users) use ($sheet, $fields, &$row) {
            foreach ($users as $user) {
                foreach ($fields as $columnIndex => $field) {
                    $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
                    $value = data_get($user, $field);
                    $sheet->setCellValue($columnLetter.$row, $value);
                }
                $row++;
            }
        });

        // 设置列宽自适应
        foreach ($fields as $columnIndex => $field) {
            $columnLetter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // 设置表头样式
        $lastColumn = Coordinate::stringFromColumnIndex(count($fields));
        $headerRange = 'A1:'.$lastColumn.'1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E0E0E0',
                ],
            ],
        ]);

        // 保存文件
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }
}