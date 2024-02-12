<?php

namespace App\Console\Commands;

use App\Enums\StubFileType;
use App\Exceptions\StubsGeneratorError;
use App\Library\StubCodeContainer;
use App\Library\Services\Interfaces\StubsInterface;
use App\Library\StubsHelper;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Str;

/**
 * Create a new CRUD Model based on stubs/custom.model.stub file.
 *
 * @param {name?} - Optional. Model name
 * @param {showDone?} - Optional. if set then done/undone methods are added
 * @param {transactionWithInterface?} - Optional. if set then transaction with interface class are used
 * @param {help?} - Optional. if set then help text is shown, but new model is not created
 */
class MakeCustomModelCommand extends Command implements StubsInterface
{
    /**
     * The name and signature of the console command with parameters.
     *
     * @var string
     */
    protected $signature = 'make:custom-model {name} {userRelation?} {productRelation?} {productCityRelation?} {categoryRelation?} {discountsRelation?} {help?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make custom model class based on stub stubs/custom.model.stub file';

    /**
     * In help mode need to show help text and exit
     *
     * @var bool
     */
    protected bool $consoleHelp;

    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected $fs;

    /**
     * Stubs Helper file with methods
     *
     * @var stubsHelper class
     */
    protected $stubsHelper;

    /**
     * Stubs container file with stubs methods
     *
     * @var StubCodeContainer class
     */
    protected $stubContainer;

    /**
     * If need to overwrite destination file if it exists
     *
     * @var bool
     */
    protected $overwriteFile = true;

    /**
     *  To add code with creator model Relation
     *
     * @var bool
     */
    protected bool $creatorRelation;

    /**
     * To add code with user model Relation
     *
     * @var bool
     */
    protected bool $userRelation;

    /**
     * To add code with productRelation
     *
     * @var bool
     */
    protected bool $productRelation;

    /**
     * To add code with product City Relation
     *
     * @var bool
     */
    protected bool $productCityRelation;

    /**
     * To add code with category Relation
     *
     * @var bool
     */
    protected bool $categoryRelation;

    /**
     * To add code with discount Relation
     *
     * @var bool
     */
    protected bool $discountsRelation;

    /**
     * Working with file
     *
     * @param Filesystem $fs
     */
    public function __construct(Filesystem $fs)
    {
        parent::__construct();

        $this->fs = $fs;
        $this->stubContainer = new StubCodeContainer();
        $this->stubsHelper = new StubsHelper($this->fs);
    }

