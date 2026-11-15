<?php

namespace App\Events\Mall;

/**
 * 退款审核驳回事件
 *
 * 在 Reject 落库后派发（ShouldDispatchAfterCommit），由监听器通知用户。
 */
class RefundRejected extends RefundBaseEvent {}
