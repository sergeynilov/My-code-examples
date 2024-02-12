<?php

namespace App\Library;

use App\Models\Vote;
use App\Models\VoteItem;
use App\Notifications\TelegramVoteCreated\AttachImage;
use Carbon\Carbon;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Sitemap\Tags\Url;
use ZipArchive;

//http://php.net/manual/en/class.ziparchive.php
use App\Exceptions\AppArchiverCustomException;
use App;

/*

Class to archive given files into one zip file.

Example of use:
        $appArchiver = new AppArchiver();
        $appArchiver->setFiltersByExtensions(allowedExtensions: ['pNG', 'JPG', 'jpEg', 'CSV', 'ods', 'Doc'])
            ->setDestZipFileFullPath(public_path() . '/storage/tmp/archive' . (date('d-m-Y')) . '.zip')
            ->addFileByModel(Vote::class, 3)
            ->addFileByModel(Vote::class, 2)
            ->addFileByModel(Vote::class, 4)
            ->addFileByModel(Vote::class, 4)
            ->addFileByModel(Vote::class, 4)
            ->addFileByModel(Vote::class, 7)
            ->addFileByModel(Vote::class, 8)
            ->addFileByModel(VoteItem::class, 29)
            ->addFileByModel(VoteItem::class, 30)
            ->setSkipNonExistingFile(false)
            ->setFilesByFullPath([
                '/_wwwroot/lar/MS/MS_Votes/public/image-files/slogan_1.jpg',
                '/_wwwroot/lar/MS/MS_Votes/public/image-files/rules-of-our-site.pdf',
                '/_wwwroot/lar/MS/MS_Votes/public/image-files/clients.csv',
                '/_wwwroot/lar/MS/MS_Votes/public/image-files/our_prices.ods',
                '/_wwwroot/lar/MS/MS_Votes/public/image-files/deep-copy.png'
            ])
            ->addFileByFullPath('/_wwwroot/lar/MS/MS_Votes/public/image-files/terms.doc')
            ->run();

        $ret = $appArchiver->getInfo(true);
        echo '<pre>$ret::' . print_r($ret, true) . '</pre>';



 */

class AppArchiver
{
    /* @var string - location of destination file */
    protected string $destZipFileFullPath;

    /* @var bool - if not found source file must be skipped(true), if false exception would be generated */
    protected bool $skipNonExistingFile = false;

    /* @var array - list of files which must be attached into archive */
    protected array $filesByFullPath = [];

    /* @var array - list of models/id which can attached file, which must be attached into archive */
    protected array $filesByModel = [];

    /* @var array - list of allowed file extensions */
    protected array $filtersByAllowedExtensions = [];

    /* @var int - number of files really attached into archive */
    protected int $archivedFilesCount = 0;


    public function __construct()
    {
        throw_if(! class_exists('ZipArchive'), AppArchiverCustomException::class,
            'Class ZipArchive is not installed !');
    }

    /* @var string - full path of file to be attached */
    public function addFileByFullPath(string $value): self
    {
        if ( ! in_array($value, $this->filesByFullPath)) {
            $this->filesByFullPath[] = $value;
        }

        return $this;
    }

    /* @var string $model = string model class, int $id = id of model class */
    public function addFileByModel(string $model, int $id): self
    {
        $this->filesByModel[] = [
            'model' => $model,
            'id'    => $id
        ];

        return $this;
    }

    /* @var array of files by full path */
    public function setFilesByFullPath(array $value): self
    {
        $this->filesByFullPath = $value;

        return $this;
    }

    public function setFiltersByExtensions(array $allowedExtensions = []): self
    {
        $this->filtersByAllowedExtensions = Arr::map($allowedExtensions, function ($value, $key) {
            return Str::lower($value);
        });

        return $this;
    }

    public function setSkipNonExistingFile(bool $value): self
    {
        $this->skipNonExistingFile = $value;

        return $this;
    }

