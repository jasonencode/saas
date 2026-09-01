<?php

namespace Tests\Unit;

use App\Rules\PaymentPasswordRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentPasswordRuleTest extends TestCase
{
    private PaymentPasswordRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new PaymentPasswordRule;
    }

    private function validate(mixed $value): ?string
    {
        $error = null;

        $this->rule->validate('password', $value, function (string $message) use (&$error): void {
            $error = $message;
        });

        return $error;
    }

    // ========================================
    // 合法密码
    // ========================================

    #[DataProvider('validPasswords')]
    public function test_accepts_valid_password(string $password): void
    {
        $this->assertNull($this->validate($password));
    }

    public static function validPasswords(): array
    {
        return [
            '普通 6 位数字' => ['258041'],
            '以 0 开头' => ['030917'],
            '无序数字' => ['864213'],
            '全不同数字' => ['135790'],
            '含 0 的合法密码' => ['509317'],
        ];
    }

    // ========================================
    // 重复数字
    // ========================================

    #[DataProvider('repeatedPasswords')]
    public function test_rejects_repeated_digits(string $password): void
    {
        $this->assertSame('支付密码不能为重复数字', $this->validate($password));
    }

    public static function repeatedPasswords(): array
    {
        return [
            '全 1' => ['111111'],
            '全 0' => ['000000'],
            '全 9' => ['999999'],
            '全 5' => ['555555'],
        ];
    }

    // ========================================
    // 连续数字 - 升序
    // ========================================

    #[DataProvider('ascendingPasswords')]
    public function test_rejects_ascending_consecutive(string $password): void
    {
        $this->assertSame('支付密码不能为连续数字', $this->validate($password));
    }

    public static function ascendingPasswords(): array
    {
        return [
            '标准升序 123456' => ['123456'],
            '以 0 开头升序 012345' => ['012345'],
            '循环移位升序 789012' => ['789012'],
            '循环移位升序 901234' => ['901234'],
            '中间起升序 234567' => ['234567'],
            '循环移位升序 678901' => ['678901'],
        ];
    }

    // ========================================
    // 连续数字 - 降序
    // ========================================

    #[DataProvider('descendingPasswords')]
    public function test_rejects_descending_consecutive(string $password): void
    {
        $this->assertSame('支付密码不能为连续数字', $this->validate($password));
    }

    public static function descendingPasswords(): array
    {
        return [
            '标准降序 987654' => ['987654'],
            '循环移位降序 321098' => ['321098'],
            '循环移位降序 109876' => ['109876'],
            '以 1 结尾降序 654321' => ['654321'],
            '循环移位降序 210987' => ['210987'],
        ];
    }

    // ========================================
    // 格式错误
    // ========================================

    #[DataProvider('invalidFormatPasswords')]
    public function test_rejects_invalid_format(mixed $password, string $expectedError): void
    {
        $this->assertSame($expectedError, $this->validate($password));
    }

    public static function invalidFormatPasswords(): array
    {
        return [
            '少于 6 位' => ['12345', '支付密码必须是 6 位数字'],
            '多于 6 位' => ['1234560', '支付密码必须是 6 位数字'],
            '非数字' => ['abc123', '支付密码必须为纯数字'],
            '空字符串' => ['', '支付密码必须为纯数字'],
            '含字母' => ['12a456', '支付密码必须为纯数字'],
        ];
    }
}
