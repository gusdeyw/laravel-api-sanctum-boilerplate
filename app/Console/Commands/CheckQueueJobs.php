<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckQueueJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:status 
                            {--clear : Clear all pending jobs}
                            {--process : Process one job}
                            {--watch : Watch queue status in real-time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check queue jobs status and manage them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('clear')) {
            return $this->clearJobs();
        }

        if ($this->option('process')) {
            return $this->processJob();
        }

        if ($this->option('watch')) {
            return $this->watchQueue();
        }

        $this->showQueueStatus();
    }

    private function showQueueStatus()
    {
        $this->info('📊 Queue Status Dashboard');
        $this->newLine();

        // Check jobs table
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        $this->table(['Status', 'Count'], [
            ['Pending Jobs', $pendingJobs],
            ['Failed Jobs', $failedJobs],
        ]);

        if ($pendingJobs > 0) {
            $this->newLine();
            $this->info('📋 Pending Jobs:');

            $jobs = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $jobData = [];
            foreach ($jobs as $job) {
                $payload = json_decode($job->payload, true);
                $jobClass = $payload['displayName'] ?? 'Unknown';

                $jobData[] = [
                    'ID' => $job->id,
                    'Job' => $jobClass,
                    'Queue' => $job->queue ?: 'default',
                    'Attempts' => $job->attempts,
                    'Created' => $job->created_at,
                ];
            }

            $this->table(['ID', 'Job', 'Queue', 'Attempts', 'Created'], $jobData);

            $this->newLine();
            $this->info('💡 Commands:');
            $this->line('• Process one job: php artisan queue:status --process');
            $this->line('• Start worker: php artisan queue:work');
            $this->line('• Clear all jobs: php artisan queue:status --clear');
        }

        if ($failedJobs > 0) {
            $this->newLine();
            $this->warn("⚠️  You have {$failedJobs} failed jobs. Check with: php artisan queue:failed");
        }

        if ($pendingJobs === 0 && $failedJobs === 0) {
            $this->info('✅ Queue is empty - no pending or failed jobs');
        }
    }

    private function clearJobs()
    {
        $count = DB::table('jobs')->count();

        if ($count === 0) {
            $this->info('✅ No jobs to clear');
            return;
        }

        if ($this->confirm("Clear {$count} pending jobs?")) {
            DB::table('jobs')->delete();
            $this->info("✅ Cleared {$count} jobs from queue");
        }
    }

    private function processJob()
    {
        $this->info('🔄 Processing one job...');

        $exitCode = $this->call('queue:work', [
            '--once' => true,
            '--verbose' => true,
        ]);

        if ($exitCode === 0) {
            $this->info('✅ Job processed successfully');
        } else {
            $this->error('❌ Failed to process job');
        }
    }

    private function watchQueue()
    {
        $this->info('👀 Watching queue status (Press Ctrl+C to stop)...');
        $this->newLine();

        while (true) {
            $this->line("\033[2J\033[H"); // Clear screen
            $this->showQueueStatus();
            $this->line('Refreshing in 5 seconds...');
            sleep(5);
        }
    }
}
