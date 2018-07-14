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

class MakeRepository extends BaseMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {model} {--nest=} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make Model Repository';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! parent::handle()) {
            return;
        }

        try {
            // Create Contract
            $this->create("RepositoryContract", '/Contracts');
            // Create Repository
            $this->create("Repository");
            // Add To Service Provider
            $this->addToServiceProviders();
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            $this->info('Rolling Back');

            $this->delete("RepositoryContract", '/Contracts');
            $this->delete("Repository");
            // Remove From Service Provider
            $this->removeFromServiceProviders();
        }
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
        return 'Repositories';
    }

    /**
     * Add Binding to service providers
     */
    protected function addToServiceProviders()
    {
        $stub_content = $this->filesystem->get($this->getStubsPath() . '/ServiceProvider.stub');
        $stub_content = $this->processStubContent($stub_content, null, $this->getStubOutputFileBaseName('Repository'));

        $this->updateProviderBinding('/(register\(\).*?\{)/s', "$1 {$stub_content}");
    }

    /**
     * Remove Binding from service providers
     */
    protected function removeFromServiceProviders()
    {
        $this->updateProviderBinding($this->getBindPattern(), '', false);
    }

    /**
     * Get Service Provider Bind Pattern 
     * 
     * @return string pattern
     */
    protected function getBindPattern() {
        $file_basename = $this->getStubOutputFileBaseName('Repository');
        return "/#\sBind\s".$file_basename.".*?#\s".$file_basename."\sEnd/s";
    }

    /**
     * Add or Remove binding to Service Provider
     * 
     * @param  [type]  $find_pattern [description]
     * @param  [type]  $replacement  [description]
     * @param  boolean $add          [description]
     * @return [type]                [description]
     */
    protected function updateProviderBinding($find_pattern, $replacement, $add = true) {
        $service_provider = app_path('Providers/AppServiceProvider.php');
        $content = $this->filesystem->get($service_provider);

        $already_exists = preg_match($this->getBindPattern(), $content);
        if (($add && !$already_exists) || (!$add && $already_exists)) {
            $this->replaceFileContent($find_pattern, $replacement, $content, $service_provider);
        }
    }

    /**
     * Replace File content by prepared content
     * 
     * @param  [type] $find_pattern     [description]
     * @param  [type] $replacement      [description]
     * @param  [type] $content          [description]
     * @param  [type] $service_provider [description]
     * @return [type]                   [description]
     */
    protected function replaceFileContent($find_pattern, $replacement, $content, $service_provider)
    {
        $final = preg_replace($find_pattern, $replacement, $content);
        $this->filesystem->put($service_provider, $final);
    }
}