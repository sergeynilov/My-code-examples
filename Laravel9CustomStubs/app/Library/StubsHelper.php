<?php

namespace App\Library;

use App\Enums\StubFileType;
use App\Exceptions\StubsGeneratorError;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Pluralizer;
use Str;

class StubsHelper
{
    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * Stubs container file with stubs methods
     *
     * @var StubCodeContainer class
     */
    protected $stubContainer;

    public function __construct(Filesystem $filesystem)
    {
        $this->fs = $filesystem;
        $this->stubContainer = new StubCodeContainer();
    }

    /**
     * Return the Singular Capitalize Name
     *
     * @param $className
     *
     * @return string
     */
    function getSingularClassName($className)
    {
        return ucwords(Pluralizer::singular($className));
    }

    /**
     * Return the stub source file path based on stub file type
     *
     * @param StubFileType $stubFilePath )
     *
     * @return string
     */
    public function getStubSourceFilePath(StubFileType $stubFilePath): string
    {
        if ( ! $this->fs->exists($stubFilePath->value)) {
            throw new StubsGeneratorError(
                message: "Source stub controller file : {$stubFilePath->value} not found",
                code: 500
            );
        }

        return $stubFilePath->value;
    }

    /**
     * Create the directory for the class if necessary
     *
     * @param string $path
     *
     * @return string
     */
    public function createDestinationDirectory($path)
    {
        if ( ! $this->fs->isDirectory($path)) {
            $this->fs->createDestinationDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Remove additive CRLF chars which have more 2
     *
     * string $contents - stub code
     *
     * @return string
     */
    public function removeAdditiveCRLFs(string $contents): string
    {
        return preg_replace('~\n{3,}~i', "\n", $contents);
    }

    /**
     * Get the full path stub file of generate controller class based on stub file type and model name
     *
     * @param StubFileType $stubFilePath
     *
     * @param string $className
     *
     * @return string
     */
    public function getDestinationPatternFilePath(StubFileType $stubFilePath, string $className): string
    {
        return StubFileType::getDestFilePath($stubFilePath, $className);
    }

    /**
     * Get stub declaration for fields based table name
     *
     * @return array of block which be inserted into destination stub files
     * string $fieldsCastCode
     *
     * array of $useDeclarations, $traitDeclarations, $additiveMethods
     */
    public function getPredefinedFieldsCode(string $tableName): array
    {
        $columns = Schema::getColumnListing($tableName);
        $slugSourceFieldName = '';
        $fieldsCastCode = '';
        $traitDeclarations = [];
        $useDeclarations = [];
        $additiveMethods = [];
        foreach ($columns as $column) {
            if ($column === 'title' or $column === 'name') {
                $slugSourceFieldName = $column;
            }
        }
        reset($columns);
        foreach ($columns as $column) {
            switch (Schema::getColumnType($tableName, $column)) {
                case 'datetime':
                    $fieldsCastCode .= "        '" . $column . "' => 'datetime',\n";
                    break;
                case 'date':
                    $fieldsCastCode .= "        '" . $column . "' => 'date',\n";
                    break;
                case 'integer':
                    if (Str::substrCount($column, 'price') or Str::substrCount($column,
                            'salary') or Str::substrCount($column, 'rate')) {
                        $fieldsCastCode .= "        '" . $column . "' => MoneyCast::class,\n";
                        $useDeclarations[] = 'use App\Casts\MoneyCast;';
                    }
                    break;
                case 'string':
                    if ($column === 'slug') {
                        $useDeclarations[] = 'use Cviebrock\EloquentSluggable\Sluggable;';
                        $traitDeclarations[] = 'use Sluggable;';
                        $additiveMethods[] = $this->stubContainer->getSluggableFieldCode($slugSourceFieldName);
                    }
                    break;
                case 'json':
                    $fieldsCastCode = "        '" . $column . "' => 'array',\n";
                    break;
                default:
                    break;
            }
        }

        return [$fieldsCastCode, $useDeclarations, $traitDeclarations, $additiveMethods];
    }

}
