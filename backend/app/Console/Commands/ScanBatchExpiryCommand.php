<?php

namespace App\Console\Commands;

use App\Repositories\ProductBatchRepository;
use Illuminate\Console\Command;

class ScanBatchExpiryCommand extends Command
{
    protected $signature = 'batches:scan-expiry';

    protected $description = '扫描批次临期/过期状态，自动更新状态并标记过期批次不可售';

    public function __construct(
        private ProductBatchRepository $batchRepository
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('开始扫描批次临期/过期状态...');

        $result = $this->batchRepository->scanAndUpdateBatchStatuses();

        $this->info("扫描完成！");
        $this->table(
            ['状态更新', '数量'],
            [
                ['标记为已过期', $result['expired']],
                ['标记为临期', $result['expiring_soon']],
                ['恢复为正常', $result['back_to_normal']],
            ]
        );

        if ($result['expired'] > 0) {
            $this->warn("警告：有 {$result['expired']} 个批次已过期并被自动标记为不可售！");
        }

        if ($result['expiring_soon'] > 0) {
            $this->comment("提示：有 {$result['expiring_soon']} 个批次即将到期（30天内），请注意处理。");
        }

        return Command::SUCCESS;
    }
}
