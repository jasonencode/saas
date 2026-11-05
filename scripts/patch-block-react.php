<?php

/**
 * 修复 clue/block-react 在 PHP 8.4+ 的隐式可空参数弃用警告。
 *
 * 该包（v1.5.0，2021 年后再无发版）的 4 个函数将 LoopInterface 参数声明为
 * `LoopInterface $loop = null`，PHP 8.4 起会输出
 * "Implicitly marking parameter $loop as nullable is deprecated"。
 * 它由 alibabacloud/darabonba 传递依赖引入，没有可用新版本，因此在 composer
 * 安装/更新后自动打补丁，避免每次命令都输出弃用噪音。
 */
$file = dirname(__DIR__).'/vendor/clue/block-react/src/functions.php';

if (!is_file($file)) {
    exit(0);
}

$contents = file_get_contents($file);

$patched = preg_replace(
    '/(?<!\?)\bLoopInterface \$loop = null/',
    '?LoopInterface $loop = null',
    $contents,
    -1,
    $count
);

if ($count > 0 && $patched !== null) {
    file_put_contents($file, $patched);

    fwrite(STDOUT, "Patched clue/block-react: {$count} signature(s).".PHP_EOL);
}
