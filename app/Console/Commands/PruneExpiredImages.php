<?php

namespace App\Console\Commands;

use App\Models\ProcessedImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneExpiredImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:prune-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune persistent image storage assets and clean up records exceeding their designated expiration timestamp limits.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Locating expired image records...');

        $expiredImages = ProcessedImage::where('expires_at', '<=', now())->get();

        $count = 0;
        foreach ($expiredImages as $image) {
            // Remove physical file from public storage disk
            if (Storage::disk('public')->exists($image->disk_path)) {
                Storage::disk('public')->delete($image->disk_path);
            }

            // Remove database record
            $image->delete();
            $count++;
        }

        $this->info("Successfully pruned {$count} expired physical images and database tracking records.");
    }
}
