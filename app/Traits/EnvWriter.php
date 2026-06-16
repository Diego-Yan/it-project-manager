<?php

namespace App\Traits;

use App\Services\EnvService;

/**
 * [REVIEW-FIX] SP1.3: 提供统一的 updateEnv() 方法给 Admin 组件使用。
 *
 * 替代 AdSettingsManager、AiSettings、ImSettings 中各自私有的 updateEnv()，
 * 三个组件现在 use EnvWriter trait 即可消除最后一层间接调用。
 */
trait EnvWriter
{
    /**
     * 更新 .env 文件中的键值对并刷新配置缓存。
     */
    protected function updateEnv(array $updates): void
    {
        EnvService::write($updates);
    }
}
