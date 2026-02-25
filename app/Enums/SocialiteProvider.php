<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * 全部支持的第三方登录，可自行选择开启哪些
 */
enum SocialiteProvider: string implements HasLabel
{
    case Alipay = 'Alipay';

//    case Azure = 'Azure';

//    case Baidu = 'Baidu';

//    case Coding = 'Coding';

//    case DingTalk = 'DingTalk';

//    case Douban = 'Douban';

    case Douyin = 'Douyin';

//    case Facebook = 'Facebook';

//    case FeiShu = 'FeiShu';

//    case Figma = 'Figma';

//    case Gitee = 'Gitee';

//    case GitHub = 'GitHub';

//    case Google = 'Google';

//    case Lark = 'Lark';

//    case Line = 'Line';

//    case Linkedin = 'Linkedin';

//    case OpenWeWork = 'OpenWeWork';

//    case Outlook = 'Outlook';

//    case PayPal = 'PayPal';

//    case QCloud = 'QCloud';

    case QQ = 'QQ';

    case Taobao = 'Taobao';

//    case Tapd = 'Tapd';

//    case TouTiao = 'TouTiao';

    case WeChat = 'WeChat';

//    case Weibo = 'Weibo';

//    case WeWork = 'WeWork';

//    case XiGua = 'XiGua';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Alipay => '支付宝',
//            self::Azure => '微软',
//            self::Baidu => '百度',
//            self::Coding => 'Coding',
//            self::DingTalk => '钉钉',
//            self::Douban => '豆瓣',
            self::Douyin => '抖音',
//            self::Facebook => '脸书',
//            self::FeiShu => '飞书',
//            self::Figma => 'Figma',
//            self::Gitee => '码云',
//            self::GitHub => 'GitHub',
//            self::Google => '谷歌',
//            self::Lark => 'Lark',
//            self::Line => 'Line',
//            self::Linkedin => '领英',
//            self::OpenWeWork => '企业微信(三方)',
//            self::Outlook => 'Outlook',
//            self::PayPal => 'PayPal',
//            self::QCloud => '腾讯云',
            self::QQ => 'QQ',
            self::Taobao => '淘宝',
//            self::Tapd => 'TAPD',
//            self::TouTiao => '头条',
            self::WeChat => '微信',
//            self::Weibo => '微博',
//            self::WeWork => '企业微信',
//            self::XiGua => '西瓜视频',
        };
    }
}
