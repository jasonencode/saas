<!DOCTYPE html>
<html>
<head>
    <title>分拣单</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 portrait;
            background: #ffffff;
        }

        body {
            font-family: "Microsoft YaHei", "PingFang SC", sans-serif;
            width: 100%;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-size: 14px;
            line-height: 1.6;
            color: #000;
            background: #ffffff;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;

            .title {
                font-size: 24px;
                font-weight: bold;
                letter-spacing: 2px;
            }

            .sub-title {
                font-size: 16px;
                margin-top: 4px;
            }
        }

        .info {
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;

            .row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 4px 0;
                font-size: 14px;

                .label {
                    font-weight: bold;
                    white-space: nowrap;
                    margin-right: 8px;
                }

                .value {
                    text-align: right;
                    word-break: break-all;
                }
            }

            .address-row {
                .value {
                    text-align: left;
                    flex: 1;
                    margin-left: 8px;
                }
            }
        }

        .items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
            font-weight: bold;
            border-bottom: 2px solid #000;

            .col-name {
                flex: 1;
            }

            .col-qty {
                width: 15%;
                text-align: center;
            }

            .col-price {
                width: 15%;
                text-align: right;
            }

            .col-subtotal {
                width: 15%;
                text-align: right;
            }
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 8px 0;
            font-size: 14px;
            border-bottom: 1px dashed #eee;

            .col-name {
                flex: 1;

                .product-name {
                    font-size: 14px;
                    font-weight: 500;
                }

                .sku-name {
                    font-size: 13px;
                    color: #666;
                    margin-top: 2px;
                }

                .item-remark {
                    font-size: 12px;
                    color: #999;
                    margin-top: 2px;
                }
            }

            .col-qty {
                width: 15%;
                text-align: center;
                font-weight: bold;
            }

            .col-price {
                width: 15%;
                text-align: right;
            }

            .col-subtotal {
                width: 15%;
                text-align: right;
                font-weight: 500;
            }
        }

        .summary {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #000;
            font-size: 14px;

            .row {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;

                .label {
                    font-weight: bold;
                }
            }

            .remark {
                margin-top: 8px;
                padding: 8px;
                background: #f9f9f9;
                border-radius: 4px;
                font-size: 13px;

                .remark-label {
                    font-weight: bold;
                    margin-right: 4px;
                }
            }
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="title">{{ $order->tenant->name }}</div>
    <div class="sub-title">分拣单</div>
</div>

<div class="info">
    <div class="row">
        <span class="label">订单编号</span>
        <span class="value">{{ $order->no }}</span>
    </div>
    <div class="row">
        <span class="label">下单时间</span>
        <span class="value">{{ $order->created_at->format('Y-m-d H:i') }}</span>
    </div>
    @if($order->paid_at)
        <div class="row">
            <span class="label">付款时间</span>
            <span class="value">{{ $order->paid_at->format('Y-m-d H:i') }}</span>
        </div>
    @endif
    @if($order->address)
        <div class="row">
            <span class="label">收货人</span>
            <span class="value">{{ $order->address->name }} {{ $order->address->mobile }}</span>
        </div>
        <div class="row address-row">
            <span class="label">地址</span>
            <span class="value">{{ $order->address->full_address }}</span>
        </div>
    @endif
</div>

<div class="items-header">
    <span class="col-name">商品</span>
    <span class="col-qty">数量</span>
    <span class="col-price">单价</span>
    <span class="col-subtotal">小计</span>
</div>

@foreach($order->items as $item)
    <div class="item-row">
        <div class="col-name">
            <div class="product-name">{{ $item->orderable?->product?->name ?? $item->orderable_name }}</div>
            @if($item->orderable?->name)
                <div class="sku-name">{{ $item->orderable->name }}</div>
            @endif
            @if($item->remark)
                <div class="item-remark">备注: {{ $item->remark }}</div>
            @endif
        </div>
        <div class="col-qty">x{{ $item->qty }}</div>
        <div class="col-price">&yen;{{ number_format($item->price, 2) }}</div>
        <div class="col-subtotal">&yen;{{ number_format($item->sub_total, 2) }}</div>
    </div>
@endforeach

<div class="summary">
    <div class="row">
        <span class="label">商品总数</span>
        <span>{{ $order->items_quantity }} 件</span>
    </div>
    <div class="row">
        <span class="label">商品金额</span>
        <span>&yen;{{ number_format($order->amount, 2) }}</span>
    </div>
    @if($order->freight > 0)
        <div class="row">
            <span class="label">运费</span>
            <span>&yen;{{ number_format($order->freight, 2) }}</span>
        </div>
    @endif
    <div class="row">
        <span class="label">实付金额</span>
        <span>&yen;{{ number_format($order->total_amount, 2) }}</span>
    </div>

    @if($order->remark)
        <div class="remark">
            <span class="remark-label">买家备注:</span>{{ $order->remark }}
        </div>
    @endif

    @if($order->seller_remark)
        <div class="remark">
            <span class="remark-label">商家备注:</span>{{ $order->seller_remark }}
        </div>
    @endif
</div>

<div class="footer">
    <div>打印时间：{{ now()->format('Y-m-d H:i:s') }}</div>
    <div>制单人：{{ Auth::user()->name ?? '' }}</div>
</div>
</body>
</html>
