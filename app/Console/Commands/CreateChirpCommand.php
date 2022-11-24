<?php

namespace App\Console\Commands;

use App\Models\Chirp;
use App\Models\User;
use Illuminate\Console\Command;

class CreateChirpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chirp:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new chirp';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::get()->random();

        $user->chirps()->create(Chirp::factory()->make()->toArray());

        return Command::SUCCESS;
    }
}
