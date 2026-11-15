<?php

namespace App\Exceptions;

use InvalidArgumentException;

/**
 * 可预期业务异常
 *
 * 渲染为 400 + 原始提示，并按 warning 记录（不记 5xx 堆栈）。
 * 与直接抛 RuntimeException / InvalidArgumentException 的区别：语义显式，
 * 且不受「仅 app/ 目录抛出」的来源限制，推荐作为业务错误的标准出口。
 */
class BusinessException extends InvalidArgumentException {}