    public function setDestZipFileFullPath(string $value): self
    {
        $this->destZipFileFullPath = $value;

        return $this;
    }

    /* @var read all images from models and files set by full path and archive them */
    public function run(): bool
    {
        foreach ($this->filesByModel as $fileByMode) { // get all images from assigned models
            try {
                $modelData  = App::make($fileByMode['model'])
                    ->findOrFail($fileByMode['id']);
                $modelMedia = $modelData->getFirstMedia(config('app.media_app_name'));
                if ( ! empty($modelMedia) and File::exists($modelMedia->getPath())) {
                    if ( ! in_array($modelMedia->getPath(), $this->filesByFullPath)) {
                        $this->filesByFullPath[] = $modelMedia->getPath();
                    }
                }
            } catch (BindingResolutionException $e) {
                App\Library\AppCustomException::getInstance()::raiseChannelError(
                    errorMsg: 'Model # ' . $fileByMode['model'] . ' not found',
                    exceptionClass: BindingResolutionException::class,
                    file: __FILE__,
                    line: __LINE__
                );
                continue;
            }
        }

        // Exclude files which are not in allowed_extensions
        foreach ($this->filesByFullPath as $key => $fileFullPath) {
            $extension = Str::lower(\File::extension($fileFullPath));
            if (count($this->filtersByAllowedExtensions) > 0 and ! in_array($extension,
                    $this->filtersByAllowedExtensions)) {
                unset($this->filesByFullPath[$key]);
            }
        }
        throw_if(count($this->filesByFullPath) === 0, AppArchiverCustomException::class, 'No files to attach provided');

        // Create lacking directories for $this->destZipFileFullPath
        $destDirectoryPath = Str::beforeLast($this->destZipFileFullPath, '/');
        $destDirectories   = Str::of($destDirectoryPath)->explode('/')->toArray();
        $createDirs        = [];
        $dir               = '/';
        foreach ($destDirectories as $destDirectory) {
            $dir          .= '/' . $destDirectory;
            $createDirs[] = $dir;
        }
        createDir($createDirs);

        // Create archive with list of files in $this->filesByFullPath into $this->destZipFileFullPath file
        $this->archivedFilesCount = 0;
        $zipArchive               = new ZipArchive;
        foreach ($this->filesByFullPath as $fileFullPath) { // all files to attach
            $filenameBasename = getFilenameBasename($fileFullPath);
            $extension        = Str::lower(\File::extension($fileFullPath));
            try {
                if ($zipArchive->open($this->destZipFileFullPath, ZipArchive::CREATE) === true) {
                    $zipArchive->addFile($fileFullPath, $filenameBasename . '.' . $extension);
                    $this->archivedFilesCount++;
                }
            } catch (\Exception $e) {
                throw_if(! $this->skipNonExistingFile, AppArchiverCustomException::class,
                    $e->getMessage() . ': Check if source file "' . $fileFullPath . '" exists.');
            }

        } // foreach( $this->filesByFullPath as $fileFullPath ) { // all files to attach

        if ($this->archivedFilesCount > 0) {
            try {
                $zipArchive->close();
            } catch (\ErrorException $e) {
                throw new AppArchiverCustomException($e->getMessage() . ': Check if path for destination file "' . $this->destZipFileFullPath . '" is valid.');
            }

            return true;
        }
        $this->destZipFileFullPath = '';

        return false;
    }

    public function getInfo(bool $asArray = true): array|string
    {
        if ($asArray) {
            return [
                'result'              => $this->archivedFilesCount > 0,
                'archivedFilesCount'  => $this->archivedFilesCount,
                'destZipFileFullPath' => $this->destZipFileFullPath
            ];
        }

        return
            'result : ' . ($this->archivedFilesCount > 0) . ', ' .
            'archived files count : ' . $this->archivedFilesCount . ', ' .
            'destination zip file full path : ' . $this->destZipFileFullPath;
    }

}
