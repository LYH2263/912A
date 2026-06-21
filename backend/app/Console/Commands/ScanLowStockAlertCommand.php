<?php

namespace App\Console\Commands;

use App\Services\InventoryAlertService;
use Illuminate\Console\Command;

class ScanLowStockAlertCommand extends Command
{
    protected $signature = 'inventory:scan-low-stock';

    protected $description = '扫描低库存商品并生成预警记录';

    public function __construct(
        private InventoryAlertService $alertService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('开始扫描低库存商品...');

        $result = $this->alertService->scanLowStock();

        $this->info("扫描完成！");
        $this->table(
            ['指标', '数量'],
            [
                ['低库存商品总数', $result['total_low_stock']],
                ['新增预警记录', $result['created']],
                ['已存在未读预警', $result['skipped']],
            ]
        );

        return Command::SUCCESS;
    }
}
