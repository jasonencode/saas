<?php

namespace App\Events\Mall;

/**
 * 退款审核通过事件
 *
 * 在 Approve 落库后派发（ShouldDispatchAfterCommit），由监听器通知用户。
 */
class RefundApproved extends RefundBaseEvent {}
