<?php

namespace  Osoobe\LaravelTraits\Support;

use Illuminate\Support\Facades\Storage;
use Osoobe\Utilities\Helpers\Utilities;
use Osoobe\Utilities\Helpers\Str;

trait HasFiles {

    /**
     * Download file
     *
     * @param string $path
     * @param string $disk
     * @return mixed
     */
    public function downloadFile(string $path, string $disk='public') {
        return Storage::disk($disk)->download($path);
    }

    /**
     * Return folder path
     *
     * @param string $disk
     * @return array
     */
    public function getStorePath($disk='public') {
        $folder = $this->kekabPath();
        $disk_obj = Storage::disk($disk);
        $path = $disk_obj->path($folder);
        $this->makeStorePath($path);
        return [
            'disk_relative_path' => $folder,
            'disk' => $disk_obj,
            'absolute_path' => $path
        ];
    }

    /**
     * Create storage path
     *
     * @param string $directory
     * @return bool
     */
    protected function makeStorePath($directory) {
        if ( ! file_exists($directory)  ) {
            try {
                Storage::makeDirectory($directory);
            } catch (\Throwable $th) {
                return false;
            }
            return true;
        }
        return false;
    }


    /**
     * Kebab Path with id
     *
     * @return string
     */
    public function kekabPath() {
        $class = Utilities::getClassNameOnly(get_class($this));
        $class = Str::kebab($class);
        $plural = Str::plural($class);
        return "$plural/$class-$this->id";
    }


    /**
     * Store files in the public folder
     *
     * @param string $paths
     * @param string|resource $contents
     * @param string $disk
     * @param mixed $options
     * @return bool
     */
    public function storeFile(string $paths, $contents, $disk='public', $options=[]) {
        $folder = $this->kekabPath();
        $paths = $folder.'/'.rtrim($paths, '/');
        return Storage::disk($disk)->put($paths, $contents, $options);
    }


    /**
     * Store files in the public folder
     * 
     * @uses HasFiles::storePath
     *
     * @param string $paths
     * @param string|resource $contents
     * @param mixed $options
     * @return bool
     */
    public function storePrivateFile(string $paths, $contents, $options=[]) {
        return $this->storeFile($paths, $contents, 'private', $options);
    }

    /**
     * Store files in the public folder
     * 
     * @uses HasFiles::storePath
     *
     * @param string $paths
     * @param string|resource $contents
     * @param mixed $options
     * @return bool
     */
    public function storePublicFile(string $paths, $contents, $options=[]) {
        return $this->storeFile($paths, $contents, 'public', $options);
    }


    /**
     * Kebab Path with id
     *
     * @deprecated 1.5.0
     * @return string
     */
    public function getFolderPath() {
        return $this->kekabPath();
    }

    /**
     * Get private file
     *
     * @param string $filename
     * @return string
     */
    public function getPrivateFile($filename) {
        $path = $this->getStorePath('private');
        return $path['absolute_path'].'/'.rtrim($filename, '/');
    }


    /**
     * Get public file
     *
     * @param string $filename
     * @return string
     */
    public function getPublicFile($filename) {
        $path = $this->getStorePath('public');
        return $path['absolute_path'].'/'.rtrim($filename, '/');
    }
    
    /**
     * Return private disk or path
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|array 
     */
    public function privateDisk() {
        $folder = $this->kekabPath();

        $path = storage_path("app/private/$folder");
        $this->makeStorePath($path);

        // Storage::build is only available in Laravel 8
        // As a result, this function will return an array if the
        // Storage::build does not exist.
        if ( method_exists(Storage::class, 'build') ) {
            return Storage::build([
                'driver' => 'local',
                'root' => $path,
                'url' => env('APP_URL').'/storage',
                'visibility' => 'private',
            ]);
        }

        return [
            'disk_relative_path' => $folder,
            'absolute_path' => $path
        ];
    }

    /**
     * Return public disk or path
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|array 
     */
    public function publicDisk() {
        $folder = $this->kekabPath();

        $path = storage_path("app/private/$folder");
        $this->makeStorePath($path);

        // Storage::build is only available in Laravel 8
        // As a result, this function will return an array if the
        // Storage::build does not exist.
        if ( method_exists(Storage::class, 'build') ) {
            return Storage::build([
                'driver' => 'local',
                'root' => $path,
                'url' => env('APP_URL').'/storage',
                'visibility' => 'private',
            ]);
        }
        return [
            'disk_relative_path' => $folder,
            'absolute_path' => $path
        ];
    }


}

?>