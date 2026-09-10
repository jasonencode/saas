<?php

namespace App\Models\User;

use App\Enums\User\RealnameStatus;
use App\Enums\User\RealnameType;
use App\Models\Model;
use App\Models\Traits\BelongsToUser;
use App\Support\AES\AES;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

#[Unguarded]
class UserRealname extends Model
{
    use BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => RealnameType::class,
            'status' => RealnameStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * 身份证号写入器
     *
     * 数据库字段为 id_card_number，落库为 AES 密文；读取时原样返回密文，不做解密。
     * 企业认证不填该字段。
     *
     * @return Attribute<never, ?string>
     */
    protected function idCardNumber(): Attribute
    {
        return Attribute::make(
            set: static fn (?string $value) => blank($value) ? null : AES::encrypt($value),
        );
    }

    /**
     * 获取解密后的身份证号
     *
     * 身份证号在模型属性中始终为密文，仅在需要明文核对时显式调用本方法。
     *
     * @throws RuntimeException 密文格式错误或解密失败
     *
     * @return string|null 明文身份证号，未填写时返回 null
     */
    public function decryptedIdCardNumber(): ?string
    {
        $value = $this->getAttributeFromArray('id_card_number');

        return blank($value) ? null : AES::decrypt($value);
    }

    /**
     * 身份证正面 URL 访问器
     *
     * @return Attribute<?string, never>
     */
    protected function idCardFrontUrl(): Attribute
    {
        return Attribute::get(
            fn () => temporary_file_url($this->id_card_front)
        )->shouldCache();
    }

    /**
     * 身份证反面 URL 访问器
     *
     * @return Attribute<?string, never>
     */
    protected function idCardBackUrl(): Attribute
    {
        return Attribute::get(
            fn () => temporary_file_url($this->id_card_back)
        )->shouldCache();
    }

    /**
     * 营业执照 URL 访问器
     *
     * @return Attribute<?string, never>
     */
    protected function businessLicenseUrl(): Attribute
    {
        return Attribute::get(
            fn () => temporary_file_url($this->business_license)
        )->shouldCache();
    }

    /**
     * 是否待审核状态
     *
     * @return bool 是否待审核
     */
    public function isPending(): bool
    {
        return $this->status === RealnameStatus::Pending;
    }

    /**
     * 是否已通过状态
     *
     * @return bool 是否已通过
     */
    public function isApproved(): bool
    {
        return $this->status === RealnameStatus::Approved;
    }

    /**
     * 是否已拒绝状态
     *
     * @return bool 是否已拒绝
     */
    public function isRejected(): bool
    {
        return $this->status === RealnameStatus::Rejected;
    }
}
