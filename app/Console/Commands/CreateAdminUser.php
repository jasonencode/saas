<?php

namespace App\Console\Commands;

use App\Enums\AdminType;
use App\Models\Administrator;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    /**
     * 命令名称和参数
     */
    protected $signature = 'admin:user {--force : 强制创建，跳过确认}';

    /**
     * 命令描述
     */
    protected $description = '创建管理员用户';

    /**
     * 执行命令
     */
    public function handle(): int
    {
        try {
            // 获取用户输入
            $data = $this->getUserData();

            // 创建确认
            if (!$this->option('force') && !$this->confirm("确认创建管理员: {$data['username']}?")) {
                $this->info('操作已取消');

                return self::SUCCESS;
            }

            $data['type'] = AdminType::Admin;

            // 创建管理员
            $admin = Administrator::create($data);

            $this->info('管理员创建成功！');
            $this->table(
                ['ID', '用户名', '名称'],
                [[$admin->id, $admin->username, $admin->name]]
            );

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("创建失败: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * 获取用户输入数据
     *
     * @throws InvalidArgumentException
     */
    protected function getUserData(): array
    {
        $data = [
            'name' => text(
                label: '名称',
                required: true,
                validate: fn($value) => $this->validateField('name', $value),
                hint: '管理员显示名称'
            ),
            'username' => text(
                label: '用户名',
                required: true,
                validate: fn($value) => $this->validateField('username', $value),
                hint: '登录用户名（4-32个字符）'
            ),
            'password' => password(
                label: '密码',
                required: true,
                validate: fn($value) => $this->validateField('password', $value),
                hint: '登录密码（最少6个字符）'
            ),
        ];

        // 密码加密
        $data['password'] = Hash::make($data['password']);

        return $data;
    }

    /**
     * 验证字段
     *
     * @throws InvalidArgumentException
     */
    protected function validateField(string $field, mixed $value): ?string
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:32'],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:32',
                'unique:administrators,username',
            ],
            'password' => ['required', Password::min(6)],
        ];

        $validator = Validator::make(
            [$field => $value],
            [$field => $rules[$field]]
        );

        return $validator->fails()
            ? $validator->errors()->first()
            : null;
    }
}
