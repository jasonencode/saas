<?php

namespace App\Console\Commands;

use App\Enums\System\AdminType;
use App\Models\System\Administrator;
use Exception;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:user {--force : 强制创建，跳过确认}')]
#[Description('创建管理员用户')]
class AdminUser extends Command
{
    public function handle(): int
    {
        $created = [];

        try {
            do {
                $data = $this->getUserData();

                if (! $this->option('force') && ! confirm("确认创建管理员: {$data['username']}?", default: true)) {
                    $this->info('操作已取消');

                    continue;
                }

                $data['type'] = AdminType::Admin;

                $admin = Administrator::create($data);
                $created[] = [$admin->id, $admin->username, $admin->name, $data['password']];

                $this->info('管理员创建成功！');
            } while (confirm('是否继续创建管理员?', default: false));

            if ($created) {
                $this->newLine();
                $this->info('已创建的管理员列表：');
                $this->table(
                    ['ID', '用户名', '名称', '密码'],
                    $created
                );
            }

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
                validate: fn ($value) => $this->validateField('name', $value),
                hint: '管理员显示名称'
            ),
            'username' => text(
                label: '用户名',
                required: true,
                validate: fn ($value) => $this->validateField('username', $value),
                hint: '登录用户名（4-32个字符）'
            ),
            'password' => password(
                label: '密码',
                required: true,
                validate: fn ($value) => $this->validateField('password', $value),
                hint: '登录密码（最少6个字符）'
            ),
        ];

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
