<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncVendors extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vendors:sync {--fix : Fix inconsistencies automatically}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize vendors and users, fix inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== VENDOR SYNCHRONIZATION ===');
        
        // 1. Check current state
        $totalVendors = Vendor::count();
        $totalVendorUsers = User::where('role', 'vendeur')->count();
        $orphanVendors = Vendor::whereNull('user_id')->orWhereDoesntHave('user')->count();
        $missingVendors = User::where('role', 'vendeur')->whereDoesntHave('vendor')->count();
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Vendors', $totalVendors],
                ['Vendor Users', $totalVendorUsers],
                ['Orphan Vendors', $orphanVendors],
                ['Missing Vendors', $missingVendors],
            ]
        );
        
        if ($orphanVendors > 0 || $missingVendors > 0) {
            $this->warn('Inconsistencies detected!');
            
            if ($this->option('fix')) {
                $this->fixInconsistencies();
            } else {
                $this->info('Run with --fix to automatically resolve issues.');
            }
        } else {
            $this->info('✅ All vendors are synchronized!');
        }
        
        return 0;
    }
    
    /**
     * Fix vendor inconsistencies
     */
    private function fixInconsistencies(): void
    {
        $this->info('Fixing inconsistencies...');
        
        DB::transaction(function() {
            // Remove orphan vendors
            $orphanCount = Vendor::whereNull('user_id')
                ->orWhereDoesntHave('user')
                ->forceDelete();
            
            if ($orphanCount > 0) {
                $this->info("Removed {$orphanCount} orphan vendors");
            }
            
            // Create missing vendors
            $missingUsers = User::where('role', 'vendeur')
                ->whereDoesntHave('vendor')
                ->get();
            
            foreach($missingUsers as $user) {
                Vendor::create([
                    'user_id' => $user->id,
                    'shop_name' => $user->name . ' Boutique',
                    'description' => 'Boutique de ' . $user->name,
                    'status' => 'pending',
                ]);
                
                $this->info("Created vendor for user: {$user->email}");
            }
            
            if ($missingUsers->count() > 0) {
                $this->info("Created {$missingUsers->count()} missing vendors");
            }
        });
        
        $this->info('✅ Inconsistencies fixed!');
    }
}