    /**
     * Execute the console command to create a new model file
     */
    public function handle()
    {
        try {
            $destinationPath =  $this->stubsHelper->getDestinationPatternFilePath(StubFileType::CUSTOM_MODEL,
                $this->argument('name'));
            if (empty($destinationPath)) {
                $this->error("Destination file path is not defined");
                return;
            }

            $this->stubsHelper->createDestinationDirectory(dirname($destinationPath));
            $resultingCode = $this->generateResultingCode();
            if ( ! $this->fs->exists($destinationPath) or $this->overwriteFile) {
                $this->fs->put($destinationPath, $resultingCode);
                $this->info("File : {$destinationPath} created");
            } else {
                $this->error("File : {$destinationPath} already exits");
            }
        } catch (StubsGeneratorError $e) {
            $this->error("Stubs generator error : " . $e->getMessage());
        } catch (\Exception|\Error $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     **
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVars(): array
    {
        $arguments = $this->arguments();
        $this->consoleHelp = Str::lower($arguments['name']) === 'help';
        if ($this->consoleHelp) {
            $stubContainer = new StubCodeContainer();
            foreach ($stubContainer->getModelHelp() as $modelHelpLine) {
                $this->info($modelHelpLine);
            };
            die('Help shown');
        }

        $singularClassName = $this->stubsHelper->getSingularClassName($this->argument('name')); // Product
        $this->userRelation = in_array('userRelation', $arguments);
        $this->creatorRelation = in_array('creatorRelation', $arguments);
        $this->productRelation = in_array('productRelation', $arguments);
        $this->productCityRelation = in_array('productCityRelation', $arguments);
        $this->categoryRelation = in_array('categoryRelation', $arguments);
        $this->discountsRelation = in_array('discountsRelation', $arguments);

        return [
            'namespacePath' => 'App\\Models',
            'ucFirstClass' => Str::ucfirst($singularClassName),   // ProductCategory
            'pluralUcFirstClass' => Str::plural(Str::ucfirst($singularClassName)),   // ProductCategories
            'camelCaseClass' => Str::of($singularClassName)->camel(),   // productCategory
            'pluralCamelCaseClass' => Str::plural(Str::of($singularClassName)->camel()),   // productCategories
            'lowercaseClass' => Str::lower($singularClassName),   // productcategory
            'uppercaseClass' => Str::upper(Str::snake($singularClassName)), // PRODUCTCATEGORY
            'snakePlural' => Str::plural(Str::snake($singularClassName, '_')), // PRODUCT_CATEGORY
        ];
    }

    /**
     * Replace variables of stub with the class value
     *
     * @param $stub
     * @param array $stubVariables
     *
     * @return string
     */
    public function getStubContents($stub, $stubVariables = []): string
    {
        $contents = file_get_contents($stub);
        $singularClassName = $this->stubsHelper->getSingularClassName($this->argument('name')); // Product
        foreach ($stubVariables as $search => $replace) {
            $contents = preg_replace('~{{[\s]*' . $search . '[\s]*}}~i', $replace, $contents);
        }

        $relationsCode = $this->stubContainer->getGetByIdCode();
        if ($this->userRelation) {
            $relationsCode .= $this->stubContainer->getUserRelationCode();
        }
        if ($this->creatorRelation) {
            $relationsCode .= $this->stubContainer->getCreatorRelationCode();
        }
        if ($this->productRelation) {
            $relationsCode .= $this->stubContainer->getProductRelationCode(sourceModel: Str::ucfirst($singularClassName));
        }
        if ($this->productCityRelation) {
            $relationsCode .= $this->stubContainer->getProductCityRelationCode();
        }
        if ($this->categoryRelation) {
            $relationsCode .= $this->stubContainer->getCategoryRelationCode(sourceModel: Str::ucfirst($singularClassName));
        }
        if ($this->discountsRelation) {
            $relationsCode .= $this->stubContainer->getDiscountsRelationCode();
        }

        $contents = preg_replace('~{{[\s]*relationsCode[\s]*}}~i', $relationsCode, $contents);

        $fillableFields = $this->stubContainer->getFillableFromDbCode(tableName: Str::plural(Str::snake($singularClassName,
            '_')));
        $contents = preg_replace('~{{[\s]*fillableFields[\s]*}}~i', $fillableFields, $contents);

        [$castingFields, $useDeclarations, $traitDeclarations, $additiveMethods] = $this->stubsHelper->getPredefinedFieldsCode(tableName: Str::plural(Str::snake($singularClassName,
            '_')));
        $contents = preg_replace('~{{[\s]*castingFields[\s]*}}~i', $castingFields, $contents);

        $useDeclarationsCode = '';
        foreach (array_unique($useDeclarations) as $useDeclaration) {
            if ( ! empty($useDeclaration)) {
                $useDeclarationsCode .= $useDeclaration . "\n";
            }
        }
        $contents = preg_replace('~{{[\s]*useDeclarations[\s]*}}~i', $useDeclarationsCode, $contents);

        $traitDeclarationsCode = '';
        foreach (array_unique($traitDeclarations) as $traitDeclaration) {
            if ( ! empty($traitDeclaration)) {
                $traitDeclarationsCode .= $traitDeclaration . "\n";
            }
        }
        $contents = preg_replace('~{{[\s]*traitDeclarations[\s]*}}~i', $traitDeclarationsCode, $contents);

        $additiveMethodsCode = '';
        foreach (array_unique($additiveMethods) as $additiveMethod) {
            if ( ! empty($additiveMethod)) {
                $additiveMethodsCode .= $additiveMethod . "\n";
            }
        }
        $contents = preg_replace('~{{[\s]*additiveMethods[\s]*}}~i', $additiveMethodsCode, $contents);

        return $contents;
    }

    /**
     * Get the stub path and the stub variables and generate resulting code string
     *
     * @return string
     *
     */
    public function generateResultingCode(): string
    {
        return $this->getStubContents($this->stubsHelper->getStubSourceFilePath(StubFileType::CUSTOM_MODEL),
            $this->getStubVars());
    }

}
