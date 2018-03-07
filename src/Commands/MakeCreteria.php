<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Saad\Fractal\Commands\BaseMakeCommand;

class MakeCreteria extends BaseMakeCommand
{
    /**
     * Output File name
     * 
     * @var string
     */
    protected $file_name;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:creteria {name} {--nest=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Creteria';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        parent::handle();

        try {
            // Create Creteria
            $this->create("Creteria");
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            $this->info('Rolling Back');
            $this->delete("Creteria");
        }
    }

    /**
     * Get Model Value from command
     * 
     * @return string model name
     */
    protected function getModelValue() {
        return null;
    }

    /**
     * Get Output Filename Value from command
     * 
     * @return string model name
     */
    protected function getFilenameValue() {
        return $this->argument('name');
    }

    /**
     * Get Stubs Path
     * @return string stubs path
     */
    protected function getStubsPath() {
        return __DIR__ . "/../../resources/stubs";
    }

    /**
     * Get Output Directory Name
     * @return string Directory name
     */
    protected function getOutputDirectoryName() {
        return 'Creteria';
    }
}