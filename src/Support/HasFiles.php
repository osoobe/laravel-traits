<?php

namespace  Osoobe\LaravelTraits\Support;

use Illuminate\Support\Facades\Storage;
use Osoobe\Utilities\Helpers\Utilities;
use Osoobe\Utilities\Helpers\Str;

trait HasFiles {

    /**
     * Return folder path
     *
     * @param string $disk
     * @return array
     */
    public function getStorePath($disk='public') {
        $folder = $this->getFolderPath();
        $disk_obj = Storage::disk($disk);
        $path = $disk_obj->path($folder);
        $this->makeStorePath($path);
        return (object) [
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
     * Return folder path
     *
     * @return string
     */
    public function getFolderPath() {
        $class = Utilities::getClassNameOnly(get_class($this));
        $class = Str::kebab($class);
        $folder = "$class/tenant-$this->id";
        return $folder;
    }

    /**
     * Return private disk or path
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|array 
     */
    public function privateDisk() {
        $folder = $this->getFolderPath();

        // Storage::build is only available in Laravel 8
        // As a result, this function will return an array if the
        // Storage::build does not exist.
        if ( method_exists(Storage::class, 'build') ) {
            return Storage::build([
                'driver' => 'local',
                'root' => storage_path("app/private/$folder"),
                'url' => env('APP_URL').'/storage',
                'visibility' => 'private',
            ]);
        }

        return [
            'disk_relative_path' => $folder,
            'absolute_path' => storage_path("app/private/$folder")
        ];
    }

    /**
     * Return public disk or path
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|array 
     */
    public function publicDisk() {
        $folder = $this->getFolderPath();

        // Storage::build is only available in Laravel 8
        // As a result, this function will return an array if the
        // Storage::build does not exist.
        if ( method_exists(Storage::class, 'build') ) {
            return Storage::build([
                'driver' => 'local',
                'root' => storage_path("app/public/$folder"),
                'url' => env('APP_URL').'/storage',
                'visibility' => 'private',
            ]);
        }
        return [
            'disk_relative_path' => $folder,
            'absolute_path' => storage_path("app/public/$folder")
        ];
    }


}

?>